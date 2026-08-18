<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateMasterAssemblyClassificationRuleRequest extends StoreMasterAssemblyClassificationRuleRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rule = $this->route('assembly_rule');

        $rules['prefix'] = [
            'required',
            'string',
            'max:30',
            'regex:/^[A-Z0-9._-]+$/',
            Rule::unique('master_assembly_classification_rules', 'prefix')
                ->where(fn ($query) => $query->where('match_field', $this->input('match_field')))
                ->ignore($rule?->id),
        ];

        return $rules;
    }
}
