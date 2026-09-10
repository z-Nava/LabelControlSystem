<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use App\Models\LabelRequestLpkLabelGroup;
use App\Models\OracleJob;
use App\Services\Catalogs\MasterModelMappingService;
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
        private readonly MasterModelMappingService $masterModelMappingService,
    ) {}

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

            if (($data['folio_mode'] ?? 'new') !== 'reprint_originals' && $requestedQuantity > $availability['available_quantity']) {
                throw ValidationException::withMessages([
                    'quantity_requested' => "La cantidad solicitada supera la disponibilidad actual del Job ({$availability['available_quantity']}).",
                ]);
            }

            $data['job_number'] = $jobNumber;
            $data['serial_standard'] = null;
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

            $mappedModel = $this->masterModelMappingService
                ->resolveAssemblyPackagingModel($job->assembly);

            if ($mappedModel) {
                $data['model'] = $mappedModel;
                $data['inner_model'] = $data['include_inner'] ? $mappedModel : null;
                $data['shipping_model'] = $data['include_shipping'] ? $mappedModel : null;
                $serialItems = $this->applyModelToRequestItems($serialItems, $mappedModel);
                $ratingItems = $this->applyModelToRequestItems($ratingItems, $mappedModel);
                $shippingItems = $this->applyModelToRequestItems($shippingItems, $mappedModel);
            }

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
            $jobs = $this->lockAndValidateLpkJobs($jobNumbers, ($data['folio_mode'] ?? 'new') === 'reprint_originals' ? collect() : $reservedByJob);
            $mappedModelsByAssembly = $this->masterModelMappingService
                ->resolveAssemblyPackagingModels($jobs->pluck('assembly'));
            $labelGroups = $this->applyMappedModelsToLpkGroups(
                $labelGroups,
                $jobs,
                $mappedModelsByAssembly,
            );
            $shippingGroups = $this->applyMappedModelsToLpkGroups(
                $shippingGroups,
                $jobs,
                $mappedModelsByAssembly,
            );
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
                'folio_mode' => $data['folio_mode'] ?? 'new',
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
                'serial_standard' => null,
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

        return [
            ...$payload,
            'mapped_model' => $this->masterModelMappingService
                ->resolveAssemblyPackagingModel($job->assembly),
            ...$this->availabilityService->calculate($job),
        ];
    }

    public function startPreparation(LabelRequest $labelRequest, ?int $userId): LabelRequest
    {
        if (! $labelRequest->canStartPreparation()) {
            throw ValidationException::withMessages([
                'status' => 'Solo una requisición pendiente puede iniciar preparación.',
            ]);
        }

        throw ValidationException::withMessages([
            'status' => 'Revisa la requisición, calcula los folios y libera el trabajo antes de iniciar preparación.',
        ]);
    }

    public function markReadyForDelivery(LabelRequest $labelRequest, ?int $userId): LabelRequest
    {
        if (! $labelRequest->canMarkReadyForDelivery()) {
            throw ValidationException::withMessages([
                'status' => 'Solo una requisición en preparación puede marcarse como lista para entregar.',
            ]);
        }

        throw ValidationException::withMessages([
            'status' => 'Confirma cada tarea de impresión. El cierre genera automáticamente el concentrado JOB.',
        ]);
    }

    public function confirmDelivery(LabelRequest $labelRequest, ?int $userId): LabelRequest
    {
        if (! $labelRequest->canConfirmDelivery()) {
            throw ValidationException::withMessages([
                'status' => 'Solo una requisición lista para entregar puede confirmarse como entregada.',
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
                'status' => 'Solo se pueden cancelar requisiciones pendientes o en preparación.',
            ]);
        }

        return DB::transaction(function () use ($labelRequest, $userId) {
            $locked = LabelRequest::whereKey($labelRequest->id)->lockForUpdate()->firstOrFail();
            if (! $locked->canCancel()) {
                throw ValidationException::withMessages(['status' => 'Esta requisición ya está cerrada.']);
            }
            $locked->update(['status' => LabelRequest::STATUS_CANCELLED, 'cancelled_at' => now(), 'cancelled_by_user_id' => $userId]);
            if ($locked->released_at) {
                $locked->workTasks()->where('status', 'pending')->update(['status' => 'cancelled']);
                DB::table('serial_ranges')->where('label_request_id', $locked->id)->update(['status' => 'cancelled', 'updated_at' => now()]);
                DB::table('label_administration_events')->insert([
                    'label_request_id' => $locked->id, 'user_id' => $userId, 'action' => 'cancelled',
                    'details' => json_encode(['folios_retained' => true]), 'created_at' => now(),
                ]);
            }

            return $locked;
        }, 3);
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

    /**
     * @param  Collection<int, array{part_number: string, model: ?string}>  $items
     * @return Collection<int, array{part_number: string, model: string}>
     */
    private function applyModelToRequestItems(Collection $items, string $model): Collection
    {
        return $items->map(fn (array $item): array => [
            ...$item,
            'model' => $model,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $groups
     * @param  Collection<string, OracleJob>  $jobs
     * @param  array<string, string>  $mappedModelsByAssembly
     * @return Collection<int, array<string, mixed>>
     */
    private function applyMappedModelsToLpkGroups(
        Collection $groups,
        Collection $jobs,
        array $mappedModelsByAssembly,
    ): Collection {
        return $groups->map(function (array $group) use ($jobs, $mappedModelsByAssembly): array {
            $group['items'] = collect($group['items'] ?? [])
                ->map(function (array $item) use ($jobs, $mappedModelsByAssembly): array {
                    $jobNumber = strtoupper(trim((string) ($item['job_number'] ?? '')));
                    $job = $jobs->get($jobNumber);
                    $assembly = strtoupper(trim((string) ($job?->assembly ?? '')));
                    $mappedModel = $mappedModelsByAssembly[$assembly] ?? null;

                    if ($mappedModel) {
                        $item['model'] = $mappedModel;
                    }

                    return $item;
                })
                ->all();

            return $group;
        });
    }

    private function nullableUppercase(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }
}
