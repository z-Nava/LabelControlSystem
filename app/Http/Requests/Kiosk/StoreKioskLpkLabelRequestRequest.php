<?php

namespace App\Http\Requests\Kiosk;

use Illuminate\Validation\Rule;

class StoreKioskLpkLabelRequestRequest extends StoreKioskLabelRequestRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $requiresShippingItems = fn (): bool => $this->boolean('include_shipping');

        return [
            ...parent::rules(),
            'shipping_items' => [Rule::requiredIf($requiresShippingItems), 'array'],
            'shipping_items.*' => ['required', 'string', 'max:120', 'distinct:ignore_case'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $shippingItems = $this->normalizeStringList($this->input('shipping_items', []));

        $this->merge([
            'shipping_items' => $this->boolean('include_shipping') ? $shippingItems : [],
        ]);
    }

    public function attributes(): array
    {
        return [
            'shipping_items' => 'modelos o herramientas de Shipping',
            'shipping_items.*' => 'modelo o herramienta de Shipping',
        ];
    }
}
