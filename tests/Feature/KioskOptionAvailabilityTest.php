<?php

use App\Models\KioskOptionStatus;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Kiosk\KioskOptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();

    $shift = Shift::query()->create(['code' => 'T1', 'name' => 'Turno 1', 'active' => true]);
    $line = ProductionLine::query()->create([
        'code' => 'TEST01',
        'name' => 'Línea de prueba',
        'line_type' => ProductionLine::TYPE_CONSOLES,
        'active' => true,
    ]);

    $this->admin = User::query()->create([
        'employee_no' => 'ADMIN-KIOSK-TEST',
        'name' => 'Administrador',
        'password' => 'password',
        'is_active' => true,
    ]);
    $this->admin->roles()->attach(Role::query()->firstOrCreate(['name' => 'admin']));

    $this->kioskUser = User::query()->create([
        'employee_no' => 'KIOSK-TEST',
        'name' => 'Operador',
        'password' => 'password',
        'is_active' => true,
        'shift_id' => $shift->id,
        'production_line_id' => $line->id,
        'position' => 'operator',
    ]);
    $this->kioskUser->roles()->attach(Role::query()->firstOrCreate(['name' => 'kiosk']));
});

test('admin manages only available and unavailable states in a dedicated page', function (): void {
    $this->actingAs($this->admin)->withSession(['auth_access_mode' => 'admin']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('admin.kiosk_options.index').'"', false)
        ->assertDontSee('name="status"', false);

    $this->get(route('admin.kiosk_options.index'))
        ->assertOk()
        ->assertSee('Disponible')
        ->assertSee('No disponible')
        ->assertDontSee('Próximamente')
        ->assertDontSee('Trabajando en ello');

    $this->put(route('admin.kiosk_options.update', 'master'), ['status' => 'unavailable'])
        ->assertRedirect(route('admin.kiosk_options.index'));

    expect(KioskOptionStatus::query()->whereKey('master')->value('status'))->toBe('unavailable');
    expect(app(KioskOptionService::class)->status('master'))->toBe(KioskOptionService::UNAVAILABLE);

    $this->get(route('admin.kiosk_options.index'))
        ->assertOk()
        ->assertSee('value="unavailable" selected', false);
});

test('disabled kiosk cards show no disponible and direct requests are blocked', function (): void {
    KioskOptionStatus::query()->create(['option_key' => 'master', 'status' => 'unavailable']);

    $this->withSession([
        'kiosk_user_id' => $this->kioskUser->id,
        'kiosk_employee_no' => $this->kioskUser->employee_no,
    ]);

    $this->get(route('kiosk.dashboard'))
        ->assertOk()
        ->assertSee('No disponible')
        ->assertDontSee('href="'.route('kiosk.master_requests.create').'"', false)
        ->assertSee('href="'.route('kiosk.label_requests.create').'"', false);

    $this->get(route('kiosk.master_requests.create'))
        ->assertRedirect(route('kiosk.dashboard'))
        ->assertSessionHas('kiosk_unavailable', 'Requisición Master: No disponible.');

    $this->post(route('kiosk.master_requests.store'), [])
        ->assertRedirect(route('kiosk.dashboard'));

    $this->getJson(route('kiosk.master_requests.lookup_job'))
        ->assertStatus(503)
        ->assertJsonPath('message', 'Requisición Master: No disponible.');
});

test('only admins may update settings and only the two supported values are accepted', function (): void {
    $this->actingAs($this->kioskUser)
        ->get(route('admin.kiosk_options.index'))
        ->assertForbidden();

    $this->actingAs($this->kioskUser)
        ->put(route('admin.kiosk_options.update', 'master'), ['status' => 'unavailable'])
        ->assertForbidden();

    $this->actingAs($this->admin)
        ->withSession(['auth_access_mode' => 'admin'])
        ->put(route('admin.kiosk_options.update', 'master'), ['status' => 'coming_soon'])
        ->assertSessionHasErrors('status');

    $this->put(route('admin.kiosk_options.update', 'unknown'), ['status' => 'available'])
        ->assertNotFound();

    expect(KioskOptionStatus::query()->whereKey('master')->exists())->toBeFalse();
});

test('restoring available removes the saved override', function (): void {
    KioskOptionStatus::query()->create(['option_key' => 'receipts', 'status' => 'unavailable']);

    $this->actingAs($this->admin)
        ->withSession(['auth_access_mode' => 'admin'])
        ->put(route('admin.kiosk_options.update', 'receipts'), ['status' => 'available'])
        ->assertRedirect(route('admin.kiosk_options.index'));

    expect(KioskOptionStatus::query()->whereKey('receipts')->exists())->toBeFalse();
    expect(app(KioskOptionService::class)->status('receipts'))->toBe(KioskOptionService::AVAILABLE);
});

test('older blocked values remain blocked and render as no disponible', function (): void {
    KioskOptionStatus::query()->create(['option_key' => 'master', 'status' => 'coming_soon']);
    KioskOptionStatus::query()->create(['option_key' => 'labels', 'status' => 'in_progress']);

    expect(app(KioskOptionService::class)->all())->toMatchArray([
        'master' => KioskOptionService::UNAVAILABLE,
        'labels' => KioskOptionService::UNAVAILABLE,
    ]);

    $this->withSession([
        'kiosk_user_id' => $this->kioskUser->id,
        'kiosk_employee_no' => $this->kioskUser->employee_no,
    ])->get(route('kiosk.master_requests.create'))
        ->assertRedirect(route('kiosk.dashboard'))
        ->assertSessionHas('kiosk_unavailable', 'Requisición Master: No disponible.');
});

test('each of the six kiosk choices blocks its entry route when disabled', function (): void {
    $this->withSession([
        'kiosk_user_id' => $this->kioskUser->id,
        'kiosk_employee_no' => $this->kioskUser->employee_no,
    ]);

    foreach ([
        'master' => 'kiosk.master_requests.create',
        'labels' => 'kiosk.label_requests.create',
        'lpk' => 'kiosk.lpk_label_requests.create',
        'dummy' => 'kiosk.dummy_requests.create',
        'oracle' => 'kiosk.oracle_jobs.index',
        'receipts' => 'kiosk.receipts.index',
    ] as $option => $route) {
        KioskOptionStatus::query()->create(['option_key' => $option, 'status' => 'unavailable']);

        $this->get(route($route))
            ->assertRedirect(route('kiosk.dashboard'))
            ->assertSessionHas('kiosk_unavailable', KioskOptionService::OPTIONS[$option].': No disponible.');

        KioskOptionStatus::query()->whereKey($option)->delete();
    }
});
