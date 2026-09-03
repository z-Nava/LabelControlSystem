<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'production_line_id' => ['nullable', 'exists:production_lines,id'],
            'position' => ['nullable', 'string', Rule::in(array_keys(User::PRODUCTION_POSITIONS))],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'module_permissions' => ['nullable', 'array'],
            'module_permissions.*' => [
                'required',
                'string',
                Rule::in(array_merge(User::AVAILABLE_MODULE_PERMISSIONS, User::AVAILABLE_SPECIAL_PERMISSIONS)),
            ],
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
        if (! $this->filled('roles')) {
            return false;
        }

        return Role::whereIn('id', $this->input('roles', []))
            ->where('name', 'admin')
            ->exists();
    }
}
