<?php

use App\Http\Middleware\EnsureKioskSession;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\RoleAny;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'kiosk.session' => EnsureKioskSession::class,
            'role' => RequireRole::class,
            'role_any' => RoleAny::class,
            'module_access' => EnsureModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
