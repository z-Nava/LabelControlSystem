<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductionLine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductionLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta estará protegida por middleware role:admin
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:production_lines,code'],
            'name' => ['required', 'string', 'max:120'],
            'line_type' => ['required', 'string', 'max:40', Rule::in(ProductionLine::TYPES)],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
