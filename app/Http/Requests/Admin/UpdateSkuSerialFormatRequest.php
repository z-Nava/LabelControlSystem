<?php

namespace App\Http\Requests\Admin;

use App\Support\SerialSchemes;
use App\Support\SerialStandards;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkuSerialFormatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('sku_serial_format')?->id ?? null;
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
                Rule::unique('sku_serial_formats', 'sku')
                    ->ignore($id)
                    ->where('serial_standard', strtoupper(trim((string) $this->input('serial_standard', 'UL')))),
            ],
            'description' => ['nullable', 'string', 'max:160'],
            'serial_length' => ['nullable', 'integer', 'min:4', 'max:80'],
            'qr_payload_format' => ['required', Rule::in(['serial_only', 'customer_tool_code_serial', 'emea_code_only'])],
            'date_mode' => ['required', Rule::in(['year_week', 'month_year', 'none'])],
            'month_letter_enabled' => ['nullable', 'boolean'],
            'month_letter_map' => ['nullable', 'string', 'max:40'],

            'separator' => ['nullable', 'string', $isUl ? Rule::in(['']) : Rule::in(['', ' ', '__SPACE__', '-', '_', '|'])],
            'year_digits' => ['required', 'integer', $isUl ? 'in:2' : 'in:2,4'],
            'week_digits' => ['required', 'integer', $isUl ? 'in:2' : 'between:1,2'],
            'include_year' => ['nullable', 'boolean'],
            'include_week' => ['nullable', 'boolean'],
            'pattern' => ['nullable', 'string', 'max:80'],
            'unit_digits' => ['required', 'integer', $isUl ? 'min:4' : 'min:1', 'max:10'],
            'reset_scope' => ['nullable', $isUl ? Rule::in(['weekly']) : Rule::in(['weekly', 'monthly', 'yearly', 'never'])],
            'is_active' => ['nullable', 'boolean'],

            'ul_prefix' => [Rule::requiredIf($isUl), 'nullable', 'string', $isUl ? 'regex:/^[A-Z0-9]{3,4}$/' : 'max:10'],
            'ul_serial_break' => [Rule::requiredIf($isUl), 'nullable', 'string', $isUl ? 'regex:/^[A-Z]$/' : 'max:10'],
            'ul_plant_code' => [Rule::requiredIf($isUl), 'nullable', 'string', $isUl ? 'regex:/^[A-Z0-9]$/' : 'max:10'],

            'emea_prefix' => [Rule::requiredIf($isEmea), 'nullable', 'string', $isEmea ? 'regex:/^\d{6}$/' : 'max:20'],
            'emea_conformity_code' => [Rule::requiredIf($isEmea), 'nullable', 'string', $isEmea ? 'regex:/^\d{2}$/' : 'max:10'],
            'emea_unit_digits' => ['nullable', 'integer', $isEmea ? 'in:6' : 'min:1', 'max:10'],

            'anz_customer_tool_code' => [Rule::requiredIf($isAnz), 'nullable', 'string', 'max:40'],
            'anz_product_prefix' => [Rule::requiredIf($isAnz), 'nullable', 'string', $isAnz ? 'regex:/^[A-Z0-9]{9}$/' : 'max:20'],
            'anz_tool_version' => [Rule::requiredIf($isAnz), 'nullable', 'string', $isAnz ? 'regex:/^[A-Z]$/' : 'max:2'],
            'anz_tool_version_required' => ['nullable', 'boolean'],
            'anz_unit_digits' => ['nullable', 'integer', $isAnz ? 'in:5' : 'min:1', 'max:10'],
            'anz_qr_separator' => ['nullable', 'string', $isAnz ? Rule::in([' | ']) : 'max:5'],
            'anz_include_customer_tool_code_in_qr' => ['nullable', 'boolean'],
            'anz_serial_print_format' => ['nullable', Rule::in($isAnz ? ['spaces'] : ['spaces', 'no_spaces', 'segmented'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $standard = strtoupper(trim((string) $this->input('serial_standard', 'UL')));
        $isUl = $standard === SerialStandards::UL;
        $isEmea = $standard === SerialStandards::EMEA;
        $isAnz = $standard === SerialStandards::ANZ;

        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'serial_standard' => $standard,
            'serial_scheme' => $isUl ? 'ul_standard' : trim((string) $this->input('serial_scheme', $standard === SerialStandards::EMEA ? 'emea_rating' : 'anz_standard')),
            'description' => $this->input('description'),
            'serial_length' => $isAnz ? 23 : (($isUl || $isEmea) ? null : $this->input('serial_length')),
            'qr_payload_format' => $isUl ? 'serial_only' : trim((string) $this->input('qr_payload_format', $standard === SerialStandards::ANZ ? 'customer_tool_code_serial' : 'emea_code_only')),
            'date_mode' => $isUl ? 'year_week' : trim((string) $this->input('date_mode', 'month_year')),
            'month_letter_enabled' => $isUl ? false : $this->boolean('month_letter_enabled', true),
            'month_letter_map' => $this->input('month_letter_map', 'A,B,C,D,E,F,G,H,J,K,L,M'),
            'pattern' => $isUl ? null : ($this->input('pattern') !== null ? trim((string) $this->input('pattern')) : null),
            'ul_prefix' => strtoupper(trim((string) $this->input('ul_prefix'))),
            'ul_serial_break' => strtoupper(trim((string) $this->input('ul_serial_break'))),
            'ul_plant_code' => strtoupper(trim((string) $this->input('ul_plant_code'))),
            'emea_prefix' => trim((string) $this->input('emea_prefix')),
            'emea_conformity_code' => trim((string) $this->input('emea_conformity_code')),
            'emea_unit_digits' => $isEmea ? 6 : $this->input('emea_unit_digits'),
            'anz_customer_tool_code' => strtoupper(trim((string) $this->input('anz_customer_tool_code'))),
            'anz_product_prefix' => strtoupper(trim((string) $this->input('anz_product_prefix'))),
            'anz_tool_version' => strtoupper(trim((string) $this->input('anz_tool_version', 'A'))),
            'anz_tool_version_required' => $isAnz ? true : $this->boolean('anz_tool_version_required', true),
            'anz_unit_digits' => $isAnz ? 5 : $this->input('anz_unit_digits'),
            'anz_qr_separator' => $isAnz ? ' | ' : $this->input('anz_qr_separator', ' | '),
            'anz_include_customer_tool_code_in_qr' => $isAnz ? true : $this->boolean('anz_include_customer_tool_code_in_qr', true),
            'anz_serial_print_format' => $isAnz ? 'spaces' : $this->input('anz_serial_print_format', 'spaces'),
            'separator' => $isUl ? '' : $this->normalizeSeparatorInput(),
            'year_digits' => $isUl ? 2 : (int) $this->input('year_digits', 4),
            'week_digits' => $isUl ? 2 : (int) $this->input('week_digits', 2),
            'include_year' => ($isUl || $isAnz) ? true : $this->boolean('include_year', true),
            'include_week' => $isUl ? true : ($isAnz ? false : $this->boolean('include_week', false)),
            'unit_digits' => $isEmea ? 6 : ($isAnz ? 5 : (int) $this->input('unit_digits', 5)),
            'reset_scope' => $isUl ? 'weekly' : (($isEmea || $isAnz) ? 'monthly' : trim((string) $this->input('reset_scope', 'monthly'))),
            'is_active' => $this->boolean('is_active', false),
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
