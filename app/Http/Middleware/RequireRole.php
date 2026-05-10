<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole($role)) {
            abort(403);
        }

        if ($role === 'admin' && $request->session()->get('auth_access_mode') !== 'admin') {
            abort(403);
        }

        return $next($request);
    }
}
