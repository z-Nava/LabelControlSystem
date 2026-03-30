<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockLocatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_locator' => ['required', 'string', 'max:40', 'unique:stock_locators,stock_locator'],
            'subinventory' => ['required', 'string', 'max:20'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
