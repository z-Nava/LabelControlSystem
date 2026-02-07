<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $this->authService->login(
            employeeNo: $request->string('employee_no')->toString(),
            password: $request->input('password'),
            remember: (bool) $request->boolean('remember')
        );

        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('dashboard'); // tu dashboard ya decide vista admin
        }

        return redirect()->route('dashboard'); // igual, el dashboard decide label_room
    }


    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('login');
    }
}
