<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use App\Models\LabelSku;
use App\Models\ProductionLine;
use App\Models\Shift;
use App\Models\SkuSerialFormat;
use App\Support\SerialStandards;
use Illuminate\Support\Collection;

class LabelRequestReadService
{
    private const INDEX_STATUS_OPTIONS = [
        'active' => 'Pendientes (todas)',
        LabelRequest::STATUS_REQUESTED => 'Pendiente',
        LabelRequest::STATUS_IN_PROGRESS => 'En preparación',
        LabelRequest::STATUS_READY_FOR_DELIVERY => 'Lista para entregar',
        LabelRequest::STATUS_COMPLETED => 'Entregada',
        LabelRequest::STATUS_CANCELLED => 'Cancelada',
        'all' => 'Todas',
    ];

    public function paginateForIndex(array $filters, int $perPage = 15): array
    {
        $validated = [
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'line_id' => $filters['line_id'] ?? null,
            'shift_id' => $filters['shift_id'] ?? null,
            'request_kind' => $filters['request_kind'] ?? null,
            'status' => $filters['status'] ?? 'active',
            'sku_np' => trim((string) ($filters['sku_np'] ?? '')),
        ];

        $labelRequests = LabelRequest::query()
            ->with([
                'line:id,name,code',
                'shift:id,name,code',
                'serials:id,label_request_id,part_number,model,position',
                'ratings:id,label_request_id,part_number,model,position',
                'shippingItems:id,label_request_id,item_reference,model,position',
                'lpkLabelGroups:id,label_request_id,label_type,part_number,position',
                'lpkLabelGroups.items:id,label_request_lpk_label_group_id,job_number,model,quantity,position',
                'lpkShippingGroups:id,label_request_id,part_number,quantity,po_number,destination,position',
                'lpkShippingGroups.items:id,label_request_lpk_shipping_group_id,job_number,model,position',
            ])
            ->withCount('printBatches')
            ->when($validated['date_from'], fn ($query, $value) => $query->whereDate('request_date', '>=', $value))
            ->when($validated['date_to'], fn ($query, $value) => $query->whereDate('request_date', '<=', $value))
            ->when($validated['line_id'], fn ($query, $value) => $query->where('line_id', $value))
            ->when($validated['shift_id'], fn ($query, $value) => $query->where('shift_id', $value))
            ->when($validated['request_kind'], fn ($query, $value) => $query->where('request_kind', $value))
            ->when($validated['status'] === 'active', fn ($query) => $query->open())
            ->when(
                ! in_array($validated['status'], ['active', 'all'], true),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->when($validated['sku_np'] !== '', function ($query) use ($validated) {
                $search = $validated['sku_np'];

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('label_part_number', 'like', "%{$search}%")
                        ->orWhere('serial_part_number', 'like', "%{$search}%")
                        ->orWhere('inner_part_number', 'like', "%{$search}%")
                        ->orWhere('shipping_part_number', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('inner_model', 'like', "%{$search}%")
                        ->orWhere('shipping_model', 'like', "%{$search}%")
                        ->orWhereHas('serials', fn ($serialQuery) => $serialQuery
                            ->where('part_number', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%"))
                        ->orWhereHas('ratings', fn ($ratingQuery) => $ratingQuery
                            ->where('part_number', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%"))
                        ->orWhereHas('shippingItems', fn ($itemQuery) => $itemQuery
                            ->where('item_reference', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%"))
                        ->orWhereHas('lpkLabelGroups', fn ($groupQuery) => $groupQuery
                            ->where('part_number', 'like', "%{$search}%")
                            ->orWhereHas('items', fn ($itemQuery) => $itemQuery
                                ->where('job_number', 'like', "%{$search}%")
                                ->orWhere('model', 'like', "%{$search}%")))
                        ->orWhereHas('lpkShippingGroups', fn ($groupQuery) => $groupQuery
                            ->where('part_number', 'like', "%{$search}%")
                            ->orWhere('po_number', 'like', "%{$search}%")
                            ->orWhere('destination', 'like', "%{$search}%")
                            ->orWhereHas('items', fn ($itemQuery) => $itemQuery
                                ->where('job_number', 'like', "%{$search}%")
                                ->orWhere('model', 'like', "%{$search}%")))
                        ->orWhere('job_number', 'like', "%{$search}%")
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
            'labelRequestRows' => $labelRequests->getCollection()
                ->map(fn (LabelRequest $labelRequest): array => $this->buildIndexRow($labelRequest)),
            'filters' => $validated,
            'statusOptions' => self::INDEX_STATUS_OPTIONS,
            'lines' => ProductionLine::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'code', 'line_type']),
            'shifts' => Shift::query()->orderBy('id')->get(['id', 'name', 'code']),
        ];
    }

    /**
     * @return array{
     *     labelRequest: LabelRequest,
     *     serialPartNumbers: array<int, string>,
     *     ratingPartNumbers: array<int, string>,
     *     innerItem: ?string,
     *     shippingItems: array<int, string>,
     *     shippingItemSummary: string,
     *     hasGroupedLpkDetails: bool,
     *     lpkProductionJobs: Collection<int, string>
     * }
     */
    private function buildIndexRow(LabelRequest $labelRequest): array
    {
        $formatItems = fn (array $items): array => collect($items)
            ->map(fn (array $item): string => $item['part_number'].($item['model'] ? ' · '.$item['model'] : ''))
            ->all();

        $serialPartNumbers = $formatItems($labelRequest->requestedSerialItems());
        $ratingPartNumbers = $formatItems($labelRequest->requestedRatingItems());
        $shippingItems = $formatItems($labelRequest->requestedShippingItems());
        $hasGroupedLpkDetails = $labelRequest->hasGroupedLpkDetails();

        return [
            'labelRequest' => $labelRequest,
            'serialPartNumbers' => $serialPartNumbers,
            'ratingPartNumbers' => $ratingPartNumbers,
            'innerItem' => $labelRequest->include_inner
                ? collect([$labelRequest->inner_part_number, $labelRequest->inner_model])->filter()->implode(' · ')
                : null,
            'shippingItems' => $shippingItems,
            'shippingItemSummary' => $shippingItems !== []
                ? implode(', ', array_slice($shippingItems, 0, 2))
                : 'Sin NP capturado',
            'hasGroupedLpkDetails' => $hasGroupedLpkDetails,
            'lpkProductionJobs' => $hasGroupedLpkDetails
                ? $labelRequest->lpkLabelGroups->flatMap->items->pluck('job_number')->unique()->values()
                : collect(),
        ];
    }

    public function buildCreateFormData(): array
    {
        return [
            'defaultDate' => now()->toDateString(),
            'defaultWeek' => (int) now()->isoWeek(),
            'defaultStandard' => 'UL',
            'serialStandards' => SerialStandards::requestFlow(),
            'lines' => ProductionLine::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'code', 'line_type']),
            'shifts' => Shift::query()->orderBy('id')->get(['id', 'name', 'code']),
            'labelSkus' => LabelSku::query()
                ->active()
                ->whereExists(function ($query) {
                    $query->selectRaw('1')
                        ->from((new SkuSerialFormat)->getTable())
                        ->whereColumn('sku_serial_formats.sku', 'label_skus.sku')
                        ->whereColumn('sku_serial_formats.serial_standard', 'label_skus.serial_standard')
                        ->where('sku_serial_formats.is_active', true);
                })
                ->orderBy('sku')
                ->orderBy('serial_standard')
                ->get([
                    'sku',
                    'serial_standard',
                    'label_part_number',
                    'description',
                    'assembly_part_number',
                    'packaging_part_number',
                ]),
        ];
    }

    public function buildKioskCreateFormData(): array
    {
        return [
            'defaultDate' => now()->toDateString(),
            'defaultWeek' => (int) now()->isoWeek(),
            'lines' => ProductionLine::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'line_type']),
            'shifts' => Shift::query()
                ->where('active', true)
                ->orderBy('id')
                ->get(['id', 'name', 'code']),
        ];
    }

    public function findForShow(int $id): LabelRequest
    {
        return LabelRequest::query()
            ->with([
                'line:id,name,code',
                'shift:id,name,code',
                'requestedByUser:id,name',
                'oracleJob:id,job_number,assembly,part_description,job_qty,quantity_remainder',
                'requisitionPrintedByUser:id,name',
                'readyForDeliveryByUser:id,name',
                'deliveredByUser:id,name',
                'cancelledByUser:id,name',
                'serials:id,label_request_id,part_number,model,position',
                'ratings:id,label_request_id,part_number,model,position',
                'shippingItems:id,label_request_id,item_reference,model,position',
                'lpkLabelGroups:id,label_request_id,label_type,part_number,position',
                'lpkLabelGroups.items:id,label_request_lpk_label_group_id,job_number,model,quantity,position',
                'lpkShippingGroups:id,label_request_id,part_number,quantity,po_number,destination,position',
                'lpkShippingGroups.items:id,label_request_lpk_shipping_group_id,job_number,model,position',
                'printBatches' => fn ($query) => $query->with('printedByUser:id,name')->latest('printed_at')->latest('id'),
                'serialRanges' => fn ($query) => $query->with('week:id,label_part_number,week,year,prefix,last_serial_number')->orderBy('range_start'),
            ])
            ->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildShowViewData(int $id): array
    {
        $labelRequest = $this->findForShow($id);
        $printBatches = $labelRequest->printBatches;
        $hasGroupedLpkDetails = $labelRequest->hasGroupedLpkDetails();

        return [
            'labelRequest' => $labelRequest,
            'printBatches' => $printBatches,
            'hasUnprintedPrintBatch' => $printBatches->contains(
                fn ($batch) => $batch->batch_type === 'print' && $batch->printed_at === null
            ),
            'hasGroupedLpkDetails' => $hasGroupedLpkDetails,
            'lpkProductionJobs' => $hasGroupedLpkDetails
                ? $labelRequest->lpkLabelGroups->flatMap->items->pluck('job_number')->unique()->values()
                : collect(),
            'workBlocks' => $this->buildWorkBlocks($labelRequest, $hasGroupedLpkDetails),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildWorkBlocks(LabelRequest $labelRequest, bool $hasGroupedLpkDetails): array
    {
        $legacyLines = collect($labelRequest->requestedLabelLines());
        $types = [
            ['key' => 'serial', 'label' => 'Serial', 'selected' => $labelRequest->include_serial],
            ['key' => 'rating', 'label' => 'Rating', 'selected' => $labelRequest->include_rating],
            ['key' => 'inner', 'label' => 'Inner', 'selected' => $labelRequest->include_inner],
            ['key' => 'shipping', 'label' => 'Shipping', 'selected' => $labelRequest->include_shipping],
        ];

        return collect($types)
            ->filter(fn (array $type) => $type['selected'])
            ->map(function (array $type) use ($labelRequest, $legacyLines, $hasGroupedLpkDetails): array {
                if (! $hasGroupedLpkDetails) {
                    $lines = $legacyLines
                        ->filter(fn (array $line) => str_starts_with(strtolower($line['type']), $type['key']))
                        ->values();

                    return [
                        'key' => $type['key'],
                        'label' => $type['label'],
                        'mode' => 'legacy',
                        'groups' => collect(),
                        'lines' => $lines,
                        'task_count' => $lines->count(),
                    ];
                }

                $groups = $type['key'] === 'shipping'
                    ? $labelRequest->lpkShippingGroups
                    : $labelRequest->lpkLabelGroups->where('label_type', $type['key'])->values();

                return [
                    'key' => $type['key'],
                    'label' => $type['label'],
                    'mode' => $type['key'] === 'shipping' ? 'shipping' : 'production',
                    'groups' => $groups,
                    'lines' => collect(),
                    'task_count' => $groups->count(),
                ];
            })
            ->values()
            ->all();
    }
}
