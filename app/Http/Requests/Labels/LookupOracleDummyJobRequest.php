<?php

namespace App\Http\Requests\Labels;

use Illuminate\Foundation\Http\FormRequest;

class LookupOracleDummyJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_number' => ['required', 'string', 'max:40', 'regex:/^[0-9A-Za-z\-]+$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'job_number' => strtoupper(trim((string) $this->input('job_number'))),
        ]);
    }
}
