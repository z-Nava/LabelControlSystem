<?php

namespace App\Http\Requests\Dummies;

use Illuminate\Foundation\Http\FormRequest;

class StoreDummyPrintBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'batch_type' => trim((string) $this->input('batch_type')),
            'reason' => trim((string) $this->input('reason')),
        ]);
    }

    public function rules(): array
    {
        return [
            'batch_type' => ['required', 'in:print,reprint'],
            'copies' => ['required', 'integer', 'min:1', 'max:10'],
            'reason' => ['nullable', 'string', 'max:255', 'required_if:batch_type,reprint'],
        ];
    }
}
