<?php

use App\Models\LabelSku;
use App\Models\OracleJob;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Shift;
use App\Models\SkuSerialFormat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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

    $this->kioskUser = User::query()->create([
        'employee_no' => '31001',
        'name' => 'María Operadora',
        'password' => Hash::make('not-used-by-kiosk'),
        'shift_id' => $this->shift->id,
        'production_line_id' => $this->line->id,
        'position' => 'operator',
        'is_active' => true,
    ]);
    $this->kioskUser->roles()->attach(Role::query()->where('name', 'kiosk')->firstOrFail());

    $this->kioskSession = [
        'kiosk_user_id' => $this->kioskUser->id,
        'kiosk_employee_no' => $this->kioskUser->employee_no,
    ];
});

test('an unknown employee completes a profile on the first kiosk access', function () {
    $this->get(route('kiosk.dashboard'))
        ->assertRedirect(route('kiosk.login'));

    $this->get(route('kiosk.login'))
        ->assertOk()
        ->assertSee('minlength="3"', false)
        ->assertSee('maxlength="5"', false)
        ->assertSee('pattern="[0-9]{3,5}"', false);

    $this->post(route('kiosk.login.attempt'), [
        'employee_no' => '31002',
    ])
        ->assertRedirect(route('kiosk.register'))
        ->assertSessionHas('kiosk_pending_employee_no', '31002')
        ->assertSessionMissing('kiosk_user_id');

    $this->assertGuest();

    $this->get(route('kiosk.register'))
        ->assertOk()
        ->assertSee('Completa tu perfil')
        ->assertSee('31002')
        ->assertSee('Operadora')
        ->assertSee('Utility')
        ->assertSee('Líder');

    $this->post(route('kiosk.register.store'), [
        'name' => 'Ana Utility',
        'production_line_id' => $this->line->id,
        'shift_id' => $this->shift->id,
        'position' => 'utility',
    ])
        ->assertRedirect(route('kiosk.dashboard'))
        ->assertSessionMissing('kiosk_pending_employee_no')
        ->assertSessionHas('kiosk_employee_no', '31002');

    $registeredUser = User::query()->where('employee_no', '31002')->firstOrFail();

    expect($registeredUser->hasRole('kiosk'))->toBeTrue();
    $this->assertDatabaseHas('users', [
        'id' => $registeredUser->id,
        'employee_no' => '31002',
        'name' => 'Ana Utility',
        'production_line_id' => $this->line->id,
        'shift_id' => $this->shift->id,
        'position' => 'utility',
        'is_active' => true,
    ]);

    $this->get(route('kiosk.dashboard'))
        ->assertOk()
        ->assertSee('Ana Utility')
        ->assertSee('31002')
        ->assertSee('LINE-01')
        ->assertSee('Turno A')
        ->assertSee('Utility')
        ->assertSee('Requisición Master')
        ->assertSee('Consultar Job en Oracle');

    $this->post(route('kiosk.logout'))
        ->assertRedirect(route('kiosk.login'))
        ->assertSessionMissing('kiosk_employee_no')
        ->assertSessionMissing('kiosk_user_id');
});

test('a registered employee enters the kiosk without seeing registration again', function () {
    $this->post(route('kiosk.login.attempt'), [
        'employee_no' => $this->kioskUser->employee_no,
    ])
        ->assertRedirect(route('kiosk.dashboard'))
        ->assertSessionHas('kiosk_user_id', $this->kioskUser->id)
        ->assertSessionHas('kiosk_employee_no', $this->kioskUser->employee_no)
        ->assertSessionMissing('kiosk_pending_employee_no');

    $this->get(route('kiosk.dashboard'))
        ->assertOk()
        ->assertSee('María Operadora')
        ->assertSee('Operadora');

    expect($this->kioskUser->fresh()->last_login_at)->not->toBeNull();
});

test('kiosk registration completes an existing user without replacing other roles', function () {
    $labelRoomRole = Role::query()->create([
        'name' => 'label_room',
        'description' => 'Label Room',
    ]);
    $existingUser = User::query()->create([
        'employee_no' => '31003',
        'name' => 'Usuario existente',
        'password' => Hash::make('existing-password'),
        'is_active' => true,
    ]);
    $existingUser->roles()->attach($labelRoomRole);
    $originalPassword = $existingUser->password;

    $this->post(route('kiosk.login.attempt'), ['employee_no' => '31003'])
        ->assertRedirect(route('kiosk.register'));

    $this->post(route('kiosk.register.store'), [
        'name' => 'Usuario Actualizado',
        'production_line_id' => $this->line->id,
        'shift_id' => $this->shift->id,
        'position' => 'leader',
    ])->assertRedirect(route('kiosk.dashboard'));

    $existingUser->refresh()->load('roles');

    expect($existingUser->password)->toBe($originalPassword)
        ->and($existingUser->hasRole('label_room'))->toBeTrue()
        ->and($existingUser->hasRole('kiosk'))->toBeTrue()
        ->and($existingUser->position)->toBe('leader');
});

test('an inactive employee cannot enter or register in the kiosk', function () {
    $this->kioskUser->update(['is_active' => false]);

    $this->from(route('kiosk.login'))
        ->post(route('kiosk.login.attempt'), [
            'employee_no' => $this->kioskUser->employee_no,
        ])
        ->assertRedirect(route('kiosk.login'))
        ->assertSessionHasErrors('employee_no')
        ->assertSessionMissing('kiosk_user_id');
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
    $this->withSession($this->kioskSession)
        ->get(route('master_requests.index'))
        ->assertRedirect(route('login'));

    $this->get(route('oracle_jobs.index'))
        ->assertRedirect(route('login'));
});

test('the kiosk renders its own request views', function () {
    $this->withSession($this->kioskSession);

    $this->get(route('kiosk.master_requests.create'))
        ->assertOk()
        ->assertViewIs('kiosk.master-requests.create')
        ->assertSee('Sigue estos pasos')
        ->assertSee('esta pantalla no imprime ni entrega material')
        ->assertSee('Define folios y envía')
        ->assertSee(route('kiosk.master_requests.store'), false);

    $this->get(route('kiosk.label_requests.create'))
        ->assertOk()
        ->assertViewIs('kiosk.label-requests.create')
        ->assertSee('Sigue estos pasos')
        ->assertSee('esta pantalla no imprime ni entrega material')
        ->assertSee('Define las etiquetas')
        ->assertSee(route('kiosk.label_requests.store'), false);

    $this->get(route('kiosk.dummy_requests.create'))
        ->assertOk()
        ->assertViewIs('kiosk.dummy-requests.create')
        ->assertSee('Sigue estos pasos')
        ->assertSee('esta pantalla no imprime ni entrega material')
        ->assertSee('Indica la cantidad')
        ->assertSee(route('kiosk.dummy_requests.store'), false);
});

test('the kiosk stores the employee number on a master request', function () {
    OracleJob::query()->create([
        'job_number' => 'MASTER100',
        'assembly' => '103-TEST',
        'job_qty' => 100,
        'line' => 'LINE-01',
    ]);

    $this->withSession($this->kioskSession)
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
        'requested_by_user_id' => $this->kioskUser->id,
        'requested_by_name' => $this->kioskUser->name,
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

    $this->withSession($this->kioskSession)
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
        'requested_by_user_id' => $this->kioskUser->id,
        'requested_by_name' => $this->kioskUser->name,
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

    $this->withSession($this->kioskSession)
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
        'requested_by_user_id' => $this->kioskUser->id,
        'requested_by_name' => $this->kioskUser->name,
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

    $this->withSession($this->kioskSession)
        ->get(route('kiosk.oracle_jobs.lookup', ['job_number' => 'oracle100']))
        ->assertOk()
        ->assertSee('ORACLE100')
        ->assertSee('018-TEST')
        ->assertSee('Producto de prueba')
        ->assertDontSee('Importar archivo');
});
