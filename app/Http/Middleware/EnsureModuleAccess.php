<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module)
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        if (!$user->hasModuleAccess($module)) {
            abort(403);
        }

        return $next($request);
    }
}
