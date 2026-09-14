<?php

namespace App\Http\Requests\Admin;

use App\Support\SerialStandards;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRatingAssemblyMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $optionalParts = [];
        foreach (['serial_part_number', 'shipping_part_number', 'inner_part_number'] as $field) {
            $value = strtoupper(trim((string) $this->input($field)));
            $optionalParts[$field] = $value === '' ? null : $value;
        }

        $this->merge([
            ...$optionalParts,
            'rating_part_number' => strtoupper(trim((string) $this->input('rating_part_number'))),
            'assembly_part_number' => strtoupper(trim((string) $this->input('assembly_part_number'))),
            'market' => strtoupper(trim((string) $this->input('market'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'rating_part_number' => ['required', 'string', 'max:80'],
            'serial_part_number' => ['nullable', 'string', 'max:80'],
            'shipping_part_number' => ['nullable', 'string', 'max:80'],
            'inner_part_number' => ['nullable', 'string', 'max:80'],
            'assembly_part_number' => [
                'required', 'string', 'max:80',
                Rule::unique('rating_assembly_mappings', 'assembly_part_number')
                    ->where(fn ($query) => $query
                        ->where('rating_part_number', $this->input('rating_part_number'))
                        ->where('market', $this->input('market'))),
            ],
            'market' => ['required', Rule::in(SerialStandards::all())],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
