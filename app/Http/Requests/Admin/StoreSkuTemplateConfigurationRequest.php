<?php

namespace App\Http\Requests\Admin;

use App\Models\LabelSku;
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
        $labelType = $this->input('label_type', 'serial');
        $sku = LabelSku::query()->find($this->input('label_sku_id'));
        $serialStandard = strtoupper(trim((string) ($sku?->serial_standard ?? $this->input('serial_standard', 'UL'))));
        $forceRatingQr = $labelType === 'rating' && $serialStandard === 'EMEA';

        $this->merge([
            'template_is_active' => $this->boolean('template_is_active', true),
            'profile_is_active' => $this->boolean('profile_is_active', true),
            'rating_with_qr' => $forceRatingQr ? true : $this->boolean('rating_with_qr', false),
            'connection_type' => $this->input('connection_type', 'usb'),
            'sn_prefix' => trim((string) $this->input('sn_prefix', 'SN:')),
            'label_type' => $labelType,
            'serial_standard' => $serialStandard,
            'qr_orientation' => $this->input('qr_orientation', 'N'),
        ]);
    }

    public function rules(): array
    {
        return [
            'label_sku_id' => [
                'required',
                'integer',
                Rule::exists('label_skus', 'id')->where('serial_standard', strtoupper(trim((string) $this->input('serial_standard', 'UL')))),
            ],
            'label_type' => ['required', 'in:serial,rating'],
            'serial_standard' => ['required', Rule::in(['UL', 'EMEA'])],
            'rating_with_qr' => ['nullable', 'boolean'],

            'template_name' => ['required', 'string', 'max:120'],
            'template_dpi' => ['required', 'integer', 'in:203,300'],
            'template_width_mm' => ['nullable', 'numeric', 'min:1'],
            'template_height_mm' => ['nullable', 'numeric', 'min:1'],
            'qr_position_x' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'qr_position_y' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'qr_orientation' => ['nullable', 'in:N,R,I,B'],
            'qr_magnification' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sku_position_x' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'sku_position_y' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'sku_font_size' => ['nullable', 'integer', 'min:10', 'max:300'],
            'sku_font_width' => ['nullable', 'integer', 'min:10', 'max:300'],
            'sku_orientation' => ['nullable', 'in:N,R,I,B'],
            'serial_position_x' => ['required', 'integer', 'min:0', 'max:5000'],
            'serial_position_y' => ['required', 'integer', 'min:0', 'max:5000'],
            'serial_font_size' => ['required', 'integer', 'min:10', 'max:300'],
            'serial_font_width' => ['nullable', 'integer', 'min:10', 'max:300'],
            'serial_orientation' => ['required', 'in:N,R,I,B'],
            'sn_position_x' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'sn_position_y' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'sn_font_size' => ['nullable', 'integer', 'min:10', 'max:300'],
            'sn_orientation' => ['nullable', 'in:N,R,I,B'],
            'sn_prefix' => ['nullable', 'string', 'max:20'],
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
            if ($this->input('connection_type') === 'usb') {
                $usbConnected = filter_var($this->input('usb_connected', false), FILTER_VALIDATE_BOOLEAN);

                if (!$usbConnected) {
                    $validator->errors()->add('usb_connected', 'Debes validar la conexión USB de la impresora antes de guardar.');
                }
            }

            $requiresQrLayout = $this->input('label_type') === 'serial'
                || ($this->input('label_type') === 'rating' && $this->boolean('rating_with_qr'));

            if (!$requiresQrLayout) {
                return;
            }

            foreach ([
                'qr_position_x',
                'qr_position_y',
                'qr_orientation',
                'qr_magnification',
                'sku_position_x',
                'sku_position_y',
                'sku_font_size',
                'sku_orientation',
                'sn_position_x',
                'sn_position_y',
                'sn_font_size',
                'sn_orientation',
            ] as $field) {
                if ($this->filled($field)) {
                    continue;
                }

                $validator->errors()->add($field, 'Este campo es requerido para etiquetas Serial.');
            }
        });
    }
}
