<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $employeeNo, bool $remember = false): void
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

        if (!$user->hasRole('label_room')) {
            RateLimiter::hit($throttleKey, 60);

            $message = $user->hasRole('admin')
                ? 'Este usuario debe iniciar sesión en el acceso de administradores.'
                : 'Este usuario no cuenta con acceso operativo de Label Room.';

            throw ValidationException::withMessages([
                'employee_no' => $message,
            ]);
        }

        Auth::login($user, $remember);

        $this->finalizeLogin($throttleKey, $user, 'label_room');
    }

    public function loginAdmin(string $employeeNo, string $password, bool $remember = false): void
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

        if (!$user || !$user->hasRole('admin')) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'employee_no' => 'Usuario administrador no encontrado o inactivo.',
            ]);
        }

        $ok = Auth::attempt(
            [
                'employee_no' => $employeeNo,
                'password' => $password,
                'is_active' => 1,
            ],
            $remember
        );

        if (!$ok) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'password' => 'Credenciales inválidas.',
            ]);
        }

        $this->finalizeLogin($throttleKey, $user, 'admin');
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

    private function finalizeLogin(string $throttleKey, User $user, string $accessMode): void
    {
        RateLimiter::clear($throttleKey);
        request()->session()->regenerate();
        request()->session()->put('auth_access_mode', $accessMode);
        $user->forceFill(['last_login_at' => now()])->save();
    }
}
