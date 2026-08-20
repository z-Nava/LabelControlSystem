<?php

namespace App\Services\Masters;

use App\Models\OracleJob;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MasterRequestLabelRoomValidationService
{
    public function __construct(
        private readonly MasterRequestJobStateService $jobStateService,
    ) {}

    public function validate(
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

        $foliosFrom = (int) $data['folios_from'];
        $foliosTo = (int) $data['folios_to'];
        $partialFolio = isset($data['partial_folio']) ? (int) $data['partial_folio'] : null;
        $partialQuantity = isset($data['partial_qty']) ? (int) $data['partial_qty'] : 0;
        $requestedQuantity = (($foliosTo - $foliosFrom + 1) * $standardPack) + $partialQuantity;
        $jobs = $this->jobs($assemblyJob, $packagingJob);
        $lockedJobs = $this->jobStateService->lockJobs($jobs->pluck('job'));
        $errors = [];

        foreach ($jobs as $jobContext) {
            /** @var OracleJob|null $job */
            $job = $lockedJobs->get($jobContext['job']->id);

            if (! $job) {
                $errors[$jobContext['field']][] = "El {$jobContext['label']} ya no está disponible en Oracle Jobs.";

                continue;
            }

            $jobNumber = $this->normalizeJobNumber($job->job_number);
            $existingFolios = $this->jobStateService->effectiveFoliosForJob($jobNumber);
            $registeredFolios = $existingFolios
                ->keys()
                ->map(fn (mixed $folio): int => (int) $folio)
                ->sort()
                ->values();
            $duplicateFolios = $existingFolios
                ->keys()
                ->map(fn (mixed $folio): int => (int) $folio)
                ->filter(fn (int $folio): bool => ($folio >= $foliosFrom && $folio <= $foliosTo)
                    || ($partialFolio !== null && $folio === $partialFolio)
                )
                ->sort()
                ->values();

            if ($duplicateFolios->isNotEmpty()) {
                $errors[$jobContext['field']][] = sprintf(
                    'El %s %s ya tiene registrados los folios solicitados: %s. Todos los folios registrados para este Job son: %s.',
                    $jobContext['label'],
                    $jobNumber,
                    $duplicateFolios->implode(', '),
                    $registeredFolios->implode(', '),
                );
            }

            $foliosWithoutQuantity = $existingFolios
                ->filter(fn (?int $quantity): bool => $quantity === null)
                ->keys()
                ->map(fn (mixed $folio): int => (int) $folio)
                ->sort()
                ->values();

            if ($foliosWithoutQuantity->isNotEmpty()) {
                $errors['std_pack_qty'][] = sprintf(
                    'No se puede validar la cantidad del %s %s porque tiene folios sin cantidad registrada: %s.',
                    $jobContext['label'],
                    $jobNumber,
                    $foliosWithoutQuantity->implode(', '),
                );

                continue;
            }

            if ($job->job_qty === null || (int) $job->job_qty < 0) {
                $errors[$jobContext['field']][] = "El {$jobContext['label']} {$jobNumber} no tiene una cantidad válida en Oracle Jobs.";

                continue;
            }

            $jobQuantity = (int) $job->job_qty;
            $reservedQuantity = $existingFolios->sum();
            $resultingQuantity = $reservedQuantity + $requestedQuantity;

            if ($resultingQuantity > $jobQuantity) {
                $excessQuantity = $resultingQuantity - $jobQuantity;
                $pieceLabel = $excessQuantity === 1 ? 'pieza' : 'piezas';

                $errors['std_pack_qty'][] = sprintf(
                    'El %s %s tiene %s piezas; ya hay %s registradas y esta requisición solicita %s. Se excede la cantidad del Job por %s %s.',
                    $jobContext['label'],
                    $jobNumber,
                    number_format($jobQuantity),
                    number_format($reservedQuantity),
                    number_format($requestedQuantity),
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
     * @return Collection<int, array{field: string, label: string, job: OracleJob}>
     */
    private function jobs(?OracleJob $assemblyJob, ?OracleJob $packagingJob): Collection
    {
        return collect([
            [
                'field' => 'job_assembly',
                'label' => 'Job Ensamble',
                'job' => $assemblyJob,
            ],
            [
                'field' => 'job_packaging',
                'label' => 'Job Empaque',
                'job' => $packagingJob,
            ],
        ])->filter(fn (array $context): bool => $context['job'] instanceof OracleJob)
            ->unique(fn (array $context): string => $this->normalizeJobNumber($context['job']->job_number))
            ->values();
    }

    private function normalizeJobNumber(mixed $jobNumber): string
    {
        return strtoupper(trim((string) $jobNumber));
    }
}
