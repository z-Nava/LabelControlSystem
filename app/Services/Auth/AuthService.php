<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $employeeNo, ?string $password = null, bool $remember = false): void
    {
        $employeeNo = trim($employeeNo);

        $throttleKey = $this->throttleKey($employeeNo);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'employee_no' => "Demasiados intentos. Intenta de nuevo en {$seconds} segundos.",
            ]);
        }

        $user = User::with('roles')
            ->where('employee_no', $employeeNo)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'employee_no' => 'Usuario no encontrado o inactivo.',
            ]);
        }

        // Caso ADMIN → con contraseña
        if ($user->hasRole('admin')) {
            if (!$password) {
                RateLimiter::hit($throttleKey, 60);

                throw ValidationException::withMessages([
                    'password' => 'La contraseña es obligatoria.',
                ]);
            }

            $ok = Auth::attempt(
                [
                    'employee_no' => $employeeNo,
                    'password'    => $password,
                    'is_active'   => 1,
                ],
                $remember
            );

            if (!$ok) {
                RateLimiter::hit($throttleKey, 60);

                throw ValidationException::withMessages([
                    'password' => 'Credenciales inválidas.',
                ]);
            }
        } else {
            // Caso OPERADOR (label_room) → sin contraseña
            Auth::login($user, $remember);
        }

        RateLimiter::clear($throttleKey);

        request()->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();
    }

    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    private function throttleKey(string $employeeNo): string
    {
        return Str::lower(trim($employeeNo)) . '|' . request()->ip();
    }
}