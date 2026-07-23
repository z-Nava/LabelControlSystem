<?php

use App\Models\DummyRequest;
use App\Models\LabelRequest;
use App\Models\MasterRequest;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Shift;
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

    $labelRoomRole = Role::query()->create([
        'name' => 'label_room',
        'description' => 'Personal de Label Room',
    ]);

    $this->user = User::query()->create([
        'employee_no' => '10001',
        'name' => 'Operador Label Room',
        'password' => Hash::make('password'),
        'is_active' => true,
    ]);
    $this->user->roles()->attach($labelRoomRole);

    $this->actingAs($this->user)
        ->withSession(['auth_access_mode' => 'label_room']);
});

function createPendingCountFixtures(Shift $shift, ProductionLine $line): void
{
    $masterPayload = [
        'request_date' => now()->toDateString(),
        'week' => now()->isoWeek(),
        'line_id' => $line->id,
        'shift_id' => $shift->id,
        'leader_name' => 'Líder Master',
        'requested_by_name' => '31001',
        'request_type' => 'assembly',
        'kind' => 'new',
    ];

    MasterRequest::query()->create([...$masterPayload, 'status' => 'requested']);
    MasterRequest::query()->create([...$masterPayload, 'status' => 'requested']);
    MasterRequest::query()->create([...$masterPayload, 'status' => 'in_progress']);

    $labelPayload = [
        'request_date' => now()->toDateString(),
        'week' => now()->isoWeek(),
        'line_id' => $line->id,
        'shift_id' => $shift->id,
        'leader_name' => 'Líder Etiquetas',
        'requested_by_name' => '31002',
        'label_part_number' => 'LBL-TEST',
        'serial_standard' => 'UL',
        'quantity_requested' => 10,
    ];

    LabelRequest::query()->create([...$labelPayload, 'status' => 'requested']);
    LabelRequest::query()->create([...$labelPayload, 'status' => 'completed']);

    $dummyPayload = [
        'request_date' => now()->toDateString(),
        'week' => now()->isoWeek(),
        'line_id' => $line->id,
        'shift_id' => $shift->id,
        'leader_name' => 'Líder Dummy',
        'requested_by_name' => '31003',
        'job_number' => 'DUMMY100',
        'fg_code' => 'FG-TEST',
        'quantity_requested' => 3,
        'request_type' => 'first_time',
    ];

    DummyRequest::query()->create([...$dummyPayload, 'status' => 'requested']);
    DummyRequest::query()->create([...$dummyPayload, 'status' => 'requested']);
    DummyRequest::query()->create([...$dummyPayload, 'status' => 'requested']);
    DummyRequest::query()->create([...$dummyPayload, 'status' => 'in_progress']);
}

test('label room dashboard counts only requested requisitions', function () {
    createPendingCountFixtures($this->shift, $this->line);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboards.label_room')
        ->assertViewHas('pendingRequestCounts', [
            'master' => 2,
            'labels' => 1,
            'dummy' => 3,
        ])
        ->assertSee('Requisiciones Dummy QR pendientes')
        ->assertDontSee('Historial Dummy QR')
        ->assertSee('data-pending-request-count="master"', false)
        ->assertSee('data-pending-request-count="labels"', false)
        ->assertSee('data-pending-request-count="dummy"', false);
});

test('pending request count endpoint returns fresh requested totals', function () {
    createPendingCountFixtures($this->shift, $this->line);

    $this->getJson(route('dashboard.pending_request_counts'))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertExactJson([
            'counts' => [
                'master' => 2,
                'labels' => 1,
                'dummy' => 3,
            ],
        ]);

    DummyRequest::query()
        ->where('status', DummyRequest::STATUS_REQUESTED)
        ->firstOrFail()
        ->update(['status' => DummyRequest::STATUS_IN_PROGRESS]);

    $this->getJson(route('dashboard.pending_request_counts'))
        ->assertOk()
        ->assertJsonPath('counts.dummy', 2);
});

test('pending totals respect label room module permissions', function () {
    createPendingCountFixtures($this->shift, $this->line);
    $this->user->update(['module_permissions' => ['dummy']]);

    $this->getJson(route('dashboard.pending_request_counts'))
        ->assertOk()
        ->assertExactJson([
            'counts' => [
                'master' => 0,
                'labels' => 0,
                'dummy' => 3,
            ],
        ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Requisiciones de etiquetas pendientes')
        ->assertSee('Requisiciones Dummy QR pendientes');
});

test('users without the label room role cannot read pending totals', function () {
    $this->user->roles()->detach();

    $this->getJson(route('dashboard.pending_request_counts'))
        ->assertForbidden();
});
