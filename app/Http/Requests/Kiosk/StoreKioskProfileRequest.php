<?php

namespace App\Http\Requests\Kiosk;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKioskProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\pL\s\-.\x27]+$/u'],
            'production_line_id' => [
                'required',
                'integer',
                Rule::exists('production_lines', 'id')->where('active', true),
            ],
            'shift_id' => [
                'required',
                'integer',
                Rule::exists('shifts', 'id')->where('active', true),
            ],
            'position' => ['required', 'string', Rule::in(array_keys(User::PRODUCTION_POSITIONS))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        $this->merge([
            'name' => is_scalar($name)
                ? preg_replace('/\s+/u', ' ', trim(strip_tags((string) $name)))
                : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.regex' => 'El nombre contiene caracteres no permitidos.',
            'production_line_id.required' => 'Selecciona tu línea de producción.',
            'production_line_id.exists' => 'La línea seleccionada no está disponible.',
            'shift_id.required' => 'Selecciona tu turno.',
            'shift_id.exists' => 'El turno seleccionado no está disponible.',
            'position.required' => 'Selecciona tu puesto.',
            'position.in' => 'El puesto seleccionado no es válido.',
        ];
    }
}
