<?php

namespace App\Services\Masters;

use App\Models\MasterRequest;

class MasterReprintService
{
    public function loadRequestWithBatches(MasterRequest $masterRequest): MasterRequest
    {
        return $masterRequest->load([
            'line',
            'shift',
            'printBatches' => fn ($query) => $query
                ->with(['printedBy', 'items.folio'])
                ->orderByDesc('printed_at')
                ->orderByDesc('id'),
        ]);
    }

    public function searchByJob(string $job)
    {
        return MasterRequest::query()
            ->whereNotNull('request_type')
            ->with(['line', 'shift'])
            ->withCount('printBatches')
            ->when($job !== '', function ($query) use ($job) {
                $query->where(function ($nested) use ($job) {
                    $nested->where('job_assembly', 'like', "%{$job}%")
                        ->orWhere('job_packaging', 'like', "%{$job}%");
                });
            })
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }
}