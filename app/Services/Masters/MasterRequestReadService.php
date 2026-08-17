<?php

namespace App\Services\Masters;

use App\Models\MasterModelMapping;
use App\Models\MasterRequest;
use App\Models\ProductionLine;
use App\Models\Shift;
use App\Models\StockLocator;

class MasterRequestReadService
{
    public function paginateForIndex(array $query): array
    {
        $status = (string) ($query['status'] ?? 'pending');
        $q = trim((string) ($query['q'] ?? ''));

        $masterRequests = MasterRequest::query()
            ->with(['line', 'shift'])
            ->withCount([
                'folios as total_folios',
                'folios as printed_folios' => fn ($query) => $query->where('status', 'printed'),
            ])
            ->when($status === 'pending', fn ($query) => $query->whereIn('status', [
                MasterRequest::STATUS_REQUESTED,
                MasterRequest::STATUS_IN_PROGRESS,
            ]))
            ->when($status === 'completed', fn ($query) => $query->where('status', MasterRequest::STATUS_COMPLETED))
            ->when($status === 'cancelled', fn ($query) => $query->where('status', MasterRequest::STATUS_CANCELLED))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('id', $q)
                        ->orWhere('leader_name', 'like', "%{$q}%")
                        ->orWhere('job_assembly', 'like', "%{$q}%")
                        ->orWhere('job_packaging', 'like', "%{$q}%")
                        ->orWhere('po_number', 'like', "%{$q}%");
                });
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return [
            'masterRequests' => $masterRequests,
            'filters' => [
                'status' => $status,
                'q' => $q,
            ],
        ];
    }

    public function buildCreateFormData(): array
    {
        $inventoryMappings = StockLocator::query()
            ->where('active', true)
            ->get()
            ->keyBy(fn (StockLocator $mapping): string => strtoupper(trim($mapping->oracle_line)));

        return [
            'lines' => ProductionLine::where('active', true)->orderBy('code')->get(),
            'shifts' => Shift::where('active', true)->orderBy('code')->get(),
            'masterRequestTypes' => MasterModelMapping::requestOptions(),
            'ortAssemblyConfig' => MasterModelMapping::ortAssemblyRequestConfiguration(),
            'inventoryMappings' => $inventoryMappings,
        ];
    }

    public function findForShow(int $id): MasterRequest
    {
        return MasterRequest::with(['line', 'shift', 'folios', 'cancelledBy:id,name'])->findOrFail($id);
    }
}
