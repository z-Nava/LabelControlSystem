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
                $assemblyLabel = $assemblyJobNumber ?: 'SIN JOB ENSAMBLE';
                $errors['job_packaging'][] = sprintf(
                    'La combinación Job Ensamble %s / Job Empaque %s ya tiene registrados los folios solicitados: %s. Todos los folios registrados para esta combinación son: %s.',
                    $assemblyLabel,
                    $packagingJobNumber,
                    $duplicatePairFolios->implode(', '),
                    $registeredPairFolios->implode(', '),
                );
            }
        }

        foreach ($resolvedContexts as $context) {
            /** @var OracleJob $job */
            $job = $context['job'];
            $jobNumber = $context['job_number'];
            $existingQuantities = $this->jobStateService->effectiveFolioQuantitiesForJob(
                jobNumber: $jobNumber,
                role: $context['role'],
                excludeRootRequestId: $excludeRootRequestId,
            );
            $registeredFolios = $existingQuantities->keys()->sort()->values();

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

            $registeredWithoutQuantity = $existingQuantities
                ->filter(fn (Collection $quantities): bool => $quantities->containsStrict(null))
                ->keys()
                ->sort()
                ->values();
            $registeredQuantityConflicts = $existingQuantities
                ->filter(fn (Collection $quantities): bool => $quantities
                    ->filter(fn (?int $quantity): bool => $quantity !== null)
                    ->count() > 1)
                ->keys()
                ->sort()
                ->values();
            $requestedQuantityConflicts = $requestedFolios
                ->filter(function (int $requestedQuantity, int $folio) use ($existingQuantities): bool {
                    /** @var Collection<int, int|null>|null $registeredQuantities */
                    $registeredQuantities = $existingQuantities->get($folio);

                    if (! $registeredQuantities instanceof Collection) {
                        return false;
                    }

                    $knownQuantities = $registeredQuantities
                        ->filter(fn (?int $quantity): bool => $quantity !== null);

                    return $knownQuantities->count() === 1
                        && $knownQuantities->first() !== $requestedQuantity;
                })
                ->map(function (int $requestedQuantity, int $folio) use ($existingQuantities): string {
                    $registeredQuantity = $existingQuantities->get($folio)
                        ->filter(fn (?int $quantity): bool => $quantity !== null)
                        ->first();

                    return sprintf(
                        '%s (registrada: %s, solicitada: %s)',
                        $folio,
                        number_format((int) $registeredQuantity),
                        number_format($requestedQuantity),
                    );
                })
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

            if ($requestedQuantityConflicts->isNotEmpty()) {
                $errors['std_pack_qty'][] = sprintf(
                    'La cantidad solicitada no coincide con la ya registrada para folios compartidos del %s %s: %s.',
                    $context['label'],
                    $jobNumber,
                    $requestedQuantityConflicts->implode('; '),
                );
            }

            $hasQuantityErrors = $registeredWithoutQuantity->isNotEmpty()
                || $registeredQuantityConflicts->isNotEmpty()
                || $requestedQuantityConflicts->isNotEmpty();

            if ($job->job_qty === null || (int) $job->job_qty < 0) {
                $errors[$context['field']][] = "El {$context['label']} {$jobNumber} no tiene una cantidad válida en Oracle Jobs.";

                continue;
            }

            if ($hasQuantityErrors) {
                continue;
            }

            $reservedQuantity = (int) $existingQuantities->sum(
                fn (Collection $quantities): int => (int) $quantities->first(),
            );
            $additionalQuantity = (int) $requestedFolios
                ->reject(fn (int $quantity, int $folio): bool => $existingQuantities->has($folio))
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
