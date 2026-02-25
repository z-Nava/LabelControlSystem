<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'employee_no' => ['required', 'string', 'max:32', Rule::unique('users', 'employee_no')->ignore($userId)],
            'name' => ['required', 'string', 'max:120'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->requiresPassword() && empty($this->input('password')) && !$this->route('user')->hasRole('admin')) {
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
