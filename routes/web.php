<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductionLineController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');


    // Admin-only
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/admin', function () { return 'Admin Area'; })->name('admin.home');
        Route::get('/production-lines', [ProductionLineController::class, 'index'])->name('production_lines.index');
        Route::get('/production-lines/create', [ProductionLineController::class, 'create'])->name('production_lines.create');
        Route::post('/production-lines', [ProductionLineController::class, 'store'])->name('production_lines.store');
        Route::get('/production-lines/{production_line}/edit', [ProductionLineController::class, 'edit'])->name('production_lines.edit');
        Route::put('/production-lines/{production_line}', [ProductionLineController::class, 'update'])->name('production_lines.update');
        Route::post('/production-lines/{production_line}/toggle', [ProductionLineController::class, 'toggle'])->name('production_lines.toggle');
    });

    // Label Room-only (operación)
    Route::middleware('role:label_room')->group(function () {
        Route::get('/label-room', function () {
            return 'Label Room Area';
        })->name('labelroom.home');
    });
});