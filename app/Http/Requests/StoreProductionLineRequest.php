<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta estará protegida por middleware role:admin
    }

    public function rules(): array
    {
        return [
            'code'      => ['required', 'string', 'max:30', 'unique:production_lines,code'],
            'name'      => ['required', 'string', 'max:120'],
            'line_type' => ['required', 'string', 'max:40'],
            'active'    => ['nullable', 'boolean'],
        ];
    }
}
