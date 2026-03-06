<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use App\Services\Oracle\OracleJobLookupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelRequestService
{
    public function __construct(
        private readonly OracleJobLookupService $oracleJobLookup,
    ) {}

    public function create(array $data): LabelRequest
    {
        return DB::transaction(function () use ($data) {
            $payload = $data;
            $payload['status'] = 'requested';

            $jobNumber = (string) ($payload['job_number'] ?? '');
            if ($jobNumber !== '') {
                $job = $this->oracleJobLookup->findByJobNumber($jobNumber);

                if ($job) {
                    if (empty($payload['po_number'])) {
                        $payload['po_number'] = strtoupper(trim((string) $job->ttl_cust_po));
                    }

                    if (empty($payload['destination'])) {
                        $payload['destination'] = strtoupper(trim((string) $job->ship_code));
                    }
                }
            }

            return LabelRequest::query()->create($payload)->load(['line', 'shift']);
        });
    }

    public function complete(LabelRequest $labelRequest): LabelRequest
    {
        if ($labelRequest->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'No se puede completar una requisición cancelada.',
            ]);
        }

        $labelRequest->update(['status' => 'completed']);

        return $labelRequest->refresh();
    }

    public function cancel(LabelRequest $labelRequest): LabelRequest
    {
        if (in_array($labelRequest->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden cancelar requisiciones en estado requested o in_progress.',
            ]);
        }

        $labelRequest->update(['status' => 'cancelled']);

        return $labelRequest->refresh();
    }
}
