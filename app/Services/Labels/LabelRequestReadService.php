<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use App\Models\LabelSku;
use App\Models\ProductionLine;
use App\Models\SkuSerialFormat;
use App\Models\Shift;

class LabelRequestReadService
{
    public function paginateForIndex(array $filters, int $perPage = 15): array
    {
        $validated = [
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'line_id' => $filters['line_id'] ?? null,
            'shift_id' => $filters['shift_id'] ?? null,
            'status' => $filters['status'] ?? null,
            'sku_np' => trim((string) ($filters['sku_np'] ?? '')),
        ];

        $labelRequests = LabelRequest::query()
            ->with(['line:id,name,code', 'shift:id,name,code'])
            ->withCount('printBatches')
            ->when($validated['date_from'], fn ($query, $value) => $query->whereDate('request_date', '>=', $value))
            ->when($validated['date_to'], fn ($query, $value) => $query->whereDate('request_date', '<=', $value))
            ->when($validated['line_id'], fn ($query, $value) => $query->where('line_id', $value))
            ->when($validated['shift_id'], fn ($query, $value) => $query->where('shift_id', $value))
            ->when($validated['status'], fn ($query, $value) => $query->where('status', $value))
            ->when($validated['sku_np'] !== '', function ($query) use ($validated) {
                $search = $validated['sku_np'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('label_part_number', 'like', "%{$search}%")
                        ->orWhereIn('label_part_number', function ($labelSkuQuery) use ($search) {
                            $labelSkuQuery->select('label_part_number')
                                ->from('label_skus')
                                ->where('sku', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'labelRequests' => $labelRequests,
            'filters' => $validated,
            'lines' => ProductionLine::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'code', 'line_type']),
            'shifts' => Shift::query()->orderBy('id')->get(['id', 'name', 'code']),
        ];
    }

    public function buildCreateFormData(): array
    {
        return [
            'defaultDate' => now()->toDateString(),
            'defaultWeek' => (int) now()->isoWeek(),
            'lines' => ProductionLine::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'code', 'line_type']),
            'shifts' => Shift::query()->orderBy('id')->get(['id', 'name', 'code']),
            'labelSkus' => LabelSku::query()
                ->active()
                ->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from((new SkuSerialFormat())->getTable())
                        ->whereColumn('sku_serial_formats.sku', 'label_skus.sku')
                        ->where('sku_serial_formats.is_active', true);
                })
                ->orderBy('sku')
                ->get(['sku', 'label_part_number', 'description']),
        ];
    }

    public function findForShow(int $id): LabelRequest
    {
        return LabelRequest::query()
            ->with([
                'line:id,name,code',
                'shift:id,name,code',
                'requestedByUser:id,name',
                'printBatches' => fn ($query) => $query->with('printedByUser:id,name')->latest('printed_at')->latest('id'),
                'serialRanges' => fn ($query) => $query->with('week:id,label_part_number,week,year,prefix,last_serial_number')->orderBy('range_start'),
            ])
            ->findOrFail($id);
    }
}
