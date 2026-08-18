<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockLocatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('stock_locator')?->id;

        return [
            'oracle_line' => ['required', 'string', 'max:40', "unique:stock_locators,oracle_line,{$id}"],
            'subinventory' => ['required', 'string', 'max:20'],
            'stock_locator' => ['nullable', 'string', 'max:40'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
