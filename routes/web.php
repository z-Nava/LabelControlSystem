<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductionLineController;
use App\Http\Controllers\OracleJobController;
use App\Http\Controllers\MasterRequestController;
use App\Http\Controllers\MasterPrintController;
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
        Route::get('/label-room', function () { return 'Label Room Area'; })->name('labelroom.home');

        Route::get('/master-requests', [MasterRequestController::class, 'index'])->name('master_requests.index');
        Route::get('/master-requests/create', [MasterRequestController::class, 'create'])->name('master_requests.create');
        Route::post('/master-requests', [MasterRequestController::class, 'store'])->name('master_requests.store');
        Route::get('/master-requests/{id}', [MasterRequestController::class, 'show'])->name('master_requests.show');

        Route::get('/master-requests/{master_request}/print', [MasterPrintController::class, 'create'])->name('master_requests.print.create');
        Route::post('/master-requests/{master_request}/print', [MasterPrintController::class, 'store'])->name('master_requests.print.store');
        Route::get('/master-print-batches/{batch}/pdf', [MasterPrintController::class, 'pdf'])->name('master_print_batches.pdf');

        Route::get('/master-print-batches/{batch}/print', [MasterPrintController::class, 'print'])->name('master_print_batches.print');
        Route::get('/oracle/lookup-job', [MasterRequestController::class, 'lookup'])->name('oracle.lookup_job');


    });

    Route::middleware('role_any:admin,label_room')->group(function () {
        Route::get('/oracle-jobs', [OracleJobController::class, 'index'])->name('oracle_jobs.index');
        Route::get('/oracle-jobs/import', [OracleJobController::class, 'importView'])->name('oracle_jobs.import_view');
        Route::post('/oracle-jobs/import', [OracleJobController::class, 'import'])->name('oracle_jobs.import');
    });

});