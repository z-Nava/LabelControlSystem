<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDummyQrTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'dummy_type' => ['required', 'in:rmt,rw'],
            'dpi' => ['required', 'integer', 'in:203,300'],
            'width_mm' => ['nullable', 'numeric', 'min:1', 'max:999'],
            'height_mm' => ['nullable', 'numeric', 'min:1', 'max:999'],
            'qr_x' => ['required', 'integer', 'min:0', 'max:2000'],
            'qr_y' => ['required', 'integer', 'min:0', 'max:2000'],
            'qr_magnification' => ['required', 'integer', 'min:1', 'max:10'],
            'qr_orientation' => ['required', 'in:N,R,I,B'],
            'fg_x' => ['required', 'integer', 'min:0', 'max:2000'],
            'fg_y' => ['required', 'integer', 'min:0', 'max:2000'],
            'fg_font_size' => ['required', 'integer', 'min:10', 'max:200'],
            'job_x' => ['required', 'integer', 'min:0', 'max:2000'],
            'job_y' => ['required', 'integer', 'min:0', 'max:2000'],
            'job_font_size' => ['required', 'integer', 'min:10', 'max:200'],
            'consecutive_x' => ['required', 'integer', 'min:0', 'max:2000'],
            'consecutive_y' => ['required', 'integer', 'min:0', 'max:2000'],
            'consecutive_font_size' => ['required', 'integer', 'min:10', 'max:220'],
            'title_x' => ['required', 'integer', 'min:0', 'max:2000'],
            'title_y' => ['required', 'integer', 'min:0', 'max:2000'],
            'title_font_size' => ['required', 'integer', 'min:10', 'max:220'],
            'connection_type' => ['required', 'in:usb,network'],
            'default_printer_name' => ['nullable', 'string', 'max:120'],
            'default_printer_ip' => ['nullable', 'ip'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'dummy_type' => trim((string) $this->input('dummy_type')),
            'qr_orientation' => strtoupper(trim((string) $this->input('qr_orientation', 'N'))),
            'connection_type' => trim((string) $this->input('connection_type', 'usb')),
            'default_printer_name' => trim((string) $this->input('default_printer_name')),
            'default_printer_ip' => trim((string) $this->input('default_printer_ip')),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
