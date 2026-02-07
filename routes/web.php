<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');


    // Admin-only
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', function () {
            return 'Admin Area';
        })->name('admin.home');
    });

    // Label Room-only (operación)
    Route::middleware('role:label_room')->group(function () {
        Route::get('/label-room', function () {
            return 'Label Room Area';
        })->name('labelroom.home');
    });
});