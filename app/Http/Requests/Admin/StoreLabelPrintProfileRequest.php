<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabelPrintProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'settings' => $this->filled('settings') ? json_decode((string) $this->input('settings'), true) : null,
            'label_type' => $this->input('label_type') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'label_sku_id' => ['required', 'integer', 'exists:label_skus,id'],
            'label_type' => ['nullable', 'in:serial,rating,shipping'],
            'label_template_id' => ['nullable', 'integer', 'exists:label_templates,id'],
            'name' => ['required', 'string', 'max:120'],
            'default_printer_name' => ['nullable', 'string', 'max:120'],
            'default_printer_ip' => ['nullable', 'ip'],
            'dpi' => ['required', 'integer', 'in:203,300'],
            'darkness' => ['nullable', 'integer', 'min:0', 'max:30'],
            'speed' => ['nullable', 'integer', 'min:1', 'max:14'],
            'media_type' => ['nullable', 'string', 'max:40'],
            'media_tracking' => ['nullable', 'string', 'max:40'],
            'print_mode' => ['nullable', 'string', 'max:40'],
            'offset_x' => ['nullable', 'integer', 'min:-9999', 'max:9999'],
            'offset_y' => ['nullable', 'integer', 'min:-9999', 'max:9999'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
