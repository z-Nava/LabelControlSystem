<?php

namespace App\Http\Requests\Admin;

use App\Support\SerialSchemes;
use App\Support\SerialStandards;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkuSerialFormatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial_standard' => ['required', Rule::in(SerialStandards::all())],
            'serial_scheme' => ['required', Rule::in(SerialSchemes::all())],
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::exists('label_skus', 'sku')->where(function ($query) {
                    $query->where('is_active', true)
                        ->where('serial_standard', strtoupper(trim((string) $this->input('serial_standard', 'UL'))));
                }),
                Rule::unique('sku_serial_formats', 'sku')->where('serial_standard', strtoupper(trim((string) $this->input('serial_standard', 'UL')))),
            ],
            'ul_prefix' => ['nullable', 'string', 'max:10'],
            'ul_serial_break' => ['nullable', 'string', 'max:10'],
            'ul_plant_code' => ['nullable', 'string', 'max:10'],
            'emea_prefix' => ['nullable', 'string', 'max:10'],
            'emea_conformity_code' => ['nullable', 'string', 'max:10'],
            'emea_plant_code' => ['nullable', 'string', 'max:10'],
            'separator' => ['nullable', 'string', Rule::in(['', ' ', '__SPACE__', '-', '_', '|'])],
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
        $standard = strtoupper(trim((string) $this->input('serial_standard', 'UL')));

        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'serial_standard' => $standard,
            'serial_scheme' => trim((string) $this->input('serial_scheme', 'ul_standard')),
            'pattern' => $this->input('pattern') !== null ? trim((string) $this->input('pattern')) : null,
            'ul_prefix' => $this->input('ul_prefix'),
            'ul_serial_break' => $this->input('ul_serial_break'),
            'ul_plant_code' => $this->input('ul_plant_code'),
            'emea_prefix' => $this->input('emea_prefix'),
            'emea_conformity_code' => $this->input('emea_conformity_code'),
            'emea_plant_code' => $this->input('emea_plant_code'),
            'separator' => $this->normalizeSeparatorInput(),
            'year_digits' => (int) $this->input('year_digits', 2),
            'week_digits' => (int) $this->input('week_digits', 2),
            'include_year' => $this->boolean('include_year', true),
            'include_week' => $this->boolean('include_week', true),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    private function normalizeSeparatorInput(): string
    {
        $separator = $this->input('separator', '');

        if ($separator === null) {
            return '';
        }

        if (!is_string($separator)) {
            return '';
        }

        if ($separator === '__SPACE__') {
            return ' ';
        }

        return in_array($separator, ['', ' ', '-', '_', '|'], true) ? $separator : '';
    }
}
