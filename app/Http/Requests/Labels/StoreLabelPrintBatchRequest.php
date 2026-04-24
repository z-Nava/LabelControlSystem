<?php

namespace App\Http\Requests\Labels;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabelPrintBatchRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $serialCopies = $this->input('serial_copies', $this->input('copies', 2));
        $ratingCopies = $this->input('rating_copies', $this->input('copies', 1));

        $this->merge([
            'print_serial' => $this->boolean('print_serial'),
            'print_rating' => $this->boolean('print_rating'),
            'serial_copies' => $serialCopies,
            'rating_copies' => $ratingCopies,
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
            'copies' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'serial_copies' => ['required', 'integer', 'min:1', 'max:1000'],
            'rating_copies' => ['required', 'integer', 'min:1', 'max:1000'],
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
