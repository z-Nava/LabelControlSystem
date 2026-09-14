<?php

namespace App\Http\Requests\Admin;

use App\Support\SerialStandards;
use Illuminate\Validation\Rule;

class UpdateRatingAssemblyMappingRequest extends StoreRatingAssemblyMappingRequest
{
    public function rules(): array
    {
        $mapping = $this->route('rating_assembly_mapping');

        return [
            'rating_part_number' => ['required', 'string', 'max:80'],
            'assembly_part_number' => [
                'required', 'string', 'max:80',
                Rule::unique('rating_assembly_mappings', 'assembly_part_number')
                    ->ignore($mapping?->id)
                    ->where(fn ($query) => $query
                        ->where('rating_part_number', $this->input('rating_part_number'))
                        ->where('market', $this->input('market'))),
            ],
            'market' => ['required', Rule::in(SerialStandards::all())],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
