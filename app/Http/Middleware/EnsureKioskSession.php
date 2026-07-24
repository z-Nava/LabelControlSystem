<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureKioskSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('kiosk_user_id');
        $employeeNo = $request->session()->get('kiosk_employee_no');

        if (! is_int($userId) || ! is_string($employeeNo)) {
            return $this->reject($request);
        }

        $user = User::query()
            ->with(['productionLine', 'roles', 'shift'])
            ->find($userId);

        if (
            ! $user
            || ! $user->is_active
            || $user->employee_no !== $employeeNo
            || ! $user->isRegisteredForKiosk()
        ) {
            return $this->reject($request);
        }

        $request->attributes->set('kiosk_user', $user);
        View::share('kioskUser', $user);

        return $next($request);
    }

    private function reject(Request $request): Response
    {
        $request->session()->forget([
            'kiosk_user_id',
            'kiosk_employee_no',
            'kiosk_pending_employee_no',
        ]);

        return redirect()->route('kiosk.login');
    }
}
