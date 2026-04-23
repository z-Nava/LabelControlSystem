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
        $standard = strtoupper(trim((string) $this->input('serial_standard', 'UL')));
        $isUl = $standard === SerialStandards::UL;
        $isEmea = $standard === SerialStandards::EMEA;
        $isAnz = $standard === SerialStandards::ANZ;

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
            'description' => ['nullable', 'string', 'max:160'],
            'serial_length' => ['nullable', 'integer', 'min:4', 'max:80'],
            'qr_payload_format' => ['required', Rule::in(['serial_only', 'customer_tool_code_serial', 'emea_code_only'])],
            'date_mode' => ['required', Rule::in(['year_week', 'month_year', 'none'])],
            'month_letter_enabled' => ['nullable', 'boolean'],
            'month_letter_map' => ['nullable', 'string', 'max:40'],

            'separator' => ['nullable', 'string', Rule::in(['', ' ', '__SPACE__', '-', '_', '|'])],
            'year_digits' => ['required', 'integer', 'in:2,4'],
            'week_digits' => ['required', 'integer', 'between:1,2'],
            'include_year' => ['nullable', 'boolean'],
            'include_week' => ['nullable', 'boolean'],
            'pattern' => ['nullable', 'string', 'max:80'],
            'unit_digits' => ['required', 'integer', 'min:1', 'max:10'],
            'reset_scope' => ['nullable', Rule::in(['weekly', 'monthly', 'yearly', 'never'])],
            'is_active' => ['nullable', 'boolean'],

            'ul_prefix' => [Rule::requiredIf($isUl), 'nullable', 'string', 'max:10'],
            'ul_prefix_length' => ['nullable', 'integer', 'min:1', 'max:10'],
            'ul_serial_break' => [Rule::requiredIf($isUl), 'nullable', 'string', 'max:10'],
            'ul_plant_code' => [Rule::requiredIf($isUl), 'nullable', 'string', 'max:10'],
            'ul_use_plant_code' => ['nullable', 'boolean'],

            'emea_prefix' => [Rule::requiredIf($isEmea), 'nullable', 'string', 'max:20'],
            'emea_prefix_source' => ['nullable', Rule::in(['sap_console_last_6', 'fixed_value', 'packaging_code'])],
            'emea_prefix_digits' => ['nullable', 'integer', 'min:1', 'max:20'],
            'emea_conformity_code' => [Rule::requiredIf($isEmea), 'nullable', 'string', 'max:10'],
            'emea_plant_code' => ['nullable', 'string', 'max:10'],
            'emea_unit_digits' => ['nullable', 'integer', 'min:1', 'max:10'],
            'emea_declaration_required' => ['nullable', 'boolean'],

            'anz_customer_tool_code' => [Rule::requiredIf($isAnz), 'nullable', 'string', 'max:10'],
            'anz_product_prefix' => [Rule::requiredIf($isAnz), 'nullable', 'string', 'max:20'],
            'anz_tool_version' => [Rule::requiredIf($isAnz), 'nullable', 'string', 'max:2'],
            'anz_tool_version_required' => ['nullable', 'boolean'],
            'anz_unit_digits' => ['nullable', 'integer', 'min:1', 'max:10'],
            'anz_qr_separator' => ['nullable', 'string', 'max:5'],
            'anz_include_customer_tool_code_in_qr' => ['nullable', 'boolean'],
            'anz_serial_print_format' => ['nullable', Rule::in(['spaces', 'no_spaces', 'segmented'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $standard = strtoupper(trim((string) $this->input('serial_standard', 'UL')));

        $defaultScheme = $standard === SerialStandards::ANZ
            ? 'anz_standard'
            : ($standard === SerialStandards::EMEA ? 'emea_rating' : 'ul_standard');

        $defaultDateMode = $standard === SerialStandards::UL ? 'year_week' : 'month_year';
        $defaultQrMode = $standard === SerialStandards::ANZ
            ? 'customer_tool_code_serial'
            : ($standard === SerialStandards::EMEA ? 'emea_code_only' : 'serial_only');

        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'serial_standard' => $standard,
            'serial_scheme' => trim((string) $this->input('serial_scheme', $defaultScheme)),
            'description' => $this->input('description'),
            'serial_length' => $this->input('serial_length'),
            'qr_payload_format' => trim((string) $this->input('qr_payload_format', $defaultQrMode)),
            'date_mode' => trim((string) $this->input('date_mode', $defaultDateMode)),
            'month_letter_enabled' => $this->boolean('month_letter_enabled', $standard !== SerialStandards::UL),
            'month_letter_map' => $this->input('month_letter_map', 'A,B,C,D,E,F,G,H,J,K,L,M'),
            'pattern' => $this->input('pattern') !== null ? trim((string) $this->input('pattern')) : null,
            'ul_prefix' => $this->input('ul_prefix'),
            'ul_prefix_length' => $this->input('ul_prefix_length'),
            'ul_serial_break' => $this->input('ul_serial_break'),
            'ul_plant_code' => $this->input('ul_plant_code'),
            'ul_use_plant_code' => $this->boolean('ul_use_plant_code', true),
            'emea_prefix' => $this->input('emea_prefix'),
            'emea_prefix_source' => $this->input('emea_prefix_source', 'fixed_value'),
            'emea_prefix_digits' => $this->input('emea_prefix_digits'),
            'emea_conformity_code' => $this->input('emea_conformity_code'),
            'emea_plant_code' => $this->input('emea_plant_code'),
            'emea_unit_digits' => $this->input('emea_unit_digits'),
            'emea_declaration_required' => $this->boolean('emea_declaration_required', false),
            'anz_customer_tool_code' => $this->input('anz_customer_tool_code'),
            'anz_product_prefix' => $this->input('anz_product_prefix'),
            'anz_tool_version' => $this->input('anz_tool_version'),
            'anz_tool_version_required' => $this->boolean('anz_tool_version_required', true),
            'anz_unit_digits' => $this->input('anz_unit_digits'),
            'anz_qr_separator' => $this->input('anz_qr_separator', ' | '),
            'anz_include_customer_tool_code_in_qr' => $this->boolean('anz_include_customer_tool_code_in_qr', true),
            'anz_serial_print_format' => $this->input('anz_serial_print_format', 'spaces'),
            'separator' => $this->normalizeSeparatorInput(),
            'year_digits' => (int) $this->input('year_digits', $standard === SerialStandards::UL ? 2 : 4),
            'week_digits' => (int) $this->input('week_digits', 2),
            'include_year' => $this->boolean('include_year', true),
            'include_week' => $this->boolean('include_week', $standard === SerialStandards::UL),
            'unit_digits' => (int) $this->input('unit_digits', $standard === SerialStandards::UL ? 5 : ($standard === SerialStandards::ANZ ? 5 : 6)),
            'reset_scope' => trim((string) $this->input('reset_scope', $standard === SerialStandards::UL ? 'weekly' : 'monthly')),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    private function normalizeSeparatorInput(): string
    {
        $separator = $this->input('separator', '');

        if ($separator === null || !is_string($separator)) {
            return '';
        }

        if ($separator === '__SPACE__') {
            return ' ';
        }

        return in_array($separator, ['', ' ', '-', '_', '|'], true) ? $separator : '';
    }
}
