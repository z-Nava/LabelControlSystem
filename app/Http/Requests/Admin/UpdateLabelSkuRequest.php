<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLabelSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('label_sku')?->id ?? null;
        $serialStandard = strtoupper(trim((string) $this->input('serial_standard', 'UL')));
        $isEmeaOrAnz = in_array($serialStandard, ['EMEA', 'ANZ'], true);

        return [
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::unique('label_skus', 'sku')
                    ->ignore($id)
                    ->where('serial_standard', $serialStandard),
            ],
            'serial_standard' => ['required', Rule::in(['UL', 'EMEA', 'ANZ'])],
            'label_part_number' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:160'],
            'console_sku' => [$isEmeaOrAnz ? 'required' : 'nullable', 'string', 'max:80'],
            'assembly_part_number' => [$isEmeaOrAnz ? 'required' : 'nullable', 'string', 'max:80'],
            'packaging_part_number' => ['nullable', 'string', 'max:80'],
            'emea_sku' => ['nullable', 'string', 'max:80'],
            'anz_sku' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'label_part_number' => strtoupper(trim((string) $this->input('label_part_number'))),
            'serial_standard' => strtoupper(trim((string) $this->input('serial_standard', 'UL'))),
            'console_sku' => strtoupper(trim((string) $this->input('console_sku'))),
            'assembly_part_number' => strtoupper(trim((string) $this->input('assembly_part_number'))),
            'packaging_part_number' => strtoupper(trim((string) $this->input('packaging_part_number'))),
            'emea_sku' => strtoupper(trim((string) $this->input('emea_sku'))),
            'anz_sku' => strtoupper(trim((string) $this->input('anz_sku'))),
        ]);
    }
}
