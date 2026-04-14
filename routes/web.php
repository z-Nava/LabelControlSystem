<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Admin\ProductionLineController;
use App\Http\Controllers\Admin\LabelSkuController;
use App\Http\Controllers\Labels\LabelPrintController;
use App\Http\Controllers\Labels\LabelRequestController;
use App\Http\Controllers\Labels\LabelReworkController;
use App\Http\Controllers\Dummies\DummyRequestController;
use App\Http\Controllers\Oracle\OracleJobController;
use App\Http\Controllers\Masters\MasterRequestController;
use App\Http\Controllers\Masters\MasterPrintController;
use App\Http\Controllers\Masters\MasterReprintController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SkuSerialFormatController;
use App\Http\Controllers\Admin\SkuTemplateConfigurationController;
use App\Http\Controllers\Admin\StockLocatorController;
use App\Http\Controllers\Admin\DummyQrTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', function () { return redirect()->route('login'); });
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

        Route::get('/stock-locators', [StockLocatorController::class, 'index'])->name('stock_locators.index');
        Route::get('/stock-locators/create', [StockLocatorController::class, 'create'])->name('stock_locators.create');
        Route::post('/stock-locators', [StockLocatorController::class, 'store'])->name('stock_locators.store');
        Route::get('/stock-locators/{stock_locator}/edit', [StockLocatorController::class, 'edit'])->name('stock_locators.edit');
        Route::put('/stock-locators/{stock_locator}', [StockLocatorController::class, 'update'])->name('stock_locators.update');
        Route::post('/stock-locators/{stock_locator}/toggle', [StockLocatorController::class, 'toggle'])->name('stock_locators.toggle');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/label-skus', [LabelSkuController::class, 'index'])->name('label_skus.index');
        Route::get('/label-skus/create', [LabelSkuController::class, 'create'])->name('label_skus.create');
        Route::post('/label-skus', [LabelSkuController::class, 'store'])->name('label_skus.store');
        Route::get('/label-skus/{label_sku}/edit', [LabelSkuController::class, 'edit'])->name('label_skus.edit');
        Route::put('/label-skus/{label_sku}', [LabelSkuController::class, 'update'])->name('label_skus.update');
        Route::post('/label-skus/{label_sku}/toggle', [LabelSkuController::class, 'toggle'])->name('label_skus.toggle');

        Route::get('/sku-serial-formats', [SkuSerialFormatController::class, 'index'])->name('sku_serial_formats.index');
        Route::get('/sku-serial-formats/create', [SkuSerialFormatController::class, 'create'])->name('sku_serial_formats.create');
        Route::post('/sku-serial-formats', [SkuSerialFormatController::class, 'store'])->name('sku_serial_formats.store');
        Route::get('/sku-serial-formats/{sku_serial_format}/edit', [SkuSerialFormatController::class, 'edit'])->name('sku_serial_formats.edit');
        Route::put('/sku-serial-formats/{sku_serial_format}', [SkuSerialFormatController::class, 'update'])->name('sku_serial_formats.update');
        Route::post('/sku-serial-formats/{sku_serial_format}/toggle', [SkuSerialFormatController::class, 'toggle'])->name('sku_serial_formats.toggle');


        Route::get('/sku-template-configurations', [SkuTemplateConfigurationController::class, 'index'])->name('admin.sku_template_configurations.index');
        Route::get('/sku-template-configurations/create', [SkuTemplateConfigurationController::class, 'create'])->name('admin.sku_template_configurations.create');
        Route::post('/sku-template-configurations', [SkuTemplateConfigurationController::class, 'store'])->name('admin.sku_template_configurations.store');
        Route::get('/sku-template-configurations/{configuration}/edit', [SkuTemplateConfigurationController::class, 'edit'])->name('admin.sku_template_configurations.edit');
        Route::put('/sku-template-configurations/{configuration}', [SkuTemplateConfigurationController::class, 'update'])->name('admin.sku_template_configurations.update');
        Route::post('/sku-template-configurations/{configuration}/toggle', [SkuTemplateConfigurationController::class, 'toggle'])->name('admin.sku_template_configurations.toggle');


        Route::get('/dummy-qr-templates', [DummyQrTemplateController::class, 'index'])->name('admin.dummy_qr_templates.index');
        Route::get('/dummy-qr-templates/create', [DummyQrTemplateController::class, 'create'])->name('admin.dummy_qr_templates.create');
        Route::post('/dummy-qr-templates', [DummyQrTemplateController::class, 'store'])->name('admin.dummy_qr_templates.store');
        Route::get('/dummy-qr-templates/{dummy_qr_template}/edit', [DummyQrTemplateController::class, 'edit'])->name('admin.dummy_qr_templates.edit');
        Route::put('/dummy-qr-templates/{dummy_qr_template}', [DummyQrTemplateController::class, 'update'])->name('admin.dummy_qr_templates.update');
        Route::post('/dummy-qr-templates/{dummy_qr_template}/toggle', [DummyQrTemplateController::class, 'toggle'])->name('admin.dummy_qr_templates.toggle');


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
        Route::get('/master-requests/{master_request}/reprints', [MasterReprintController::class, 'index'])->name('master_requests.reprints.index');
        Route::get('/master-reprints', [MasterReprintController::class, 'search'])->name('master_reprints.search');
        Route::get('/master-print-batches/{batch}/pdf', [MasterPrintController::class, 'pdf'])->name('master_print_batches.pdf');

        Route::get('/master-print-batches/{batch}/print', [MasterPrintController::class, 'print'])->name('master_print_batches.print');
        Route::get('/oracle/lookup-job', [MasterRequestController::class, 'lookup'])->name('oracle.lookup_job');
        Route::get('/label-requests/lookup-job', [LabelRequestController::class, 'lookup'])->name('label_requests.lookup_job');

        Route::get('/dummy-requests/lookup-job', [DummyRequestController::class, 'lookup'])->name('dummy_requests.lookup_job');

        Route::get('/dummy-requests', [DummyRequestController::class, 'index'])->name('dummy_requests.index');
        Route::get('/dummy-requests/create', [DummyRequestController::class, 'create'])->name('dummy_requests.create');
        Route::post('/dummy-requests', [DummyRequestController::class, 'store'])->name('dummy_requests.store');
        Route::get('/dummy-requests/{id}', [DummyRequestController::class, 'show'])->name('dummy_requests.show');

        Route::get('/label-requests', [LabelRequestController::class, 'index'])->name('label_requests.index');
        Route::get('/label-requests/create', [LabelRequestController::class, 'create'])->name('label_requests.create');
        Route::post('/label-requests', [LabelRequestController::class, 'store'])->name('label_requests.store');
        Route::get('/label-requests/{id}', [LabelRequestController::class, 'show'])->name('label_requests.show');
        Route::post('/label-requests/{label_request}/cancel', [LabelRequestController::class, 'cancel'])->name('label_requests.cancel');
        Route::post('/label-requests/{label_request}/complete', [LabelRequestController::class, 'complete'])->name('label_requests.complete');

        Route::get('/label-requests/{label_request}/print', [LabelPrintController::class, 'create'])->name('label_requests.print.create');
        Route::post('/label-requests/{label_request}/print', [LabelPrintController::class, 'store'])->name('label_requests.print.store');
        Route::get('/label-requests/{label_request}/print-batches/{batch}/print', [LabelPrintController::class, 'printCenter'])->name('label_requests.print_batches.print');
        Route::post('/label-requests/{label_request}/print-batches/{batch}/preview', [LabelPrintController::class, 'preview'])->name('label_requests.print_batches.preview');
        Route::post('/label-requests/{label_request}/print-batches/{batch}/confirm', [LabelPrintController::class, 'confirm'])->name('label_requests.print_batches.confirm');

        Route::get('/label-reworks', [LabelReworkController::class, 'search'])->name('label_reworks.search');
        Route::get('/label-reworks/{label_request}', [LabelReworkController::class, 'show'])->name('label_reworks.show');
        Route::post('/label-reworks/{label_request}/reprint', [LabelReworkController::class, 'store'])->name('label_reworks.store');


    });

    Route::middleware('role_any:admin,label_room')->group(function () {
        Route::get('/oracle-jobs', [OracleJobController::class, 'index'])->name('oracle_jobs.index');
        Route::get('/oracle-jobs/import', [OracleJobController::class, 'importView'])->name('oracle_jobs.import_view');
        Route::post('/oracle-jobs/import', [OracleJobController::class, 'import'])->name('oracle_jobs.import');
    });

});
