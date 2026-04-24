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

    public function rules(): array
    {
        $mapping = $this->route('master_model_mapping');

        return [
            'np' => ['required', 'string', 'max:40'],
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::unique('master_model_mappings', 'sku')
                    ->ignore($mapping?->id)
                    ->where(fn ($query) => $query
                        ->where('np', strtoupper(trim((string) $this->input('np'))))
                        ->where('master_sheet_type', (string) $this->route('type'))
                    ),
            ],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
