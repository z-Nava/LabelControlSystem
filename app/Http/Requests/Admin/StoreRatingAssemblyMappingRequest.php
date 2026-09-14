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
        $this->merge([
            'rating_part_number' => strtoupper(trim((string) $this->input('rating_part_number'))),
            'assembly_part_number' => strtoupper(trim((string) $this->input('assembly_part_number'))),
            'market' => strtoupper(trim((string) $this->input('market'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'rating_part_number' => ['required', 'string', 'max:80'],
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
