<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TestLabelPrinterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'default_printer_name' => ['nullable', 'string', 'max:120'],
            'default_printer_ip' => ['required', 'ip'],
            'dpi' => ['nullable', 'integer', 'in:203,300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'default_printer_ip.required' => 'Ingresa la IP de la impresora para hacer la prueba.',
            'default_printer_ip.ip' => 'La IP de la impresora no es válida.',
        ];
    }
}

