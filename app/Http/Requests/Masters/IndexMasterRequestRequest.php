<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMasterRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => strtolower((string) $this->query('status', 'pending')),
            'q' => trim((string) $this->query('q', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'completed', 'cancelled', 'all'])],
            'q' => ['nullable', 'string', 'max:80', 'regex:/^[\pL\pN\s#\-_.\/\x27]*$/u'],
        ];
    }
}
