<?php

namespace App\Http\Requests\Labels;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabelPrintBatchRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'print_serial' => $this->boolean('print_serial'),
            'print_rating' => $this->boolean('print_rating'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_type' => ['required', 'in:print,reprint,rework'],
            'copies' => ['required', 'integer', 'min:1', 'max:1000'],
            'print_serial' => ['required', 'boolean'],
            'print_rating' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255', 'required_if:batch_type,reprint,rework'],
            'selected_serial_unit_ids' => ['nullable', 'array'],
            'selected_serial_unit_ids.*' => ['integer'],
            'selected_rating_unit_ids' => ['nullable', 'array'],
            'selected_rating_unit_ids.*' => ['integer'],
        ];
    }
}
