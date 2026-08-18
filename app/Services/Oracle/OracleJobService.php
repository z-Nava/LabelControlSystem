<?php

namespace App\Services\Oracle;

use App\Imports\OracleJobsImport;
use App\Models\MasterAssemblyClassificationRule;
use App\Models\OracleJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class OracleJobService
{
    /**
     * @var array<string,OracleJob|null>
     */
    private array $jobCache = [];

    /**
     * @var array<int, array{match_field: string, prefix: string, allows_assembly: bool, allows_packaging: bool}>|null
     */
    private ?array $classificationRules = null;

    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $q = $filters['q'] ?? null;
        $line = $filters['line'] ?? null;
        $status = $filters['job_status'] ?? null;

        return OracleJob::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('job_number', 'like', "%{$q}%")
                        ->orWhere('assembly', 'like', "%{$q}%")
                        ->orWhere('part_description', 'like', "%{$q}%")
                        ->orWhere('ttl_cust_po', 'like', "%{$q}%");
                });
            })
            ->when($line, fn ($query) => $query->where('line', $line))
            ->when($status, fn ($query) => $query->where('job_status', $status))
            ->orderByDesc('last_update_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function importFromExcel(UploadedFile $file): array
    {
        // Leemos el excel con heading row
        $rows = Excel::toArray(new OracleJobsImport, $file)[0] ?? [];

        $sourceName = $file->getClientOriginalName();
        $now = now();

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $sourceName, $now, &$inserted, &$updated, &$skipped) {
            foreach ($rows as $row) {
                $data = OracleJobsImport::normalizeRow($row);

                if (empty($data['job_number'])) {
                    $skipped++;

                    continue;
                }

                $data['source_file_name'] = $sourceName;
                $data['imported_at'] = $now;

                $existing = OracleJob::where('job_number', $data['job_number'])->first();

                if (! $existing) {
                    OracleJob::create($data);
                    $inserted++;
                } else {
                    $existing->update($data);
                    $updated++;
                }
            }
        });

        return compact('inserted', 'updated', 'skipped');
    }

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
        return $this->matchesClassificationRule($job, 'allows_assembly');
    }

    public function isPackagingJob(OracleJob $job): bool
    {
        return $this->matchesClassificationRule($job, 'allows_packaging');
    }

    public function classificationValidationMessage(string $role): string
    {
        $isPackaging = $role === 'packaging';
        $flag = $isPackaging ? 'allows_packaging' : 'allows_assembly';
        $jobLabel = $isPackaging ? 'Empaque' : 'Ensamble';
        $rulesByField = [];

        foreach ($this->activeClassificationRules() as $rule) {
            if (! $rule[$flag]) {
                continue;
            }

            $rulesByField[$rule['match_field']][] = $rule['prefix'];
        }

        $ruleDescriptions = [];

        foreach (MasterAssemblyClassificationRule::MATCH_FIELDS as $field) {
            $prefixes = array_values(array_unique($rulesByField[$field] ?? []));

            if ($prefixes === []) {
                continue;
            }

            sort($prefixes, SORT_NATURAL);
            $ruleDescriptions[] = MasterAssemblyClassificationRule::fieldLabel($field).': '.implode('/', $prefixes);
        }

        if ($ruleDescriptions === []) {
            return "No hay reglas activas configuradas para Jobs de {$jobLabel}.";
        }

        return "El Job {$jobLabel} debe coincidir con una regla activa de {$jobLabel} (".implode('; ', $ruleDescriptions).').';
    }

    public function buildLookupPayload(string $jobNumber): array
    {
        $normalizedJobNumber = strtoupper(trim($jobNumber));
        $job = $this->findByJobNumber($normalizedJobNumber);

        if (! $job) {
            return [
                'found' => false,
                'job_number' => $normalizedJobNumber,
            ];
        }

        return [
            'found' => true,
            'job_number' => $job->job_number,
            'job_qty' => $job->job_qty,
            'qty_completed' => $job->qty_completed,
            'quantity_remainder' => $job->quantity_remainder,
            'line' => $job->line,
            'assembly' => $job->assembly,
            'part_description' => $job->part_description,
            'ttl_cust_po' => $job->ttl_cust_po,
            'ship_code' => $job->ship_code,
            'bom_revision' => $job->bom_revision,
            'valid_for_assembly' => $this->isAssemblyJob($job),
            'valid_for_packaging' => $this->isPackagingJob($job),
            'classification_messages' => [
                'assembly' => $this->classificationValidationMessage('assembly'),
                'packaging' => $this->classificationValidationMessage('packaging'),
            ],
        ];
    }

    /**
     * @param  'allows_assembly'|'allows_packaging'  $flag
     */
    private function matchesClassificationRule(OracleJob $job, string $flag): bool
    {
        foreach ($this->activeClassificationRules() as $rule) {
            if (! $rule[$flag]) {
                continue;
            }

            $value = strtoupper(trim((string) $job->{$rule['match_field']}));

            if ($value !== '' && str_starts_with($value, $rule['prefix'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{match_field: string, prefix: string, allows_assembly: bool, allows_packaging: bool}>
     */
    private function activeClassificationRules(): array
    {
        if ($this->classificationRules !== null) {
            return $this->classificationRules;
        }

        return $this->classificationRules = MasterAssemblyClassificationRule::query()
            ->where('active', true)
            ->orderBy('match_field')
            ->orderBy('prefix')
            ->get(['match_field', 'prefix', 'allows_assembly', 'allows_packaging'])
            ->map(static fn (MasterAssemblyClassificationRule $rule): array => [
                'match_field' => $rule->match_field,
                'prefix' => strtoupper(trim($rule->prefix)),
                'allows_assembly' => $rule->allows_assembly,
                'allows_packaging' => $rule->allows_packaging,
            ])
            ->all();
    }
}
