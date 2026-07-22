<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKioskSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $employeeNo = $request->session()->get('kiosk_employee_no');

        if (! is_string($employeeNo) || ! preg_match('/^\d{3,5}$/', $employeeNo)) {
            $request->session()->forget('kiosk_employee_no');

            return redirect()->route('kiosk.login');
        }

        return $next($request);
    }
}
