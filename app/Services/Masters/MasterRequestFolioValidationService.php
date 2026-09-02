<?php

namespace App\Services\Masters;

use App\Models\MasterModelMapping;
use App\Models\OracleJob;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class MasterRequestFolioValidationService
{
    public function __construct(
        private readonly MasterRequestJobStateService $jobStateService,
    ) {}

    public function validateNewRequest(
        array $data,
        ?OracleJob $assemblyJob,
        ?OracleJob $packagingJob,
    ): void {
        $standardPack = (int) ($data['std_pack_qty'] ?? 0);

        if ($standardPack < 1) {
            throw ValidationException::withMessages([
                'std_pack_qty' => 'El Std pack es obligatorio para validar la cantidad del Job.',
            ]);
        }

        $foliosFrom = (int) ($data['folios_from'] ?? 0);
        $foliosTo = (int) ($data['folios_to'] ?? 0);
        $requestedFolios = collect();

        for ($folio = $foliosFrom; $folio <= $foliosTo; $folio++) {
            $requestedFolios->put($folio, $standardPack);
        }

        if (isset($data['partial_folio'], $data['partial_qty'])) {
            $requestedFolios->put((int) $data['partial_folio'], (int) $data['partial_qty']);
        }

        $this->validateFolioSet(
            data: $data,
            assemblyJob: $assemblyJob,
            packagingJob: $packagingJob,
            requestedFolios: $requestedFolios,
        );
    }

    /**
     * A revision replaces the effective folio set of its original requisition.
     * The current root is therefore excluded before validating the final set.
     *
     * @param  Collection<int, int|null>  $requestedFolios
     */
    public function validateRevision(
        array $data,
        ?OracleJob $assemblyJob,
        ?OracleJob $packagingJob,
        Collection $requestedFolios,
        int $rootRequestId,
    ): void {
        $this->validateFolioSet(
            data: $data,
            assemblyJob: $assemblyJob,
            packagingJob: $packagingJob,
            requestedFolios: $requestedFolios,
            excludeRootRequestId: $rootRequestId,
        );
    }

    /**
     * @param  Collection<int, int|null>  $requestedFolios
     */
    private function validateFolioSet(
        array $data,
        ?OracleJob $assemblyJob,
        ?OracleJob $packagingJob,
        Collection $requestedFolios,
        ?int $excludeRootRequestId = null,
    ): void {
        $foliosWithoutQuantity = $requestedFolios
            ->filter(fn (mixed $quantity): bool => ! is_int($quantity) || $quantity < 1)
            ->keys()
            ->sort()
            ->values();

        if ($foliosWithoutQuantity->isNotEmpty()) {
            throw ValidationException::withMessages([
                'std_pack_qty' => sprintf(
                    'No se puede validar la requisición porque los folios solicitados no tienen una cantidad válida: %s.',
                    $foliosWithoutQuantity->implode(', '),
                ),
            ]);
        }

        $isAssemblyPackaging = ($data['request_type'] ?? null) === MasterModelMapping::TYPE_ASSEMBLY_PACKAGING;
        $contexts = $this->validationContexts($data, $assemblyJob, $packagingJob);
        $availableJobs = $contexts
            ->pluck('job')
            ->filter(fn (mixed $job): bool => $job instanceof OracleJob);
        $lockedJobs = $this->jobStateService->lockJobs($availableJobs);
        $resolvedContexts = collect();
        $registeredPairFolios = collect();
        $errors = [];

        foreach ($contexts as $context) {
            $job = $context['job'] instanceof OracleJob
                ? $lockedJobs->get($context['job']->id)
                : null;

            if (! $job) {
                $errors[$context['field']][] = "El {$context['label']} ya no está disponible en Oracle Jobs.";

                continue;
            }

            $resolvedContexts->put($context['role'], [
                ...$context,
                'job' => $job,
                'job_number' => strtoupper(trim((string) $job->job_number)),
            ]);
        }

        if ($isAssemblyPackaging && $resolvedContexts->has(MasterRequestJobStateService::ROLE_PACKAGING)) {
            $assemblyJobNumber = $resolvedContexts
                ->get(MasterRequestJobStateService::ROLE_ASSEMBLY)['job_number']
                ?? null;
            $packagingJobNumber = $resolvedContexts
                ->get(MasterRequestJobStateService::ROLE_PACKAGING)['job_number'];
            $registeredPairFolios = $this->jobStateService->registeredFoliosForPair(
                assemblyJobNumber: $assemblyJobNumber,
                packagingJobNumber: $packagingJobNumber,
                excludeRootRequestId: $excludeRootRequestId,
            );
            $duplicatePairFolios = $requestedFolios->keys()
                ->intersect($registeredPairFolios)
                ->sort()
                ->values();

            if ($duplicatePairFolios->isNotEmpty()) {
                $duplicateDescription = $duplicatePairFolios->count() === 1
                    ? "registrado el folio {$duplicatePairFolios->first()}"
                    : "registrados los folios {$duplicatePairFolios->implode(', ')}";

                $errors['job_packaging'][] = $assemblyJobNumber
                    ? sprintf(
                        'La combinación Job Ensamble %s / Job Empaque %s ya tiene %s. Todos los folios registrados para esta combinación son: %s.',
                        $assemblyJobNumber,
                        $packagingJobNumber,
                        $duplicateDescription,
                        $registeredPairFolios->implode(', '),
                    )
                    : sprintf(
                        'El Job Empaque %s ya tiene %s en una requisición sin Job Ensamble. Todos los folios registrados para este Job Empaque sin Ensamble son: %s.',
                        $packagingJobNumber,
                        $duplicateDescription,
                        $registeredPairFolios->implode(', '),
                    );
            }
        }

        foreach ($resolvedContexts as $context) {
            /** @var OracleJob $job */
            $job = $context['job'];
            $jobNumber = $context['job_number'];
            $existingReservations = $this->jobStateService->effectiveFolioReservationsForJob(
                jobNumber: $jobNumber,
                role: $context['role'],
                excludeRootRequestId: $excludeRootRequestId,
            );
            $registeredFolios = $existingReservations
                ->pluck('folio_number')
                ->unique()
                ->sort()
                ->values();

            if (! $isAssemblyPackaging) {
                $duplicateFolios = $requestedFolios->keys()
                    ->intersect($registeredFolios)
                    ->sort()
                    ->values();

                if ($duplicateFolios->isNotEmpty()) {
                    $errors[$context['field']][] = sprintf(
                        'El %s %s ya tiene registrados los folios solicitados: %s. Todos los folios registrados para este Job son: %s.',
                        $context['label'],
                        $jobNumber,
                        $duplicateFolios->implode(', '),
                        $registeredFolios->implode(', '),
                    );
                }
            }

            $registeredWithoutQuantity = $existingReservations
                ->filter(fn (array $reservation): bool => $reservation['quantities']->containsStrict(null))
                ->pluck('folio_number')
                ->unique()
                ->sort()
                ->values();
            $registeredQuantityConflicts = $existingReservations
                ->filter(fn (array $reservation): bool => $reservation['quantities']
                    ->filter(fn (?int $quantity): bool => $quantity !== null)
                    ->count() > 1)
                ->pluck('folio_number')
                ->unique()
                ->sort()
                ->values();

            if ($registeredWithoutQuantity->isNotEmpty()) {
                $errors['std_pack_qty'][] = sprintf(
                    'No se puede validar la cantidad del %s %s porque tiene folios sin cantidad registrada: %s.',
                    $context['label'],
                    $jobNumber,
                    $registeredWithoutQuantity->implode(', '),
                );
            }

            if ($registeredQuantityConflicts->isNotEmpty()) {
                $errors['std_pack_qty'][] = sprintf(
                    'El %s %s tiene cantidades diferentes registradas para los folios compartidos: %s.',
                    $context['label'],
                    $jobNumber,
                    $registeredQuantityConflicts->implode(', '),
                );
            }

            $hasQuantityErrors = $registeredWithoutQuantity->isNotEmpty()
                || $registeredQuantityConflicts->isNotEmpty();

            if ($job->job_qty === null || (int) $job->job_qty < 0) {
                $errors[$context['field']][] = "El {$context['label']} {$jobNumber} no tiene una cantidad válida en Oracle Jobs.";

                continue;
            }

            if ($hasQuantityErrors) {
                continue;
            }

            $reservedQuantity = (int) $existingReservations->sum(
                fn (array $reservation): int => (int) $reservation['quantities']->first(),
            );
            $alreadyReservedFolios = $isAssemblyPackaging
                ? $registeredPairFolios
                : $registeredFolios;
            $additionalQuantity = (int) $requestedFolios
                ->reject(fn (int $quantity, int $folio): bool => $alreadyReservedFolios->contains($folio))
                ->sum();
            $resultingQuantity = $reservedQuantity + $additionalQuantity;
            $jobQuantity = (int) $job->job_qty;

            if ($resultingQuantity > $jobQuantity) {
                $excessQuantity = $resultingQuantity - $jobQuantity;
                $pieceLabel = $excessQuantity === 1 ? 'pieza' : 'piezas';

                $errors['std_pack_qty'][] = sprintf(
                    'El %s %s tiene %s piezas; ya hay %s registradas y esta requisición agrega %s piezas nuevas para este Job. Se excede la cantidad por %s %s.',
                    $context['label'],
                    $jobNumber,
                    number_format($jobQuantity),
                    number_format($reservedQuantity),
                    number_format($additionalQuantity),
                    number_format($excessQuantity),
                    $pieceLabel,
                );
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return Collection<int, array{field: string, label: string, role: string, job: OracleJob|null}>
     */
    private function validationContexts(
        array $data,
        ?OracleJob $assemblyJob,
        ?OracleJob $packagingJob,
    ): Collection {
        if (($data['request_type'] ?? null) === MasterModelMapping::TYPE_ASSEMBLY_PACKAGING) {
            return collect([
                ! empty($data['job_assembly']) || $assemblyJob instanceof OracleJob
                    ? [
                        'field' => 'job_assembly',
                        'label' => 'Job Ensamble',
                        'role' => MasterRequestJobStateService::ROLE_ASSEMBLY,
                        'job' => $assemblyJob,
                    ]
                    : null,
                [
                    'field' => 'job_packaging',
                    'label' => 'Job Empaque',
                    'role' => MasterRequestJobStateService::ROLE_PACKAGING,
                    'job' => $packagingJob,
                ],
            ])->filter()->values();
        }

        return collect([
            [
                'field' => 'job_assembly',
                'label' => 'Job Ensamble',
                'role' => MasterRequestJobStateService::ROLE_ASSEMBLY,
                'job' => $assemblyJob,
            ],
        ]);
    }
}
