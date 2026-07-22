<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kiosk\KioskLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KioskSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (preg_match('/^\d{3,5}$/', (string) $request->session()->get('kiosk_employee_no'))) {
            return redirect()->route('kiosk.dashboard');
        }

        return view('auth.kiosk-login');
    }

    public function store(KioskLoginRequest $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put(
            'kiosk_employee_no',
            $request->string('employee_no')->toString(),
        );

        return redirect()->route('kiosk.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('kiosk.login');
    }
}
