<?php

namespace App\Services\Masters;

use App\Models\MasterPrintBatch;
use App\Models\MasterRequest;

class MasterReprintService
{
    public function buildHistoryData(MasterRequest $masterRequest): array
    {
        $requestIds = MasterRequest::query()
            ->select('id')
            ->whereKey($masterRequest->id)
            ->when(! $masterRequest->isRework(), fn ($query) => $query
                ->orWhere('parent_master_request_id', $masterRequest->id));

        return [
            'mr' => $masterRequest->load(['line', 'shift']),
            'printBatches' => MasterPrintBatch::query()
                ->whereIn('master_request_id', $requestIds)
                ->with(['masterRequest', 'printedBy', 'items.folio'])
                ->orderByDesc('printed_at')
                ->orderByDesc('id')
                ->get(),
        ];
    }

    public function searchByJob(string $job)
    {
        return MasterRequest::query()
            ->whereNotNull('request_type')
            ->whereNull('parent_master_request_id')
            ->with(['line', 'shift'])
            ->withCount('printBatches')
            ->withCount('revisions')
            ->when($job !== '', function ($query) use ($job) {
                $query->where(function ($nested) use ($job) {
                    $nested->where('job_assembly', 'like', "%{$job}%")
                        ->orWhere('job_packaging', 'like', "%{$job}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }
}
