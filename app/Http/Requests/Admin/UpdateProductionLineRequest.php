<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductionLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('production_line')?->id ?? null;

        return [
            'code'      => ['required', 'string', 'max:30', "unique:production_lines,code,{$id}"],
            'name'      => ['required', 'string', 'max:120'],
            'line_type' => ['required', 'string', 'max:40'],
            'active'    => ['nullable', 'boolean'],
        ];
    }
}
