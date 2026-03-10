<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabelTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'meta' => $this->filled('meta') ? json_decode((string) $this->input('meta'), true) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'label_type' => ['required', 'in:serial,rating,shipping'],
            'label_sku_id' => ['nullable', 'integer', 'exists:label_skus,id'],
            'dpi' => ['required', 'integer', 'in:203,300'],
            'width_mm' => ['nullable', 'numeric', 'min:1'],
            'height_mm' => ['nullable', 'numeric', 'min:1'],
            'zpl' => ['required', 'string'],
            'meta' => ['nullable', 'array'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
