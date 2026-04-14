<?php

namespace App\Http\Requests\Dummies;

use App\Services\Oracle\OracleJobLookupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDummyRequestRequest extends FormRequest
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
            'job_number' => ['required', 'string', 'max:40', 'regex:/^[0-9A-Za-z\-]+$/'],
            'quantity_requested' => ['required', 'integer', 'min:1', 'max:100000'],
            'request_type' => ['required', 'in:first_time,rework'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'leader_name' => trim((string) $this->input('leader_name')),
            'job_number' => strtoupper(trim((string) $this->input('job_number'))),
            'request_type' => trim((string) $this->input('request_type')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $jobNumber = (string) $this->input('job_number');

            if ($jobNumber === '') {
                return;
            }

            $job = $this->oracleJobLookup()->findByJobNumber($jobNumber);

            if (!$job) {
                $validator->errors()->add('job_number', 'El Job no existe en Oracle Jobs.');
            }
        });
    }

    private function oracleJobLookup(): OracleJobLookupService
    {
        if ($this->oracleJobLookup instanceof OracleJobLookupService) {
            return $this->oracleJobLookup;
        }

        $this->oracleJobLookup = app(OracleJobLookupService::class);

        return $this->oracleJobLookup;
    }
}
