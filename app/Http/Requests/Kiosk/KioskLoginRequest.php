<?php

namespace App\Http\Requests\Kiosk;

use Illuminate\Foundation\Http\FormRequest;

class KioskLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_no' => ['required', 'string', 'regex:/^\d{3,5}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $employeeNo = $this->input('employee_no');

        $this->merge([
            'employee_no' => is_scalar($employeeNo)
                ? trim(strip_tags((string) $employeeNo))
                : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'employee_no.required' => 'El número de empleado es obligatorio.',
            'employee_no.regex' => 'El número de empleado debe contener únicamente de 3 a 5 dígitos.',
        ];
    }
}
