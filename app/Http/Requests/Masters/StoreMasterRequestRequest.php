<?php

namespace App\Http\Requests\Masters;

use App\Models\OracleJob;
use App\Services\Oracle\OracleJobLookupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMasterRequestRequest extends FormRequest
{
    private ?OracleJobLookupService $oracleJobLookup = null;

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

            'request_type' => ['required', Rule::in(['assembly', 'batteries_assembly', 'assembly_packaging', 'motors_molding'])],
            'kind' => ['required', 'in:new,reposition'],

            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function findOracleJob(string $jobNumber): ?OracleJob
    {
        return $this->oracleJobLookup()->findByJobNumber($jobNumber);
    }

    private function isAssemblyJob(OracleJob $job): bool
    {
        return $this->oracleJobLookup()->isAssemblyJob($job);
    }

    private function isPackagingJob(OracleJob $job): bool
    {
        return $this->oracleJobLookup()->isPackagingJob($job);
    }

    private function oracleJobLookup(): OracleJobLookupService
    {
        if (!$this->oracleJobLookup) {
            $this->oracleJobLookup = app(OracleJobLookupService::class);
        }

        return $this->oracleJobLookup;
    }
}