<?php

namespace App\Http\Requests\Masters;

use App\Models\OracleJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMasterRequestRequest extends FormRequest
{

    /**
     * @var array<string,OracleJob|null>
     */
    private array $oracleJobCache = [];


    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_date' => ['required', 'date', 'before_or_equal:today'],
            'week' => ['required', 'integer', 'min:1', 'max:53'],
            'line_id' => ['required', 'integer', 'exists:production_lines,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'leader_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\pL\s\-.\x27"]+$/u'],

            'po_number' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9\-\/_\s]+$/'],
            'job_assembly' => [
                'required',
                'string',
                'max:40',
                'regex:/^[0-9A-Za-z\-]+$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!is_string($value)) {
                        return;
                    }

                    $job = $this->findOracleJob($value);

                    if (!$job) {
                        $fail('El Job Ensamble no existe en Oracle Jobs.');
                        return;
                    }

                    if (!$this->isAssemblyJob($job)) {
                        $fail('El Job Ensamble debe pertenecer a Ensamble/Subensamble (103/130) o a Motores-Moldeo (MEXMI/MXM).');
                    }
                },
            ],
            'job_packaging' => [
                'nullable',
                'string',
                'max:40',
                'regex:/^[0-9A-Za-z\-]+$/',
                'different:job_assembly',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!is_string($value) || trim($value) === '') {
                        return;
                    }

                    $job = $this->findOracleJob($value);

                    if (!$job) {
                        $fail('El Job Empaque no existe en Oracle Jobs.');
                        return;
                    }

                    if (!$this->isPackagingJob($job)) {
                        $fail('El Job Empaque debe pertenecer a Empaque (assembly 018/055/001).');
                    }
                },
            ],
            'destination' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9\-\/_\s]+$/'],

            'folios_from' => ['required', 'integer', 'min:1'],
            'folios_to' => ['required', 'integer', 'min:1', 'gte:folios_from'],
            'std_pack_qty' => ['nullable', 'integer', 'min:1'],

            'partial_folio' => ['nullable', 'integer', 'min:1', 'required_with:partial_qty'],
            'partial_qty' => ['nullable', 'integer', 'min:1', 'required_with:partial_folio'],

            'request_type' =>  ['required', Rule::in(['assembly', 'batteries_assembly', 'assembly_packaging', 'motors_molding'])],
            'kind' => ['required', 'in:new,reposition'],

            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function findOracleJob(string $jobNumber): ?OracleJob
    {
        $normalizedJobNumber = strtoupper(trim($jobNumber));

        if (array_key_exists($normalizedJobNumber, $this->oracleJobCache)) {
            return $this->oracleJobCache[$normalizedJobNumber];
        }

        return $this->oracleJobCache[$normalizedJobNumber] = OracleJob::query()
            ->whereRaw('UPPER(job_number) = ?', [$normalizedJobNumber])
            ->first();
    }

    private function isAssemblyJob(OracleJob $job): bool
    {
        $assembly = strtoupper(trim((string) $job->assembly));
        $line = strtoupper(trim((string) $job->line));

        return str_starts_with($assembly, '103')
            || str_starts_with($assembly, '130')
            || str_starts_with($line, 'MEXMI')
            || str_starts_with($line, 'MXM');
    }

    private function isPackagingJob(OracleJob $job): bool
    {
        $assembly = strtoupper(trim((string) $job->assembly));

        return str_starts_with($assembly, '018')
            || str_starts_with($assembly, '055')
            || str_starts_with($assembly, '001');
    }
}
