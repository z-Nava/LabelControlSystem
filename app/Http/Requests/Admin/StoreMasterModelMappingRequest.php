<?php

namespace App\Http\Requests\Admin;

use App\Models\MasterModelMapping;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMasterModelMappingRequest extends FormRequest
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
        $type = (string) $this->route('type');

        return [
            'np' => [
                'required',
                'string',
                'max:40',
                Rule::unique('master_model_mappings', 'np')
                    ->where(fn ($query) => $query->where('master_sheet_type', $type)),
            ],
            'sku' => ['required', 'string', 'max:80'],
            'active' => ['nullable', 'boolean'],
            'type' => ['nullable', Rule::in(MasterModelMapping::TYPES)],
        ];
    }
}
