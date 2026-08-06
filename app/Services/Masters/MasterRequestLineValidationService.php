<?php

namespace App\Services\Masters;

use App\Models\MasterModelMapping;
use App\Models\OracleJob;
use App\Models\ProductionLine;

final class MasterRequestLineValidationService
{
    public function validationError(
        ProductionLine $selectedLine,
        string $requestType,
        ?OracleJob $assemblyJob,
        ?OracleJob $packagingJob,
    ): ?string {
        $jobs = $this->relevantJobs($requestType, $assemblyJob, $packagingJob);
        $selectedLineCode = $this->normalizeLineCode($selectedLine->code);
        $oracleLines = [];

        foreach ($jobs as $role => $job) {
            $oracleLine = $this->normalizeLineCode($job->line);

            if ($oracleLine === '') {
                continue;
            }

            $oracleLines[] = [
                'role' => $role,
                'job_number' => (string) $job->job_number,
                'line' => $oracleLine,
            ];
        }

        foreach ($oracleLines as $oracleJob) {
            if ($oracleJob['line'] === $selectedLineCode) {
                return null;
            }
        }

        if ($oracleLines === []) {
            return 'No se puede validar la línea seleccionada porque los Jobs aplicables no tienen una línea de producción registrada en Oracle.';
        }

        $expectedLines = array_map(
            fn (array $oracleJob): string => sprintf(
                'Job %s %s (%s)',
                $oracleJob['role'],
                $oracleJob['job_number'],
                $oracleJob['line'],
            ),
            $oracleLines,
        );

        return sprintf(
            'La línea seleccionada %s no coincide con ninguna de las líneas aplicables registradas en Oracle: %s.',
            $selectedLineCode,
            implode(' ni ', $expectedLines),
        );
    }

    /**
     * @return array<string, OracleJob>
     */
    private function relevantJobs(
        string $requestType,
        ?OracleJob $assemblyJob,
        ?OracleJob $packagingJob,
    ): array {
        if ($requestType !== MasterModelMapping::TYPE_ASSEMBLY_PACKAGING) {
            return $assemblyJob ? ['Ensamble' => $assemblyJob] : [];
        }

        return array_filter([
            'Ensamble' => $assemblyJob,
            'Empaque' => $packagingJob,
        ]);
    }

    private function normalizeLineCode(mixed $lineCode): string
    {
        return strtoupper(trim((string) $lineCode));
    }
}
