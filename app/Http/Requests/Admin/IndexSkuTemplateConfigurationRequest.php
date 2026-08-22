<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSkuTemplateConfigurationRequest extends FormRequest
{
    private const SORTS = ['sku', 'type', 'updated'];

    private const SERIAL_STANDARDS = ['ALL', 'UL', 'EMEA', 'ANZ'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $search = $this->input('q');
        $sortInput = $this->input('sort', 'sku');
        $serialStandardInput = $this->input('serial_standard', 'ALL');
        $sort = is_string($sortInput) ? $sortInput : 'sku';
        $serialStandard = is_string($serialStandardInput)
            ? strtoupper(trim($serialStandardInput))
            : 'ALL';

        $this->merge([
            'q' => is_string($search) && trim($search) !== '' ? trim($search) : null,
            'sort' => in_array($sort, self::SORTS, true) ? $sort : 'sku',
            'serial_standard' => in_array($serialStandard, self::SERIAL_STANDARDS, true)
                ? $serialStandard
                : 'ALL',
        ]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'sort' => ['required', Rule::in(self::SORTS)],
            'serial_standard' => ['required', Rule::in(self::SERIAL_STANDARDS)],
        ];
    }
}
