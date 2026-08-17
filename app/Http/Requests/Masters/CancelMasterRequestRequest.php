<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class CancelMasterRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cancellation_reason' => trim((string) $this->input('cancellation_reason')),
        ]);
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'El motivo de cancelación es obligatorio.',
            'cancellation_reason.max' => 'El motivo de cancelación no puede exceder 500 caracteres.',
        ];
    }
}
