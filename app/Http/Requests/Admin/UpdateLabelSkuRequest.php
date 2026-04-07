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

        return [
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::unique('label_skus', 'sku')
                    ->ignore($id)
                    ->where('serial_standard', $serialStandard),
            ],
            'serial_standard' => ['required', Rule::in(['UL', 'EMEA'])],
            'label_part_number' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:160'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'label_part_number' => strtoupper(trim((string) $this->input('label_part_number'))),
            'serial_standard' => strtoupper(trim((string) $this->input('serial_standard', 'UL'))),
        ]);
    }
}
