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
            'separator' => ['nullable', 'string', 'max:5'],
            'year_digits' => ['required', 'integer', 'in:2,4'],
            'week_digits' => ['required', 'integer', 'between:1,2'],
            'include_year' => ['nullable', 'boolean'],
            'include_week' => ['nullable', 'boolean'],
            'pattern' => ['nullable', 'string', 'max:80'],
            'unit_length' => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'pattern' => $this->input('pattern') !== null ? trim((string) $this->input('pattern')) : null,
            'prefix' => $this->input('prefix'),
            'serial_break' => $this->input('serial_break'),
            'plant_code' => $this->input('plant_code'),
            'separator' => trim((string) $this->input('separator', '')),
            'year_digits' => (int) $this->input('year_digits', 2),
            'week_digits' => (int) $this->input('week_digits', 2),
            'include_year' => $this->boolean('include_year', true),
            'include_week' => $this->boolean('include_week', true),
            'is_active' => $this->boolean('is_active', false),
        ]);
    }
}
