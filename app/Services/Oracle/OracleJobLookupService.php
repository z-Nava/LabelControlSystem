<?php

namespace App\Services\Oracle;

use App\Models\OracleJob;

class OracleJobLookupService
{
    /**
     * @var array<string,OracleJob|null>
     */
    private array $jobCache = [];

    public function findByJobNumber(string $jobNumber): ?OracleJob
    {
        $normalizedJobNumber = strtoupper(trim($jobNumber));

        if ($normalizedJobNumber === '') {
            return null;
        }

        if (array_key_exists($normalizedJobNumber, $this->jobCache)) {
            return $this->jobCache[$normalizedJobNumber];
        }

        return $this->jobCache[$normalizedJobNumber] = OracleJob::query()
            ->whereRaw('UPPER(TRIM(job_number)) = ?', [$normalizedJobNumber])
            ->first();
    }

    public function isAssemblyJob(OracleJob $job): bool
    {
        $assembly = strtoupper(trim((string) $job->assembly));
        $line = strtoupper(trim((string) $job->line));

        return str_starts_with($assembly, '103')
            || str_starts_with($assembly, '130')
            || str_starts_with($line, 'MEXMI')
            || str_starts_with($line, 'MXM');
    }

    public function isPackagingJob(OracleJob $job): bool
    {
        $assembly = strtoupper(trim((string) $job->assembly));

        return str_starts_with($assembly, '018')
            || str_starts_with($assembly, '055')
            || str_starts_with($assembly, '001');
    }

    public function buildLookupPayload(string $jobNumber): array
    {
        $normalizedJobNumber = strtoupper(trim($jobNumber));
        $job = $this->findByJobNumber($normalizedJobNumber);

        if (!$job) {
            return [
                'found' => false,
                'job_number' => $normalizedJobNumber,
            ];
        }

        return [
            'found' => true,
            'job_number' => $job->job_number,
            'line' => $job->line,
            'assembly' => $job->assembly,
            'part_description' => $job->part_description,
            'ttl_cust_po' => $job->ttl_cust_po,
            'ship_code' => $job->ship_code,
            'bom_revision' => $job->bom_revision,
            'valid_for_assembly' => $this->isAssemblyJob($job),
            'valid_for_packaging' => $this->isPackagingJob($job),
        ];
    }
}
