<?php

use App\Models\KioskRequisitionPrintJob;
use App\Models\LabelRequest;
use App\Models\MasterModelMapping;
use App\Models\OracleJob;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Labels\LabelFolioService;
use App\Services\Labels\LabelRoomAdministrationService;
use App\Services\Labels\LabelWorkDefinitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->line = ProductionLine::query()->create([
        'code' => 'LR-TEST', 'name' => 'Test line', 'line_type' => 'consoles', 'active' => true,
    ]);
    $this->shift = Shift::query()->create(['code' => 'TEST', 'name' => 'Test shift']);
    $this->operator = User::query()->create([
        'employee_no' => 'LR-TEST',
        'name' => 'Label Room Operator',
        'password' => Hash::make('password'),
        'is_active' => true,
        'module_permissions' => ['master', 'labels', 'dummy', 'oracle'],
    ]);
    $this->operator->roles()->attach(Role::query()->firstOrCreate(['name' => 'label_room']));
    $this->actingAs($this->operator)->withSession(['auth_access_mode' => 'label_room']);
});

function labelRoomControlRequest(array $attributes = []): LabelRequest
{
    return LabelRequest::query()->create(array_replace([
        'request_kind' => LabelRequest::KIND_STANDARD,
        'request_date' => now()->toDateString(),
        'week' => (int) now()->isoWeek(),
        'line_id' => test()->line->id,
        'shift_id' => test()->shift->id,
        'leader_name' => 'Test Leader',
        'requested_by_name' => 'Test Requester',
        'requested_by_user_id' => test()->operator->id,
        'label_part_number' => 'RATING-TEST',
        'serial_part_number' => 'SERIAL-TEST',
        'quantity_requested' => 10,
        'include_serial' => true,
        'include_rating' => true,
        'status' => LabelRequest::STATUS_REQUESTED,
    ], $attributes));
}

it('keeps the pending inbox and the other operational modules on the dashboard', function (): void {
    labelRoomControlRequest();

    $this->get(route('dashboard'))->assertOk()
        ->assertSee('Requisiciones de etiquetas pendientes')
        ->assertSee(route('label_requests.index'))
        ->assertSee(route('master_requests.create'))
        ->assertSee(route('dummy_requests.create'))
        ->assertSee(route('oracle_jobs.index'))
        ->assertDontSee('Nueva requisición de Etiquetas')
        ->assertDontSee('Retrabajo etiquetas');

    $this->getJson(route('dashboard.pending_request_counts'))->assertOk()
        ->assertJsonPath('counts.labels', 1);
});

it('lists open requests by default and still allows consulting delivered requests', function (): void {
    $pending = labelRoomControlRequest();
    $preparing = labelRoomControlRequest(['status' => LabelRequest::STATUS_IN_PROGRESS]);
    $ready = labelRoomControlRequest(['status' => LabelRequest::STATUS_READY_FOR_DELIVERY]);
    $delivered = labelRoomControlRequest(['status' => LabelRequest::STATUS_COMPLETED]);
    labelRoomControlRequest(['status' => LabelRequest::STATUS_CANCELLED]);

    $this->get(route('label_requests.index'))->assertOk()
        ->assertDontSee('Nueva requisición')
        ->assertViewHas('labelRequests', fn ($rows) => $rows->pluck('id')->sort()->values()->all()
            === [$pending->id, $preparing->id, $ready->id]);

    $this->get(route('label_requests.index', ['status' => 'completed']))->assertOk()
        ->assertViewHas('labelRequests', fn ($rows) => $rows->pluck('id')->all() === [$delivered->id]);
});

it('no longer exposes direct creation or automatic printing endpoints', function (string $method, string $uri, int $status): void {
    $this->call($method, $uri)->assertStatus($status);
})->with([
    ['GET', '/label-requests/create', 404],
    ['GET', '/label-requests/lookup-job', 404],
    ['POST', '/label-requests', 405],
    ['GET', '/label-requests/1/print', 404],
    ['POST', '/label-requests/1/print', 404],
    ['GET', '/label-requests/1/print-batches/1/print', 404],
    ['POST', '/label-requests/1/print-batches/1/preview', 404],
    ['POST', '/label-requests/1/print-batches/1/confirm', 404],
    ['POST', '/label-requests/1/print-batches/1/fail', 404],
    ['GET', '/label-reworks', 404],
    ['GET', '/label-reworks/1', 404],
    ['POST', '/label-reworks/1/reprint', 404],
]);

it('receives kiosk requests and controls their preparation and delivery without print batches', function (string $kind): void {
    $kioskUser = User::query()->create([
        'employee_no' => 'KIOSK-TEST', 'name' => 'Kiosk Requester',
        'password' => Hash::make('password'), 'is_active' => true,
        'shift_id' => $this->shift->id, 'production_line_id' => $this->line->id, 'position' => 'operator',
    ]);
    $kioskUser->roles()->attach(Role::query()->firstOrCreate(['name' => 'kiosk']));
    $this->withSession(['kiosk_user_id' => $kioskUser->id, 'kiosk_employee_no' => $kioskUser->employee_no]);
    $job = OracleJob::query()->create([
        'job_number' => 'JOB-TEST', 'assembly' => '018-TEST', 'line' => $this->line->code,
        'job_qty' => 100, 'quantity_remainder' => 100,
    ]);
    MasterModelMapping::create(['np' => '018-TEST', 'sku' => 'MODEL-TEST', 'master_sheet_type' => 'assembly_packaging', 'active' => true]);
    $routePrefix = $kind === 'lpk' ? 'kiosk.lpk_label_requests' : 'kiosk.label_requests';
    $payload = [
        'request_date' => now()->toDateString(), 'week' => (int) now()->isoWeek(),
        'line_id' => $this->line->id, 'shift_id' => $this->shift->id, 'leader_name' => 'Test Leader',
    ];
    if ($kind === 'lpk') {
        $payload['lpk_label_groups'] = collect(['serial', 'rating'])->map(fn ($type) => [
            'label_type' => $type, 'part_number' => strtoupper($type).'-TEST',
            'items' => [['job_number' => $job->job_number, 'model' => 'MODEL-TEST', 'quantity' => 10]],
        ])->all();
        $payload['lpk_shipping_groups'] = [];
    } else {
        $payload += [
            'job_number' => $job->job_number, 'quantity_requested' => 10,
            'include_serial' => true, 'include_rating' => true,
            'serial_items' => [['part_number' => 'SERIAL-TEST', 'model' => 'MODEL-TEST']],
            'rating_items' => [['part_number' => 'RATING-TEST', 'model' => 'MODEL-TEST']],
        ];
    }

    $this->get(route($routePrefix.'.create'))->assertOk();
    $this->getJson(route($routePrefix.'.lookup_job', ['job_number' => $job->job_number]))->assertOk();
    $this->post(route($routePrefix.'.store'), $payload)->assertSessionHasNoErrors()
        ->assertRedirect(route('kiosk.dashboard'));

    $labelRequest = LabelRequest::query()->sole();
    expect($labelRequest->request_kind)->toBe($kind)
        ->and($labelRequest->serial_standard)->toBeNull();
    $receipt = KioskRequisitionPrintJob::query()->sole();
    expect($receipt->label_request_id)->toBe($labelRequest->id)
        ->and($receipt->zpl)->toContain('^XA');
    $this->postJson(route('kiosk.label_requests.requisition_label.claim', $labelRequest), [
        'token' => $receipt->token,
    ])->assertOk()->assertJsonPath('status', 'claimed');
    $this->postJson(route('kiosk.label_requests.requisition_label.confirm', $labelRequest), [
        'token' => $receipt->token, 'printer_name' => 'Test printer',
    ])->assertOk();
    expect($receipt->refresh()->status)->toBe(KioskRequisitionPrintJob::STATUS_PRINTED);

    $this->get(route('label_requests.index'))->assertOk()->assertSee('SERIAL-TEST')->assertSee('RATING-TEST');
    $this->get(route('label_requests.show', $labelRequest))->assertOk()
        ->assertSee('Revisar y liberar')->assertDontSee('Centro de impresión')
        ->assertDontSee('sistema anterior')->assertDontSee('Impresión automática');
    $this->get(route('label_requests.requisition_sheet', $labelRequest))->assertOk();

    $this->post(route('label_requests.start_preparation', $labelRequest))->assertSessionHasErrors('status');
    app(LabelFolioService::class)->initialize([
        'label_part_number' => 'RATING-TEST', 'folio_family' => 'MODEL-TEST',
        'year' => now()->isoWeekYear(), 'week' => now()->isoWeek(), 'last_serial_number' => 0,
        'opening_notes' => 'Inicio verificado',
    ], $this->operator);
    $administration = app(LabelRoomAdministrationService::class);
    $definitions = app(LabelWorkDefinitionService::class)->forRequest($labelRequest);
    $review = [
        'control_year' => now()->isoWeekYear(), 'control_week' => now()->isoWeek(),
        'job_status' => 'C', 'plan_checked' => 1,
        'tasks' => $definitions->map(fn ($line) => [
            'rating_part_number' => 'RATING-TEST',
            'evidence_position' => 'last', 'assigned_to_user_id' => $this->operator->id,
        ])->all(),
    ];
    $proposal = $administration->proposal($labelRequest, $review);
    foreach ($proposal['tasks'] as $key => $task) {
        $review['tasks'][$key]['expected_start'] = $task['folio_start'];
    }
    $review['proposal_signature'] = $administration->proposalSignature($labelRequest, $proposal, $review);
    $this->post(route('label_requests.release', $labelRequest), $review)->assertSessionHasNoErrors();
    expect($labelRequest->refresh()->status)->toBe(LabelRequest::STATUS_IN_PROGRESS);
    $this->post(route('label_requests.ready_for_delivery', $labelRequest))->assertSessionHasErrors('status');
    foreach ($labelRequest->workTasks as $task) {
        $this->post(route('label_requests.tasks.complete', [$labelRequest, $task]), [
            'printed_by_user_id' => $this->operator->id, 'printed_shift_id' => $this->shift->id,
            'work_date' => now()->toDateString(), 'work_confirmed' => 1,
        ])->assertSessionHasNoErrors();
    }
    expect($labelRequest->refresh()->status)->toBe(LabelRequest::STATUS_READY_FOR_DELIVERY)
        ->and($labelRequest->attended_by_user_id)->toBe($this->operator->id);
    $this->post(route('label_requests.deliver', $labelRequest))->assertSessionHasNoErrors();
    expect($labelRequest->refresh()->status)->toBe(LabelRequest::STATUS_COMPLETED)
        ->and($labelRequest->delivered_by_user_id)->toBe($this->operator->id);
    $this->assertDatabaseCount('label_print_batches', 0);
    $this->assertDatabaseCount('serial_units', 0);
    $this->post(route('label_requests.cancel', $labelRequest))->assertSessionHasErrors('status');
    expect($labelRequest->refresh()->status)->toBe(LabelRequest::STATUS_COMPLETED);
})->with(['standard', 'lpk']);

it('cancels an open request without deleting or rewriting its historical serials', function (bool $printed): void {
    $labelRequest = labelRoomControlRequest(['status' => LabelRequest::STATUS_IN_PROGRESS]);
    $weekId = DB::table('serial_weeks')->insertGetId([
        'label_part_number' => 'RATING-TEST', 'week' => 1, 'year' => 2026, 'last_serial_number' => 1,
    ]);
    DB::table('serial_ranges')->insert([
        'serial_week_id' => $weekId, 'range_start' => 1, 'range_end' => 1,
        'quantity' => 1, 'label_request_id' => $labelRequest->id,
    ]);
    DB::table('serial_units')->insert([
        'serial_week_id' => $weekId, 'serial_number' => 1, 'serial_full' => 'HISTORICAL-001',
        'status' => $printed ? 'printed' : 'allocated', 'printed_at' => $printed ? now() : null,
    ]);
    DB::table('label_print_batches')->insert([
        'label_request_id' => $labelRequest->id, 'shift_id' => $this->shift->id,
        'serial_week_id' => $weekId, 'batch_type' => 'print', 'printed_at' => $printed ? now() : null,
    ]);
    $tables = ['serial_weeks', 'serial_ranges', 'serial_units', 'label_print_batches'];
    $history = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->get()->toJson()]);

    $this->post(route('label_requests.cancel', $labelRequest))->assertSessionHasNoErrors();

    expect($labelRequest->refresh()->status)->toBe(LabelRequest::STATUS_CANCELLED)
        ->and($labelRequest->cancelled_by_user_id)->toBe($this->operator->id)
        ->and($labelRequest->cancelled_at)->not->toBeNull();
    foreach ($history as $table => $rows) {
        expect(DB::table($table)->get()->toJson())->toBe($rows);
    }
})->with([false, true]);

it('keeps access to the other operational modules', function (string $routeName): void {
    $this->get(route($routeName))->assertOk();
})->with(['master_requests.index', 'master_requests.create', 'dummy_requests.index', 'dummy_requests.create', 'oracle_jobs.index']);

it('still requires labels permission to access the request inbox', function (): void {
    $this->operator->update(['module_permissions' => ['master']]);
    $this->get(route('label_requests.index'))->assertForbidden();
    $this->get(route('master_requests.index'))->assertOk();
});
