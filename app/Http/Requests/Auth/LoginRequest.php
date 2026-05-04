<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    private const NO_HTML_PATTERN = '/<[^>]*>/';

    public function authorize(): bool
    {
        return true; // Guest
    }

    public function rules(): array
    {
        $passwordRules = ['nullable', 'string', 'min:8', 'max:72', 'not_regex:' . self::NO_HTML_PATTERN];

        if ($this->routeIs('admin.login.attempt')) {
            $passwordRules[0] = 'required';
        }

        return [
            'employee_no' => ['required', 'string', 'min:1', 'max:10', 'regex:/^\d+$/', 'not_regex:' . self::NO_HTML_PATTERN],
            'password'    => $passwordRules,
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_no' => $this->cleanInput($this->input('employee_no', '')),
            'password' => $this->cleanInput($this->input('password', '')),
        ]);
    }

    public function messages(): array
    {
        return [
            'employee_no.required' => 'El número de empleado es obligatorio.',
            'employee_no.regex' => 'El número de empleado solo debe contener dígitos.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    private function cleanInput(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $cleaned = trim(strip_tags((string) $value));

        return $cleaned === '' ? null : $cleaned;
    }
}
