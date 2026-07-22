<?php

use App\Models\LabelSku;
use App\Models\OracleJob;
use App\Models\ProductionLine;
use App\Models\Shift;
use App\Models\SkuSerialFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->shift = Shift::query()->create([
        'code' => 'A',
        'name' => 'Turno A',
        'active' => true,
    ]);

    $this->line = ProductionLine::query()->create([
        'code' => 'LINE-01',
        'name' => 'Línea de prueba',
        'line_type' => 'EMPAQUE',
        'active' => true,
    ]);
});

test('an employee number starts a temporary kiosk session without a user', function () {
    $this->get(route('kiosk.dashboard'))
        ->assertRedirect(route('kiosk.login'));

    $this->get(route('kiosk.login'))
        ->assertOk()
        ->assertSee('minlength="3"', false)
        ->assertSee('maxlength="5"', false)
        ->assertSee('pattern="[0-9]{3,5}"', false);

    $this->post(route('kiosk.login.attempt'), [
        'employee_no' => '31001',
    ])
        ->assertRedirect(route('kiosk.dashboard'))
        ->assertSessionHas('kiosk_employee_no', '31001');

    $this->assertGuest();

    $this->get(route('kiosk.dashboard'))
        ->assertOk()
        ->assertSee('Número de empleado: 31001')
        ->assertSee('Requisición Master')
        ->assertSee('Consultar Job en Oracle');

    $this->post(route('kiosk.logout'))
        ->assertRedirect(route('kiosk.login'))
        ->assertSessionMissing('kiosk_employee_no');
});

test('the employee number must contain only three to five digits', function (string $employeeNo) {
    $this->from(route('kiosk.login'))
        ->post(route('kiosk.login.attempt'), ['employee_no' => $employeeNo])
        ->assertRedirect(route('kiosk.login'))
        ->assertSessionHasErrors('employee_no')
        ->assertSessionMissing('kiosk_employee_no');
})->with([
    'too short' => '12',
    'too long' => '123456',
    'letters' => '12A4',
    'symbols' => '12.4',
    'html' => '<123>',
]);

test('a kiosk session cannot access label room operations', function () {
    $this->withSession(['kiosk_employee_no' => '31001'])
        ->get(route('master_requests.index'))
        ->assertRedirect(route('login'));

    $this->get(route('oracle_jobs.index'))
        ->assertRedirect(route('login'));
});

test('the kiosk renders its own request views', function () {
    $this->withSession(['kiosk_employee_no' => '31001']);

    $this->get(route('kiosk.master_requests.create'))
        ->assertOk()
        ->assertViewIs('kiosk.master-requests.create')
        ->assertSee(route('kiosk.master_requests.store'), false);

    $this->get(route('kiosk.label_requests.create'))
        ->assertOk()
        ->assertViewIs('kiosk.label-requests.create')
        ->assertSee(route('kiosk.label_requests.store'), false);

    $this->get(route('kiosk.dummy_requests.create'))
        ->assertOk()
        ->assertViewIs('kiosk.dummy-requests.create')
        ->assertSee(route('kiosk.dummy_requests.store'), false);
});

test('the kiosk stores the employee number on a master request', function () {
    OracleJob::query()->create([
        'job_number' => 'MASTER100',
        'assembly' => '103-TEST',
        'job_qty' => 100,
        'line' => 'LINE-01',
    ]);

    $this->withSession(['kiosk_employee_no' => '31001'])
        ->post(route('kiosk.master_requests.store'), [
            'request_date' => now()->toDateString(),
            'week' => now()->isoWeek(),
            'line_id' => $this->line->id,
            'shift_id' => $this->shift->id,
            'leader_name' => 'Líder Prueba',
            'job_assembly' => 'MASTER100',
            'folios_from' => 1,
            'folios_to' => 2,
            'std_pack_qty' => 10,
            'request_type' => 'assembly',
            'kind' => 'new',
        ])
        ->assertRedirect(route('kiosk.dashboard'))
        ->assertSessionHas('kiosk_receipt.request_id', 1);

    $this->assertDatabaseHas('master_requests', [
        'id' => 1,
        'requested_by_user_id' => null,
        'requested_by_name' => '31001',
        'leader_name' => 'Líder Prueba',
        'status' => 'requested',
    ]);
    $this->assertDatabaseCount('master_request_folios', 2);
});

test('the kiosk stores the employee number on a label request', function () {
    LabelSku::query()->create([
        'sku' => 'SKU-TEST',
        'serial_standard' => 'UL',
        'label_part_number' => 'LBL-TEST',
        'is_active' => true,
    ]);

    SkuSerialFormat::query()->create([
        'sku' => 'SKU-TEST',
        'serial_standard' => 'UL',
        'serial_scheme' => 'ul_standard',
        'is_active' => true,
    ]);

    $this->withSession(['kiosk_employee_no' => '31001'])
        ->post(route('kiosk.label_requests.store'), [
            'request_date' => now()->toDateString(),
            'week' => now()->isoWeek(),
            'line_id' => $this->line->id,
            'shift_id' => $this->shift->id,
            'leader_name' => 'Líder Prueba',
            'serial_standard' => 'UL',
            'label_part_number' => 'LBL-TEST',
            'quantity_requested' => 25,
            'include_serial' => true,
        ])
        ->assertRedirect(route('kiosk.dashboard'))
        ->assertSessionHas('kiosk_receipt.request_id', 1);

    $this->assertDatabaseHas('label_requests', [
        'id' => 1,
        'requested_by_user_id' => null,
        'requested_by_name' => '31001',
        'leader_name' => 'Líder Prueba',
        'quantity_requested' => 25,
        'status' => 'requested',
    ]);
});

test('the kiosk stores the employee number on a dummy request', function () {
    OracleJob::query()->create([
        'job_number' => 'DUMMY100',
        'assembly' => 'FG-TEST',
        'job_qty' => 10,
    ]);

    $this->withSession(['kiosk_employee_no' => '31001'])
        ->post(route('kiosk.dummy_requests.store'), [
            'request_date' => now()->toDateString(),
            'week' => now()->isoWeek(),
            'line_id' => $this->line->id,
            'shift_id' => $this->shift->id,
            'leader_name' => 'Líder Prueba',
            'job_number' => 'DUMMY100',
            'quantity_requested' => 3,
            'request_type' => 'first_time',
        ])
        ->assertRedirect(route('kiosk.dashboard'))
        ->assertSessionHas('kiosk_receipt.request_id', 1);

    $this->assertDatabaseHas('dummy_requests', [
        'id' => 1,
        'requested_by_user_id' => null,
        'requested_by_name' => '31001',
        'leader_name' => 'Líder Prueba',
        'range_from' => 1,
        'range_to' => 3,
        'status' => 'requested',
    ]);
    $this->assertDatabaseCount('dummy_request_items', 3);
});

test('the oracle kiosk card only displays imported job information', function () {
    OracleJob::query()->create([
        'job_number' => 'ORACLE100',
        'job_status' => 'Released',
        'assembly' => '018-TEST',
        'part_description' => 'Producto de prueba',
        'job_qty' => 50,
        'imported_at' => now(),
    ]);

    $this->withSession(['kiosk_employee_no' => '31001'])
        ->get(route('kiosk.oracle_jobs.lookup', ['job_number' => 'oracle100']))
        ->assertOk()
        ->assertSee('ORACLE100')
        ->assertSee('018-TEST')
        ->assertSee('Producto de prueba')
        ->assertDontSee('Importar archivo');
});
