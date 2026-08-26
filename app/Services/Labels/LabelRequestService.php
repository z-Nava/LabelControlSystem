<?php

namespace App\Services\Labels;

use App\Models\LabelPrintBatch;
use App\Models\LabelRequest;
use App\Models\LabelRequestLpkLabelGroup;
use App\Models\OracleJob;
use App\Models\SerialUnit;
use App\Models\SerialWeek;
use App\Services\Oracle\OracleJobService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelRequestService
{
    public function __construct(
        private readonly OracleJobService $oracleJobService,
        private readonly LabelRequestJobAvailabilityService $availabilityService,
        private readonly LpkJobReservationCalculator $lpkReservationCalculator,
    ) {}

    public function create(array $data): LabelRequest
    {
        return DB::transaction(function () use ($data): LabelRequest {
            $payload = $this->buildCreatePayload($data);

            return LabelRequest::query()->create($payload)->load(['line', 'shift']);
        });
    }

    public function createKiosk(array $data, string $requestKind = LabelRequest::KIND_STANDARD): LabelRequest
    {
        return DB::transaction(function () use ($data, $requestKind): LabelRequest {
            $serialItems = $this->normalizeRequestItems(
                $data['serial_items'] ?? $data['serial_part_numbers'] ?? [],
            );
            $ratingItems = $this->normalizeRequestItems(
                $data['rating_items'] ?? $data['rating_part_numbers'] ?? [],
            );
            $shippingItems = $this->normalizeRequestItems($data['shipping_items'] ?? []);

            unset(
                $data['serial_items'],
                $data['serial_part_numbers'],
                $data['rating_items'],
                $data['rating_part_numbers'],
                $data['shipping_items'],
            );

            $data['request_kind'] = $requestKind === LabelRequest::KIND_LPK
                ? LabelRequest::KIND_LPK
                : LabelRequest::KIND_STANDARD;

            if ($data['request_kind'] !== LabelRequest::KIND_LPK || ! $data['include_shipping']) {
                $shippingItems = collect();
            }

            if ($data['request_kind'] === LabelRequest::KIND_LPK) {
                $data['shipping_part_number'] = null;
                $data['shipping_model'] = null;
            }

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
            $data['serial_part_number'] = $data['include_serial']
                ? data_get($serialItems->first(), 'part_number')
                : null;
            $data['label_part_number'] = $data['include_rating']
                ? data_get($ratingItems->first(), 'part_number')
                : null;
            $data['folio_start'] = null;
            $data['folio_end'] = null;
            $data['po_number'] = $this->valueOrOracleFallback($data['po_number'] ?? null, $job->ttl_cust_po);
            $data['destination'] = $this->valueOrOracleFallback($data['destination'] ?? null, $job->ship_code);

            $labelRequest = LabelRequest::query()->create($this->buildCreatePayload($data));

            if ($data['include_serial']) {
                $labelRequest->serials()->createMany(
                    $serialItems
                        ->map(fn (array $item, int $position) => [
                            'part_number' => $item['part_number'],
                            'model' => $item['model'],
                            'position' => $position + 1,
                        ])
                        ->all(),
                );
            }

            if ($data['include_rating']) {
                $labelRequest->ratings()->createMany(
                    $ratingItems
                        ->map(fn (array $item, int $position) => [
                            'part_number' => $item['part_number'],
                            'model' => $item['model'],
                            'position' => $position + 1,
                        ])
                        ->all(),
                );
            }

            if ($shippingItems->isNotEmpty()) {
                $labelRequest->shippingItems()->createMany(
                    $shippingItems
                        ->map(fn (array $item, int $position) => [
                            'item_reference' => $item['part_number'],
                            'model' => $item['model'],
                            'position' => $position + 1,
                        ])
                        ->all(),
                );
            }

            return $labelRequest->load(['line', 'shift', 'serials', 'ratings', 'shippingItems']);
        }, attempts: 3);
    }

    public function createKioskLpk(array $data): LabelRequest
    {
        return DB::transaction(function () use ($data): LabelRequest {
            $labelGroups = collect($data['lpk_label_groups'] ?? []);
            $shippingGroups = collect($data['lpk_shipping_groups'] ?? []);
            $reservedByJob = $this->lpkReservationCalculator->calculate($labelGroups);
            $jobNumbers = $this->lpkJobNumbers($labelGroups, $shippingGroups);
            $jobs = $this->lockAndValidateLpkJobs($jobNumbers, $reservedByJob);
            $shippingGroups = $shippingGroups->map(function (array $group) use ($jobs): array {
                $firstJobNumber = data_get($group, 'items.0.job_number');
                $firstJob = filled($firstJobNumber) ? $jobs->get($firstJobNumber) : null;

                if (! $firstJob) {
                    return $group;
                }

                $group['po_number'] = $this->valueOrOracleFallback(
                    $group['po_number'] ?? null,
                    $firstJob->ttl_cust_po,
                );
                $group['destination'] = $this->valueOrOracleFallback(
                    $group['destination'] ?? null,
                    $firstJob->ship_code,
                );

                return $group;
            });

            $firstLabelGroup = $labelGroups->first();
            $firstShippingGroup = $shippingGroups->first();
            $firstProductionItem = data_get($firstLabelGroup, 'items.0');
            $firstShippingItem = data_get($firstShippingGroup, 'items.0');
            $representativeJob = data_get($firstProductionItem, 'job_number')
                ?: data_get($firstShippingItem, 'job_number');
            $serialGroup = $labelGroups->firstWhere('label_type', LabelRequestLpkLabelGroup::TYPE_SERIAL);
            $ratingGroup = $labelGroups->firstWhere('label_type', LabelRequestLpkLabelGroup::TYPE_RATING);
            $innerGroup = $labelGroups->firstWhere('label_type', LabelRequestLpkLabelGroup::TYPE_INNER);

            $labelRequest = LabelRequest::query()->create([
                'request_kind' => LabelRequest::KIND_LPK,
                'request_date' => $data['request_date'],
                'week' => $data['week'],
                'line_id' => $data['line_id'],
                'shift_id' => $data['shift_id'],
                'leader_name' => $data['leader_name'],
                'requested_by_name' => $data['requested_by_name'],
                'requested_by_user_id' => $data['requested_by_user_id'],
                'job_number' => $representativeJob,
                'model' => data_get($firstProductionItem, 'model'),
                'quantity_requested' => $reservedByJob->sum(),
                'shipping_quantity' => $shippingGroups->sum('quantity') ?: null,
                'serial_standard' => 'UL',
                'serial_part_number' => data_get($serialGroup, 'part_number'),
                'label_part_number' => data_get($ratingGroup, 'part_number'),
                'inner_part_number' => data_get($innerGroup, 'part_number'),
                'inner_model' => data_get($innerGroup, 'items.0.model'),
                'shipping_part_number' => data_get($firstShippingGroup, 'part_number'),
                'shipping_model' => data_get($firstShippingGroup, 'items.0.model'),
                'po_number' => data_get($firstShippingGroup, 'po_number'),
                'destination' => data_get($firstShippingGroup, 'destination'),
                'folio_start' => null,
                'folio_end' => null,
                'include_serial' => $serialGroup !== null,
                'include_rating' => $ratingGroup !== null,
                'include_inner' => $innerGroup !== null,
                'include_shipping' => $shippingGroups->isNotEmpty(),
                'status' => LabelRequest::STATUS_REQUESTED,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($labelGroups as $groupPosition => $groupData) {
                $group = $labelRequest->lpkLabelGroups()->create([
                    'label_type' => $groupData['label_type'],
                    'part_number' => $groupData['part_number'],
                    'position' => $groupPosition + 1,
                ]);

                $group->items()->createMany(
                    collect($groupData['items'])
                        ->map(fn (array $item, int $itemPosition): array => [
                            'job_number' => $item['job_number'],
                            'model' => $item['model'],
                            'quantity' => (int) $item['quantity'],
                            'position' => $itemPosition + 1,
                        ])
                        ->all(),
                );
            }

            foreach ($shippingGroups as $groupPosition => $groupData) {
                $group = $labelRequest->lpkShippingGroups()->create([
                    'part_number' => $groupData['part_number'],
                    'quantity' => (int) $groupData['quantity'],
                    'po_number' => $groupData['po_number'],
                    'destination' => $groupData['destination'],
                    'position' => $groupPosition + 1,
                ]);

                $group->items()->createMany(
                    collect($groupData['items'])
                        ->map(fn (array $item, int $itemPosition): array => [
                            'job_number' => $item['job_number'],
                            'model' => $item['model'],
                            'position' => $itemPosition + 1,
                        ])
                        ->all(),
                );
            }

            return $labelRequest->load([
                'line',
                'shift',
                'lpkLabelGroups.items',
                'lpkShippingGroups.items',
            ]);
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

    /**
     * @param  Collection<int, array<string, mixed>>  $labelGroups
     * @param  Collection<int, array<string, mixed>>  $shippingGroups
     * @return Collection<int, string>
     */
    private function lpkJobNumbers(Collection $labelGroups, Collection $shippingGroups): Collection
    {
        return $labelGroups
            ->concat($shippingGroups)
            ->flatMap(fn (array $group): array => $group['items'])
            ->pluck('job_number')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @param  Collection<int, string>  $jobNumbers
     * @param  Collection<string, int>  $reservedByJob
     * @return Collection<string, OracleJob>
     */
    private function lockAndValidateLpkJobs(Collection $jobNumbers, Collection $reservedByJob): Collection
    {
        if ($jobNumbers->isEmpty() || $jobNumbers->count() > 200) {
            throw ValidationException::withMessages([
                'lpk_label_groups' => 'La requisición debe incluir entre 1 y 200 Jobs distintos.',
            ]);
        }

        $jobs = OracleJob::query()
            ->where(function ($query) use ($jobNumbers): void {
                foreach ($jobNumbers as $jobNumber) {
                    $query->orWhereRaw('UPPER(TRIM(job_number)) = ?', [$jobNumber]);
                }
            })
            ->orderBy('job_number')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (OracleJob $job): string => strtoupper(trim((string) $job->job_number)));

        foreach ($jobNumbers as $jobNumber) {
            /** @var OracleJob|null $job */
            $job = $jobs->get($jobNumber);

            if (! $job) {
                throw ValidationException::withMessages([
                    'lpk_label_groups' => "El Job {$jobNumber} no existe en Oracle Jobs.",
                ]);
            }

            if (! $this->oracleJobService->isPackagingJob($job)) {
                throw ValidationException::withMessages([
                    'lpk_label_groups' => $this->oracleJobService->classificationValidationMessage('packaging'),
                ]);
            }

            $requestedQuantity = $reservedByJob->get($jobNumber);

            if ($requestedQuantity === null) {
                continue;
            }

            $availability = $this->availabilityService->calculate($job);

            if ($requestedQuantity > $availability['available_quantity']) {
                throw ValidationException::withMessages([
                    'lpk_label_groups' => "La cantidad solicitada para el Job {$jobNumber} supera su disponibilidad actual ({$availability['available_quantity']}).",
                ]);
            }
        }

        return $jobs;
    }

    /**
     * @return Collection<int, array{part_number: string, model: ?string}>
     */
    private function normalizeRequestItems(mixed $items): Collection
    {
        return collect(is_array($items) ? $items : [$items])
            ->map(fn ($item) => is_array($item)
                ? [
                    'part_number' => strtoupper(trim((string) ($item['part_number'] ?? ''))),
                    'model' => $this->nullableUppercase($item['model'] ?? null),
                ]
                : [
                    'part_number' => strtoupper(trim((string) $item)),
                    'model' => null,
                ])
            ->filter(fn (array $item) => $item['part_number'] !== '')
            ->unique('part_number')
            ->values();
    }

    private function nullableUppercase(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }
}
