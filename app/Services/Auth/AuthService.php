<?php

namespace App\Services\Auth;

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

        // 🔹 Caso OPERADOR (label_room) → sin contraseña
        if ($user->hasRole('label_room')) {
            Auth::login($user, $remember);
        }
        // 🔹 Caso ADMIN → con contraseña
        else {
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
        }

        RateLimiter::clear($throttleKey);

        request()->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();
    }

}