<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Guest
    }

    public function rules(): array
    {
        return [
            'employee_no' => ['required', 'string', 'max:32'],
            'password'    => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_no.required' => 'El número de empleado es obligatorio.',
        ];
    }
}
