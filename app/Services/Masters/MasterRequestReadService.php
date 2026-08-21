<?php

namespace App\Services\Masters;

use App\Models\MasterModelMapping;
use App\Models\MasterRequest;

class MasterRequestReadService
{
    public function paginateForIndex(array $query): array
    {
        $status = (string) ($query['status'] ?? 'pending');
        $q = trim((string) ($query['q'] ?? ''));

        $masterRequests = MasterRequest::query()
            ->whereNull('parent_master_request_id')
            ->with(['line', 'shift', 'requestedBy:id,name'])
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
                        ->orWhere('requested_by_name', 'like', "%{$q}%")
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

    public function buildLabelRoomCreateFormData(): array
    {
        return [
            'masterRequestTypes' => MasterModelMapping::requestOptions(),
            'ortAssemblyConfig' => MasterModelMapping::ortAssemblyRequestConfiguration(),
            'alternateStockLocator' => MasterModelMapping::ALTERNATE_STOCK_LOCATOR,
        ];
    }

    public function buildKioskCreateFormData(): array
    {
        return $this->buildLabelRoomCreateFormData();
    }

    public function findForShow(int $id): MasterRequest
    {
        return MasterRequest::with([
            'line',
            'shift',
            'requestedBy:id,name',
            'folios',
            'cancelledBy:id,name',
            'revisions' => fn ($query) => $query->with(['line', 'reworkedBy:id,name'])->withCount('folios'),
        ])->findOrFail($id);
    }
}
