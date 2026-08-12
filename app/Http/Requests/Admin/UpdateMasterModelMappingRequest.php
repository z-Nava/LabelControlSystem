<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMasterModelMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'np' => strtoupper(trim((string) $this->input('np'))),
            'sku' => strtoupper(trim((string) $this->input('sku'))),
        ]);
    }

    public function rules(): array
    {
        $mapping = $this->route('master_model_mapping');

        return [
            'np' => [
                'required',
                'string',
                'max:40',
                Rule::unique('master_model_mappings', 'np')
                    ->ignore($mapping?->id)
                    ->where(fn ($query) => $query->where('master_sheet_type', (string) $this->route('type'))),
            ],
            'sku' => ['required', 'string', 'max:80'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
