<?php

namespace App\Services\Labels;

use App\Models\LabelPrintBatch;
use App\Models\LabelRequest;
use App\Models\OracleJob;
use App\Models\SerialUnit;
use App\Models\SerialWeek;
use App\Services\Oracle\OracleJobService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelRequestService
{
    public function __construct(
        private readonly OracleJobService $oracleJobService,
        private readonly LabelRequestJobAvailabilityService $availabilityService,
    ) {}

    public function create(array $data): LabelRequest
    {
        return DB::transaction(function () use ($data): LabelRequest {
            $payload = $this->buildCreatePayload($data);

            return LabelRequest::query()->create($payload)->load(['line', 'shift']);
        });
    }

    public function createKiosk(array $data): LabelRequest
    {
        return DB::transaction(function () use ($data): LabelRequest {
            $ratingPartNumbers = collect($data['rating_part_numbers'] ?? [])
                ->map(fn ($partNumber) => strtoupper(trim((string) $partNumber)))
                ->filter()
                ->unique()
                ->values();

            unset($data['rating_part_numbers']);

            $jobNumber = strtoupper(trim((string) ($data['job_number'] ?? '')));
            $job = OracleJob::query()
                ->whereRaw('UPPER(TRIM(job_number)) = ?', [$jobNumber])
                ->lockForUpdate()
                ->first();

            if (! $job) {
                throw ValidationException::withMessages([
                    'job_number' => 'El Job no existe en Oracle Jobs.',
                ]);
            }

            $availability = $this->availabilityService->calculate($job);
            $requestedQuantity = (int) ($data['quantity_requested'] ?? 0);

            if ($requestedQuantity > $availability['available_quantity']) {
                throw ValidationException::withMessages([
                    'quantity_requested' => "La cantidad solicitada supera la disponibilidad actual del Job ({$availability['available_quantity']}).",
                ]);
            }

            $data['job_number'] = $jobNumber;
            $data['serial_standard'] = 'UL';
            $data['label_part_number'] = $data['include_rating']
                ? $ratingPartNumbers->first()
                : null;
            $data['folio_start'] = null;
            $data['folio_end'] = null;
            $data['po_number'] = $this->valueOrOracleFallback($data['po_number'] ?? null, $job->ttl_cust_po);
            $data['destination'] = $this->valueOrOracleFallback($data['destination'] ?? null, $job->ship_code);

            $labelRequest = LabelRequest::query()->create($this->buildCreatePayload($data));

            if ($data['include_rating']) {
                $labelRequest->ratings()->createMany(
                    $ratingPartNumbers
                        ->map(fn (string $partNumber, int $position) => [
                            'part_number' => $partNumber,
                            'position' => $position + 1,
                        ])
                        ->all(),
                );
            }

            return $labelRequest->load(['line', 'shift', 'ratings']);
        }, attempts: 3);
    }

    public function lookupOracleJob(string $jobNumber): array
    {
        $payload = $this->oracleJobService->buildLookupPayload($jobNumber);

        if (! ($payload['found'] ?? false)) {
            return $payload;
        }

        $job = $this->oracleJobService->findByJobNumber($jobNumber);

        if (! $job) {
            return $payload;
        }

        return [...$payload, ...$this->availabilityService->calculate($job)];
    }

    public function markRequisitionPrinted(LabelRequest $labelRequest, ?int $userId): LabelRequest
    {
        if (! $labelRequest->canMarkRequisitionPrinted()) {
            throw ValidationException::withMessages([
                'status' => 'Solo una requisición pendiente puede marcarse como impresa.',
            ]);
        }

        $labelRequest->update([
            'status' => LabelRequest::STATUS_IN_PROGRESS,
            'requisition_printed_at' => now(),
            'requisition_printed_by_user_id' => $userId,
        ]);

        return $labelRequest->refresh();
    }

    public function markAttended(LabelRequest $labelRequest, ?int $userId): LabelRequest
    {
        if (! $labelRequest->canMarkAttended()) {
            throw ValidationException::withMessages([
                'status' => 'Solo una requisición impresa puede marcarse como atendida.',
            ]);
        }

        $labelRequest->update([
            'status' => LabelRequest::STATUS_ATTENDED,
            'attended_at' => now(),
            'attended_by_user_id' => $userId,
        ]);

        return $labelRequest->refresh();
    }

    public function complete(LabelRequest $labelRequest, ?int $userId): LabelRequest
    {
        if (! $labelRequest->canMarkDelivered()) {
            throw ValidationException::withMessages([
                'status' => 'Solo una requisición atendida puede marcarse como entregada.',
            ]);
        }

        $labelRequest->update([
            'status' => LabelRequest::STATUS_COMPLETED,
            'delivered_at' => now(),
            'delivered_by_user_id' => $userId,
        ]);

        return $labelRequest->refresh();
    }

    public function cancel(LabelRequest $labelRequest, ?int $userId = null): LabelRequest
    {
        if (! $labelRequest->canCancel()) {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden cancelar requisiciones pendientes o con hoja impresa.',
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

        DB::transaction(function () use ($labelRequest, $userId): void {
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

            $labelRequest->update([
                'status' => LabelRequest::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $userId,
            ]);
        });

        return $labelRequest->refresh();
    }

    private function buildCreatePayload(array $data): array
    {
        $payload = $data;
        $payload['status'] = LabelRequest::STATUS_REQUESTED;

        $jobNumber = (string) ($payload['job_number'] ?? '');

        if ($jobNumber === '') {
            return $payload;
        }

        $job = $this->oracleJobService->findByJobNumber($jobNumber);

        if (! $job) {
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

    private function valueOrOracleFallback(mixed $value, mixed $oracleValue): ?string
    {
        $normalizedValue = strtoupper(trim((string) $value));

        if ($normalizedValue !== '') {
            return $normalizedValue;
        }

        $fallback = strtoupper(trim((string) $oracleValue));

        return $fallback !== '' ? $fallback : null;
    }
}
