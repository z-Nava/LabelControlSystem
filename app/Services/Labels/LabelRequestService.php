<?php

namespace App\Services\Labels;

use App\Models\LabelPrintBatch;
use App\Models\LabelRequest;
use App\Models\SerialUnit;
use App\Models\SerialWeek;
use App\Services\Oracle\OracleJobService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelRequestService
{
    private const STATUS_REQUESTED = 'requested';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        private readonly OracleJobService $oracleJobService,
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
        return $this->oracleJobService->buildLookupPayload($jobNumber);
    }

    public function complete(LabelRequest $labelRequest, bool $forceWithoutPrintedBatch = false): LabelRequest
    {
        if ($labelRequest->status === self::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'status' => 'No se puede completar una requisición cancelada.',
            ]);
        }

        $hasPrintedPrintBatch = LabelPrintBatch::query()
            ->where('label_request_id', $labelRequest->id)
            ->where('batch_type', 'print')
            ->whereNotNull('printed_at')
            ->exists();

        if (!$hasPrintedPrintBatch && !$forceWithoutPrintedBatch) {
            throw ValidationException::withMessages([
                'status' => 'Esta requisición no tiene un batch print confirmado como impreso. Si continúas, el serial quedará asignado y no podrá reutilizarse automáticamente.',
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

        $hasPrintedPrintBatch = LabelPrintBatch::query()
            ->where('label_request_id', $labelRequest->id)
            ->where('batch_type', 'print')
            ->whereNotNull('printed_at')
            ->exists();

        if ($hasPrintedPrintBatch) {
            throw ValidationException::withMessages([
                'status' => 'No se puede cancelar: ya existe un batch print confirmado como impreso.',
            ]);
        }

        DB::transaction(function () use ($labelRequest): void {
            $serialWeekIds = $labelRequest->serialRanges()
                ->select('serial_week_id')
                ->distinct()
                ->pluck('serial_week_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();

            $unitIds = SerialUnit::query()
                ->whereIn('serial_week_id', $serialWeekIds)
                ->whereExists(function ($existsQuery) use ($labelRequest) {
                    $existsQuery->selectRaw('1')
                        ->from('serial_ranges as sr')
                        ->whereColumn('sr.serial_week_id', 'serial_units.serial_week_id')
                        ->whereColumn('serial_units.serial_number', '>=', 'sr.range_start')
                        ->whereColumn('serial_units.serial_number', '<=', 'sr.range_end')
                        ->where('sr.label_request_id', $labelRequest->id);
                })
                ->pluck('id');

            if ($unitIds->isNotEmpty()) {
                SerialUnit::query()->whereIn('id', $unitIds)->delete();
            }

            $labelRequest->printBatches()->delete();
            $labelRequest->serialRanges()->delete();

            foreach ($serialWeekIds as $serialWeekId) {
                $lastSerialNumber = (int) SerialUnit::query()
                    ->where('serial_week_id', $serialWeekId)
                    ->max('serial_number');

                SerialWeek::query()
                    ->whereKey($serialWeekId)
                    ->update(['last_serial_number' => $lastSerialNumber]);
            }

            $labelRequest->update(['status' => self::STATUS_CANCELLED]);
        });

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
