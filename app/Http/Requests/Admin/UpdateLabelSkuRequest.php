<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabelSkuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('label_sku')?->id ?? null;

        return [
            'sku' => ['required', 'string', 'max:80', "unique:label_skus,sku,{$id}"],
            'label_part_number' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:160'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
