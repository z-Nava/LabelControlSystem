<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabelSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:80', 'unique:label_skus,sku'],
            'label_part_number' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:160'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
