<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSkuSerialFormatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('sku_serial_format')?->id ?? null;

        return [
            'sku' => ['required', 'string', 'max:80', 'exists:label_skus,sku', "unique:sku_serial_formats,sku,{$id}"],
            'prefix' => ['nullable', 'string', 'max:10'],
            'serial_break' => ['nullable', 'string', 'max:10'],
            'plant_code' => ['nullable', 'string', 'max:10'],
            'pattern' => ['required', 'string', 'max:80'],
            'unit_length' => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'pattern' => trim((string) $this->input('pattern')),
            'prefix' => $this->input('prefix'),
            'serial_break' => $this->input('serial_break'),
            'plant_code' => $this->input('plant_code'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
