<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use App\Services\Oracle\OracleJobLookupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelRequestService
{
    private const STATUS_REQUESTED = 'requested';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        private readonly OracleJobLookupService $oracleJobLookup,
    ) {}

    public function create(array $data): LabelRequest
    {
        return DB::transaction(function () use ($data): LabelRequest {
            $payload = $this->buildCreatePayload($data);

            return LabelRequest::query()->create($payload)->load(['line', 'shift']);
        });
    }

    public function lookupOracleJob(string $jobNumber): array
    {
        return $this->oracleJobLookup->buildLookupPayload($jobNumber);
    }

    public function complete(LabelRequest $labelRequest): LabelRequest
    {
        if ($labelRequest->status === self::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'status' => 'No se puede completar una requisición cancelada.',
            ]);
        }

        $labelRequest->update(['status' => self::STATUS_COMPLETED]);

        return $labelRequest->refresh();
    }

    public function cancel(LabelRequest $labelRequest): LabelRequest
    {
        if (in_array($labelRequest->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden cancelar requisiciones en estado requested o in_progress.',
            ]);
        }

        $labelRequest->update(['status' => self::STATUS_CANCELLED]);

        return $labelRequest->refresh();
    }

    private function buildCreatePayload(array $data): array
    {
        $payload = $data;
        $payload['status'] = self::STATUS_REQUESTED;

        $jobNumber = (string) ($payload['job_number'] ?? '');

        if ($jobNumber === '') {
            return $payload;
        }

        $job = $this->oracleJobLookup->findByJobNumber($jobNumber);

        if (!$job) {
            return $payload;
        }

        if (empty($payload['po_number'])) {
            $payload['po_number'] = strtoupper(trim((string) $job->ttl_cust_po));
        }

        if (empty($payload['destination'])) {
            $payload['destination'] = strtoupper(trim((string) $job->ship_code));
        }

        return $payload;
    }
}
