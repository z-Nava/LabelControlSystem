<?php

namespace App\Services\Masters;

use App\Models\MasterRequest;
use App\Models\ProductionLine;
use App\Models\Shift;

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
            ->when($status === 'pending', fn ($query) => $query->whereIn('status', ['requested', 'in_progress']))
            ->when($status === 'completed', fn ($query) => $query->where('status', 'completed'))
            ->when($status === 'cancelled', fn ($query) => $query->where('status', 'cancelled'))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('id', $q)
                        ->orWhere('leader_name', 'like', "%{$q}%")
                        ->orWhere('job_assembly', 'like', "%{$q}%")
                        ->orWhere('job_packaging', 'like', "%{$q}%")
                        ->orWhere('po_number', 'like', "%{$q}%");
                });
            })
            ->latest('request_date')
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
        return [
            'lines' => ProductionLine::where('active', true)->orderBy('code')->get(),
            'shifts' => Shift::where('active', true)->orderBy('code')->get(),
        ];
    }

    public function findForShow(int $id): MasterRequest
    {
        return MasterRequest::with(['line', 'shift', 'folios'])->findOrFail($id);
    }
}
