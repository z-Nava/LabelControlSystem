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

    public function rules(): array
    {
        $type = (string) $this->route('type');

        return [
            'np' => ['required', 'string', 'max:40'],
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::unique('master_model_mappings', 'sku')
                    ->where(fn ($query) => $query
                        ->where('np', strtoupper(trim((string) $this->input('np'))))
                        ->where('master_sheet_type', $type)
                    ),
            ],
            'active' => ['nullable', 'boolean'],
            'type' => ['nullable', Rule::in(MasterModelMapping::TYPES)],
        ];
    }
}
