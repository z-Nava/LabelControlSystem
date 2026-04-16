<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_no' => ['required', 'string', 'max:32', 'unique:users,employee_no'],
            'name' => ['required', 'string', 'max:120'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'module_permissions' => ['nullable', 'array'],
            'module_permissions.*' => ['required', 'string', 'in:master,labels,dummy,oracle'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'module_permissions' => array_values(array_unique($this->input('module_permissions', []))),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->requiresPassword() && empty($this->input('password'))) {
                $validator->errors()->add('password', 'La contraseña es obligatoria para usuarios con rol admin.');
            }
        });
    }

    private function requiresPassword(): bool
    {
        if (!$this->filled('roles')) {
            return false;
        }

        return \App\Models\Role::whereIn('id', $this->input('roles', []))
            ->where('name', 'admin')
            ->exists();
    }
}
