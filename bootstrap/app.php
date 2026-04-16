<?php

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
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'role'   => \App\Http\Middleware\RequireRole::class,
            'role_any' => \App\Http\Middleware\RoleAny::class,
            'module_access' => \App\Http\Middleware\EnsureModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
