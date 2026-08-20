<?php

namespace App\Services\Masters;

use App\Models\MasterRequest;
use App\Models\MasterRequestFolio;
use App\Models\OracleJob;
use Illuminate\Support\Collection;

class MasterRequestJobStateService
{
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
    public function effectiveFoliosForJob(string $jobNumber): Collection
    {
        $folios = MasterRequestFolio::query()
            ->join('master_requests', 'master_requests.id', '=', 'master_request_folios.master_request_id')
            ->where('master_requests.status', '!=', MasterRequest::STATUS_CANCELLED)
            ->where(function ($query) use ($jobNumber): void {
                $query->whereRaw('UPPER(TRIM(master_requests.job_assembly)) = ?', [$jobNumber])
                    ->orWhereRaw('UPPER(TRIM(master_requests.job_packaging)) = ?', [$jobNumber]);
            })
            ->orderByDesc('master_requests.revision_number')
            ->orderByDesc('master_request_folios.id')
            ->get([
                'master_request_folios.folio_number',
                'master_request_folios.qty_for_folio',
            ]);

        return $folios
            ->unique(fn (MasterRequestFolio $folio): int => (int) $folio->folio_number)
            ->mapWithKeys(fn (MasterRequestFolio $folio): array => [
                (int) $folio->folio_number => $folio->qty_for_folio !== null
                    ? (int) $folio->qty_for_folio
                    : null,
            ]);
    }

    /**
     * @return array{
     *     registered_folios: array<int, int>,
     *     reserved_quantity: int|null,
     *     available_quantity: int|null,
     *     folios_without_quantity: array<int, int>
     * }
     */
    public function summaryForJob(OracleJob $job): array
    {
        $folios = $this->effectiveFoliosForJob(
            strtoupper(trim((string) $job->job_number))
        );
        $registeredFolios = $folios->keys()
            ->map(fn (mixed $folio): int => (int) $folio)
            ->sort()
            ->values();
        $foliosWithoutQuantity = $folios
            ->filter(fn (?int $quantity): bool => $quantity === null)
            ->keys()
            ->map(fn (mixed $folio): int => (int) $folio)
            ->sort()
            ->values();
        $hasCompleteQuantities = $foliosWithoutQuantity->isEmpty();
        $hasValidJobQuantity = $job->job_qty !== null && (int) $job->job_qty >= 0;
        $reservedQuantity = $hasCompleteQuantities ? (int) $folios->sum() : null;
        $availableQuantity = $hasCompleteQuantities && $hasValidJobQuantity
            ? max(0, (int) $job->job_qty - $reservedQuantity)
            : null;

        return [
            'registered_folios' => $registeredFolios->all(),
            'reserved_quantity' => $reservedQuantity,
            'available_quantity' => $availableQuantity,
            'folios_without_quantity' => $foliosWithoutQuantity->all(),
        ];
    }
}
