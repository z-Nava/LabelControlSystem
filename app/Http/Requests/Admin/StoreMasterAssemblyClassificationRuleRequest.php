<?php

namespace App\Http\Requests\Admin;

use App\Models\MasterAssemblyClassificationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMasterAssemblyClassificationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'match_field' => ['required', Rule::in(MasterAssemblyClassificationRule::MATCH_FIELDS)],
            'prefix' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('master_assembly_classification_rules', 'prefix')
                    ->where(fn ($query) => $query->where('match_field', $this->input('match_field'))),
            ],
            'description' => ['nullable', 'string', 'max:160'],
            'allows_assembly' => ['required', 'boolean'],
            'allows_packaging' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'match_field' => strtolower(trim((string) $this->input('match_field'))),
            'prefix' => strtoupper(trim((string) $this->input('prefix'))),
            'description' => trim((string) $this->input('description')),
            'allows_assembly' => $this->boolean('allows_assembly'),
            'allows_packaging' => $this->boolean('allows_packaging'),
            'active' => $this->boolean('active'),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('allows_assembly') && ! $this->boolean('allows_packaging')) {
                    $validator->errors()->add('allows_assembly', 'Selecciona al menos una clasificación: Ensamble o Empaque.');
                }
            },
        ];
    }
}
