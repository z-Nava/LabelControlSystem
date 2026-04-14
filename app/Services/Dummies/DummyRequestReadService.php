<?php

namespace App\Services\Dummies;

use App\Models\DummyRequest;
use App\Models\ProductionLine;
use App\Models\Shift;

class DummyRequestReadService
{
    public function paginateForIndex(array $filters, int $perPage = 15): array
    {
        $validated = [
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'line_id' => $filters['line_id'] ?? null,
            'shift_id' => $filters['shift_id'] ?? null,
            'status' => $filters['status'] ?? null,
            'request_type' => $filters['request_type'] ?? null,
            'job_number' => trim((string) ($filters['job_number'] ?? '')),
        ];

        $dummyRequests = DummyRequest::query()
            ->with(['line:id,name,code', 'shift:id,name,code'])
            ->withCount('items')
            ->withSum('printBatches as printed_qty', 'quantity')
            ->when($validated['date_from'], fn ($query, $value) => $query->whereDate('request_date', '>=', $value))
            ->when($validated['date_to'], fn ($query, $value) => $query->whereDate('request_date', '<=', $value))
            ->when($validated['line_id'], fn ($query, $value) => $query->where('line_id', $value))
            ->when($validated['shift_id'], fn ($query, $value) => $query->where('shift_id', $value))
            ->when($validated['status'], fn ($query, $value) => $query->where('status', $value))
            ->when($validated['request_type'], fn ($query, $value) => $query->where('request_type', $value))
            ->when($validated['job_number'] !== '', fn ($query) => $query->where('job_number', 'like', '%' . $validated['job_number'] . '%'))
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'dummyRequests' => $dummyRequests,
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
            'requestTypes' => [
                'first_time' => 'Primera vez (RMT Dummy QR)',
                'rework' => 'Retrabajo (RW Dummy QR)',
            ],
        ];
    }

    public function findForShow(int $id): DummyRequest
    {
        return DummyRequest::query()
            ->with([
                'line:id,name,code',
                'shift:id,name,code',
                'requestedByUser:id,name',
                'items' => fn ($query) => $query->orderBy('consecutive')->limit(200),
                'printBatches' => fn ($query) => $query->with('printedByUser:id,name')->latest('printed_at')->latest('id'),
            ])
            ->findOrFail($id);
    }
}
