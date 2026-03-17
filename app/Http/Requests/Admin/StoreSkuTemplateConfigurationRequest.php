<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'connection_type' => $this->input('connection_type', 'usb'),
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
            'serial_position_x' => ['required', 'integer', 'min:0', 'max:5000'],
            'serial_position_y' => ['required', 'integer', 'min:0', 'max:5000'],
            'serial_font_size' => ['required', 'integer', 'min:10', 'max:300'],
            'serial_orientation' => ['required', 'in:N,R,I,B'],
            'template_is_active' => ['required', 'boolean'],

            'profile_name' => ['required', 'string', 'max:120'],
            'default_printer_name' => ['required', 'string', 'max:120'],
            'connection_type' => ['required', Rule::in(['usb', 'network'])],
            'default_printer_ip' => ['nullable', 'ip', 'required_if:connection_type,network'],
            'profile_dpi' => ['required', 'integer', 'in:203,300'],
            'darkness' => ['nullable', 'integer', 'min:0', 'max:30'],
            'speed' => ['nullable', 'integer', 'min:1', 'max:14'],
            'media_type' => ['nullable', 'in:direct_thermal,thermal_transfer'],
            'media_tracking' => ['nullable', 'in:gap,black_mark,continuous,default'],
            'print_mode' => ['nullable', 'in:tear_off,peel_off,cutter,rewind,applicator'],
            'offset_x' => ['nullable', 'integer', 'min:-9999', 'max:9999'],
            'offset_y' => ['nullable', 'integer', 'min:-9999', 'max:9999'],
            'profile_is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('connection_type') !== 'usb') {
                return;
            }

            $usbConnected = filter_var($this->input('usb_connected', false), FILTER_VALIDATE_BOOLEAN);

            if (!$usbConnected) {
                $validator->errors()->add('usb_connected', 'Debes validar la conexión USB de la impresora antes de guardar.');
            }
        });
    }
}
