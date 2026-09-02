<?php

namespace App\Services\Masters;

use App\Models\MasterModelMapping;
use App\Models\MasterRequest;
use App\Models\MasterRequestFolio;
use App\Models\OracleJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MasterRequestJobStateService
{
    public const ROLE_ASSEMBLY = 'assembly';

    public const ROLE_PACKAGING = 'packaging';

    /**
     * @param  Collection<int, OracleJob>  $jobs
     * @return Collection<int, OracleJob>
     */
    public function lockJobs(Collection $jobs): Collection
    {
        return OracleJob::query()
            ->whereKey($jobs->pluck('id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * Returns one effective quantity per folio. Rework revisions may repeat a
     * folio, so the most recent revision must not reserve the quantity twice.
     *
     * @return Collection<int, int|null>
     */
    public function effectiveFoliosForJob(
        string $jobNumber,
        string $role,
        ?int $excludeRootRequestId = null,
    ): Collection {
        return $this->effectiveFolioQuantitiesForJob(
            $jobNumber,
            $role,
            $excludeRootRequestId,
        )->map(fn (Collection $quantities): ?int => $quantities->count() === 1
            ? $quantities->first()
            : null);
    }

    /**
     * Returns every distinct effective quantity registered for each folio.
     * Multiple Assy/Packaging pairs may share a Job and the same folio.
     *
     * @return Collection<int, Collection<int, int|null>>
     */
    public function effectiveFolioQuantitiesForJob(
        string $jobNumber,
        string $role,
        ?int $excludeRootRequestId = null,
    ): Collection {
        return $this->effectiveFolioReservationsForJob(
            $jobNumber,
            $role,
            $excludeRootRequestId,
        )
            ->groupBy('folio_number')
            ->map(fn (Collection $reservations): Collection => $reservations
                ->flatMap(fn (array $reservation): Collection => $reservation['quantities'])
                ->uniqueStrict()
                ->values())
            ->sortKeys();
    }

    /**
     * A folio belongs to an exact Assy/Packaging pair. Different pairs may use
     * the same folio number with different quantities, and each reservation
     * must count independently against every Job involved.
     *
     * @return Collection<int, array{folio_number: int, quantities: Collection<int, int|null>}>
     */
    public function effectiveFolioReservationsForJob(
        string $jobNumber,
        string $role,
        ?int $excludeRootRequestId = null,
    ): Collection {
        $jobColumn = match ($role) {
            self::ROLE_ASSEMBLY => 'job_assembly',
            self::ROLE_PACKAGING => 'job_packaging',
            default => throw new \InvalidArgumentException("Invalid Master Job role [{$role}]."),
        };
        $normalizedJobNumber = strtoupper(trim($jobNumber));
        $folios = $this->effectiveFolioRows($excludeRootRequestId)
            ->whereRaw("UPPER(TRIM(master_requests.{$jobColumn})) = ?", [$normalizedJobNumber])
            ->orderByDesc('master_request_folios.id')
            ->get([
                'master_requests.request_type',
                'master_requests.job_assembly',
                'master_requests.job_packaging',
                'master_request_folios.folio_number',
                'master_request_folios.qty_for_folio',
            ]);

        return $folios
            ->groupBy(fn (MasterRequestFolio $folio): string => $this->reservationKey($folio, $role))
            ->map(function (Collection $group): array {
                /** @var MasterRequestFolio $folio */
                $folio = $group->first();

                return [
                    'folio_number' => (int) $folio->folio_number,
                    'quantities' => $group
                        ->map(fn (MasterRequestFolio $row): ?int => $row->qty_for_folio !== null
                            ? (int) $row->qty_for_folio
                            : null)
                        ->uniqueStrict()
                        ->values(),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    public function registeredFoliosForPair(
        ?string $assemblyJobNumber,
        string $packagingJobNumber,
        ?int $excludeRootRequestId = null,
    ): Collection {
        $assemblyJobNumber = strtoupper(trim((string) $assemblyJobNumber));
        $packagingJobNumber = strtoupper(trim($packagingJobNumber));
        $folios = $this->effectiveFolioRows($excludeRootRequestId)
            ->where('master_requests.request_type', MasterModelMapping::TYPE_ASSEMBLY_PACKAGING)
            ->whereRaw(
                "UPPER(TRIM(COALESCE(master_requests.job_assembly, ''))) = ?",
                [$assemblyJobNumber],
            )
            ->whereRaw('UPPER(TRIM(master_requests.job_packaging)) = ?', [$packagingJobNumber])
            ->pluck('master_request_folios.folio_number');

        return $folios
            ->map(fn (mixed $folio): int => (int) $folio)
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @return array{
     *     registered_folios: array<int, int>,
     *     reserved_quantity: int|null,
     *     available_quantity: int|null,
     *     folios_without_quantity: array<int, int>,
     *     folio_quantities: array<int, int|null>,
     *     quantity_conflicts: array<int, int>
     * }
     */
    public function summaryForJob(OracleJob $job, string $role): array
    {
        $reservations = $this->effectiveFolioReservationsForJob(
            (string) $job->job_number,
            $role,
        );
        $registeredFolios = $reservations
            ->pluck('folio_number')
            ->unique()
            ->sort()
            ->values();
        $foliosWithoutQuantity = $reservations
            ->filter(fn (array $reservation): bool => $reservation['quantities']->containsStrict(null))
            ->pluck('folio_number')
            ->unique()
            ->sort()
            ->values();
        $quantityConflicts = $reservations
            ->filter(fn (array $reservation): bool => $reservation['quantities']
                ->filter(fn (?int $quantity): bool => $quantity !== null)
                ->count() > 1)
            ->pluck('folio_number')
            ->unique()
            ->sort()
            ->values();
        $folioQuantities = $reservations
            ->groupBy('folio_number')
            ->map(function (Collection $group): ?int {
                $quantities = $group
                    ->flatMap(fn (array $reservation): Collection => $reservation['quantities'])
                    ->uniqueStrict()
                    ->values();

                return $quantities->count() === 1 ? $quantities->first() : null;
            })
            ->sortKeys();
        $hasCompleteQuantities = $foliosWithoutQuantity->isEmpty() && $quantityConflicts->isEmpty();
        $hasValidJobQuantity = $job->job_qty !== null && (int) $job->job_qty >= 0;
        $reservedQuantity = $hasCompleteQuantities
            ? (int) $reservations->sum(
                fn (array $reservation): int => (int) $reservation['quantities']->first(),
            )
            : null;
        $availableQuantity = $hasCompleteQuantities && $hasValidJobQuantity
            ? max(0, (int) $job->job_qty - $reservedQuantity)
            : null;

        return [
            'registered_folios' => $registeredFolios->all(),
            'reserved_quantity' => $reservedQuantity,
            'available_quantity' => $availableQuantity,
            'folios_without_quantity' => $foliosWithoutQuantity->all(),
            'folio_quantities' => $folioQuantities->all(),
            'quantity_conflicts' => $quantityConflicts->all(),
        ];
    }

    /**
     * @return array{
     *     assembly_job: string|null,
     *     packaging_job: string,
     *     registered_folios: array<int, int>
     * }
     */
    public function summaryForPair(?string $assemblyJobNumber, string $packagingJobNumber): array
    {
        $assemblyJobNumber = $this->normalizeNullable($assemblyJobNumber);
        $packagingJobNumber = strtoupper(trim($packagingJobNumber));

        return [
            'assembly_job' => $assemblyJobNumber,
            'packaging_job' => $packagingJobNumber,
            'registered_folios' => $this->registeredFoliosForPair(
                $assemblyJobNumber,
                $packagingJobNumber,
            )->all(),
        ];
    }

    private function effectiveFolioRows(?int $excludeRootRequestId): Builder
    {
        $effectiveRequestIds = MasterRequest::query()
            ->selectRaw('MAX(id)')
            ->where('status', '!=', MasterRequest::STATUS_CANCELLED)
            ->when($excludeRootRequestId !== null, function ($query) use ($excludeRootRequestId): void {
                $query->whereRaw(
                    'COALESCE(parent_master_request_id, id) <> ?',
                    [$excludeRootRequestId],
                );
            })
            ->groupByRaw('COALESCE(parent_master_request_id, id)');

        return MasterRequestFolio::query()
            ->join('master_requests', 'master_requests.id', '=', 'master_request_folios.master_request_id')
            ->whereIn('master_requests.id', $effectiveRequestIds);
    }

    private function reservationKey(MasterRequestFolio $folio, string $role): string
    {
        $folioNumber = (int) $folio->folio_number;

        if ($folio->request_type !== MasterModelMapping::TYPE_ASSEMBLY_PACKAGING) {
            return "{$role}|single|{$folioNumber}";
        }

        return implode('|', [
            $role,
            $this->normalizeNullable($folio->job_assembly) ?? '',
            $this->normalizeNullable($folio->job_packaging) ?? '',
            $folioNumber,
        ]);
    }

    private function normalizeNullable(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }
}
