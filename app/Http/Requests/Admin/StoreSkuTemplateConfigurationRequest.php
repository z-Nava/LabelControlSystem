<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSkuTemplateConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'template_is_active' => $this->boolean('template_is_active', true),
            'profile_is_active' => $this->boolean('profile_is_active', true),
            'template_meta' => $this->filled('template_meta') ? json_decode((string) $this->input('template_meta'), true) : null,
            'profile_settings' => $this->filled('profile_settings') ? json_decode((string) $this->input('profile_settings'), true) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'label_sku_id' => ['required', 'integer', 'exists:label_skus,id'],
            'label_type' => ['required', 'in:serial,rating,shipping'],

            'template_name' => ['required', 'string', 'max:120'],
            'template_dpi' => ['required', 'integer', 'in:203,300'],
            'template_width_mm' => ['nullable', 'numeric', 'min:1'],
            'template_height_mm' => ['nullable', 'numeric', 'min:1'],
            'template_zpl' => ['required', 'string'],
            'template_meta' => ['nullable', 'array'],
            'template_is_active' => ['required', 'boolean'],

            'profile_name' => ['required', 'string', 'max:120'],
            'default_printer_name' => ['nullable', 'string', 'max:120'],
            'default_printer_ip' => ['nullable', 'ip'],
            'profile_dpi' => ['required', 'integer', 'in:203,300'],
            'darkness' => ['nullable', 'integer', 'min:0', 'max:30'],
            'speed' => ['nullable', 'integer', 'min:1', 'max:14'],
            'media_type' => ['nullable', 'in:direct_thermal,thermal_transfer'],
            'media_tracking' => ['nullable', 'in:gap,black_mark,continuous,default'],
            'print_mode' => ['nullable', 'in:tear_off,peel_off,cutter,rewind,applicator'],
            'offset_x' => ['nullable', 'integer', 'min:-9999', 'max:9999'],
            'offset_y' => ['nullable', 'integer', 'min:-9999', 'max:9999'],
            'profile_settings' => ['nullable', 'array'],
            'profile_is_active' => ['required', 'boolean'],
        ];
    }
}
