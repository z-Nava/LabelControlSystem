<?php

use App\Models\LabelJobEntry;
use App\Models\LabelRequest;
use App\Models\LabelWorkTask;
use App\Models\MasterModelMapping;
use App\Models\OracleJob;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\SerialRange;
use App\Models\SerialWeek;
use App\Models\Shift;
use App\Models\User;
use App\Services\Labels\LabelFolioService;
use App\Services\Labels\LabelRequestJobAvailabilityService;
use App\Services\Labels\LabelWorkDefinitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(now()->setDate(2026, 9, 9)->setTime(12, 0));
    $this->line = ProductionLine::create(['code' => 'MXC002', 'name' => 'Consolas', 'line_type' => 'consoles', 'active' => true]);
    $this->shiftA = Shift::create(['code' => 'TA', 'name' => 'Turno A', 'active' => true]);
    $this->shiftB = Shift::create(['code' => 'TB', 'name' => 'Turno B', 'active' => true]);
    $this->leader = User::create(['employee_no' => 'LR-ADMIN', 'name' => 'Líder', 'password' => 'test', 'is_active' => true, 'module_permissions' => ['labels']]);
    $this->leader->roles()->attach(Role::firstOrCreate(['name' => 'label_room']));
    $this->operator = User::create(['employee_no' => 'LR-OP', 'name' => 'Elizabeth', 'password' => 'test', 'is_active' => true, 'module_permissions' => ['labels']]);
    $this->operator->roles()->attach(Role::firstOrCreate(['name' => 'label_room']));
    $this->actingAs($this->leader)->withSession(['auth_access_mode' => 'label_room']);
    $this->job = OracleJob::create(['job_number' => 'MP-TEST', 'assembly' => '018-TEST', 'job_qty' => 1000, 'quantity_remainder' => 1000]);
    MasterModelMapping::create(['np' => '018-TEST', 'sku' => '2563-20', 'master_sheet_type' => 'assembly_packaging', 'active' => true]);
});

function adminLabelRequest(array $attributes = []): LabelRequest
{
    return LabelRequest::create(array_replace([
        'request_kind' => 'standard', 'request_date' => '2026-09-09', 'week' => 37,
        'line_id' => test()->line->id, 'shift_id' => test()->shiftA->id, 'leader_name' => 'Producción',
        'requested_by_name' => 'Solicitante', 'model' => '2563-20', 'job_number' => 'MP-TEST',
        'serial_part_number' => '95041000', 'label_part_number' => '941703002', 'quantity_requested' => 200,
        'include_serial' => true, 'include_rating' => true, 'include_shipping' => true, 'include_inner' => true,
        'shipping_part_number' => 'SHIP', 'shipping_model' => '2563-20', 'shipping_quantity' => 100,
        'inner_part_number' => 'INNER', 'inner_model' => '2563-20', 'status' => 'requested',
    ], $attributes));
}

function adminSeedWeek(int $last = 0, int $week = 37, int $year = 2026, string $part = '941703002', string $family = '2563-20'): SerialWeek
{
    return app(LabelFolioService::class)->initialize([
        'label_part_number' => $part, 'folio_family' => $family, 'year' => $year, 'week' => $week,
        'last_serial_number' => $last, 'opening_notes' => 'Excel verificado',
    ], test()->leader);
}

function adminReviewInput(LabelRequest $request, array $overrides = []): array
{
    $tasks = app(LabelWorkDefinitionService::class)->forRequest($request)->map(fn ($line) => [
        'assigned_to_user_id' => test()->operator->id, 'rating_part_number' => '941703002',
        'evidence_position' => 'last',
    ])->all();

    return array_replace(['control_year' => 2026, 'control_week' => 37, 'job_status' => 'C',
        'plan_checked' => 1, 'review_notes' => 'Validado contra producción', 'tasks' => $tasks], $overrides);
}

function adminPreviewResponse(LabelRequest $request, array $data): TestResponse
{
    test()->post(route('label_requests.preview', $request), $data)
        ->assertStatus(303)->assertRedirect(route('label_requests.review', $request))->assertSessionHasNoErrors();

    return test()->get(route('label_requests.review', $request))->assertOk();
}

function adminPreview(LabelRequest $request, array $data): array
{
    $response = adminPreviewResponse($request, $data);
    foreach ($response->viewData('proposal')['tasks'] as $key => $task) {
        if ($task['folio_start'] !== null) {
            $data['tasks'][$key]['expected_start'] = $task['folio_start'];
        }
    }

    $data['proposal_signature'] = $response->viewData('proposalSignature');

    return $data;
}

function adminRelease(LabelRequest $request, ?array $data = null): LabelRequest
{
    $data = adminPreview($request, $data ?? adminReviewInput($request));
    test()->post(route('label_requests.release', $request), $data)->assertSessionHasNoErrors();

    return $request->refresh();
}

function adminComplete(LabelRequest $request, LabelWorkTask $task, ?Shift $shift = null, bool $signed = false)
{
    return test()->post(route('label_requests.tasks.complete', [$request, $task]), [
        'printed_by_user_id' => test()->operator->id, 'printed_shift_id' => ($shift ?? test()->shiftA)->id,
        'work_date' => '2026-09-09', 'physical_signed' => $signed ? 1 : 0, 'work_confirmed' => 1,
    ]);
}

it('reserves one shared inclusive range with evidence and preserves requested quantities', function () {
    $week = adminSeedWeek();
    $request = adminLabelRequest();
    $data = adminPreview($request, adminReviewInput($request));
    expect(SerialRange::count())->toBe(0)->and($week->refresh()->last_serial_number)->toBe(0);
    $this->post(route('label_requests.release', $request), $data)->assertSessionHasNoErrors();
    $this->post(route('label_requests.release', $request), $data)->assertSessionHasNoErrors();
    $range = SerialRange::sole();
    expect($range->range_start)->toBe(1)->and($range->range_end)->toBe(201)
        ->and($range->quantity)->toBe(201)->and($range->evidence_folio)->toBe(201)
        ->and($week->refresh()->last_serial_number)->toBe(201)
        ->and($request->refresh()->quantity_requested)->toBe(200);
    expect($request->workTasks()->whereIn('label_type', ['serial', 'rating'])->pluck('serial_range_id')->unique()->all())->toBe([$range->id]);
    expect($request->workTasks()->count())->toBe(4)
        ->and($request->workTasks()->sum('evidence_quantity'))->toBe(4)
        ->and($request->workTasks()->where('label_type', 'shipping')->sole()->quantity)->toBe(100);
    $this->assertDatabaseCount('serial_units', 0);
    $this->assertDatabaseCount('label_print_batches', 0);
    $this->get(route('label_requests.show', $request))->assertOk()->assertSee('Trabajo liberado')->assertSee('201');
    $this->get(route('label_requests.requisition_sheet', $request))->assertOk();
});

it('continues the same SKU across Jobs lines and shifts and resets by year and week', function () {
    adminSeedWeek();
    adminRelease(adminLabelRequest());
    $otherLine = ProductionLine::create(['code' => 'MXC003', 'name' => 'Otra', 'line_type' => 'consoles']);
    OracleJob::create(['job_number' => 'MP-SAME-SKU', 'assembly' => '018-TEST']);
    $second = adminLabelRequest(['job_number' => 'MP-SAME-SKU', 'quantity_requested' => 100, 'line_id' => $otherLine->id, 'shift_id' => $this->shiftB->id]);
    adminRelease($second);
    expect($second->folio_start)->toBe(202)->and($second->folio_end)->toBe(302);
    $next = adminLabelRequest();
    adminRelease($next, adminReviewInput($next, ['control_week' => 38]));
    expect($next->folio_start)->toBe(1)->and($next->folio_end)->toBe(201);
    $nextYear = adminLabelRequest();
    adminRelease($nextYear, adminReviewInput($nextYear, ['control_year' => 2027, 'control_week' => 1]));
    expect($nextYear->folio_start)->toBe(1);
});

it('requires initial history verification and prevents reducing an existing counter', function () {
    $request = adminLabelRequest();
    $this->post(route('label_requests.preview', $request), adminReviewInput($request))->assertSessionHasErrors('tasks');
    adminSeedWeek(500);
    $this->post(route('label_requests.weeks.initialize'), [
        'label_part_number' => '941703002', 'folio_family' => '2563-20', 'year' => 2026, 'week' => 37,
        'last_serial_number' => 499, 'opening_notes' => 'Corrección', 'history_checked' => 1,
    ])->assertSessionHasErrors('last_serial_number');
    adminRelease($request);
    expect($request->folio_start)->toBe(501)->and($request->folio_end)->toBe(701);
});

it('does not allocate twice when a proposal becomes stale', function () {
    adminSeedWeek();
    $one = adminLabelRequest();
    $two = adminLabelRequest();
    $stale = adminPreview($two, adminReviewInput($two));
    adminRelease($one);
    $this->post(route('label_requests.release', $two), $stale)->assertSessionHasErrors('tasks');
    expect($two->refresh()->released_at)->toBeNull()->and($two->workTasks()->count())->toBe(0)->and(SerialRange::count())->toBe(1);
});

it('cannot bypass review or close an unfinished request through the old actions', function () {
    $request = adminLabelRequest();
    $this->post(route('label_requests.start_preparation', $request))->assertSessionHasErrors('status');
    $this->post(route('label_requests.ready_for_delivery', $request))->assertSessionHasErrors('status');
    adminSeedWeek();
    $data = adminReviewInput($request);
    $data['plan_checked'] = 0;
    $this->post(route('label_requests.release', $request), $data)->assertSessionHasErrors('plan_checked');
    expect($request->refresh()->released_at)->toBeNull();
});

it('closes exactly once on the final task and uses its shift and operational date', function () {
    adminSeedWeek();
    $request = adminRelease(adminLabelRequest());
    $tasks = $request->workTasks;
    foreach ($tasks->take(3) as $task) {
        adminComplete($request, $task)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('label_job_entries', 0);
    }
    $last = $tasks->last();
    adminComplete($request, $last, $this->shiftB)->assertSessionHasNoErrors();
    adminComplete($request, $last, $this->shiftA)->assertSessionHasNoErrors();
    $entry = LabelJobEntry::sole();
    expect($entry->shift_id)->toBe($this->shiftB->id)->and($entry->work_date->format('Y-m-d'))->toBe('2026-09-09')
        ->and($entry->quantity)->toBe(200)->and($entry->shipping_quantity)->toBe(100)
        ->and($request->refresh()->status)->toBe('attended')->and($request->job_status)->toBe('C')
        ->and($tasks->first()->refresh()->printed_shift_id)->toBe($this->shiftA->id);
    $this->get(route('label_requests.jobs'))->assertOk()->assertSee('MP-TEST')->assertSee('TB');
    $this->get(route('label_requests.weeks'))->assertOk()->assertSee('Elizabeth');
    $this->post(route('label_requests.deliver', $request))->assertSessionHasNoErrors();
});

it('reprints originals with no new folios or evidence and requires the physical signature', function () {
    $week = adminSeedWeek();
    $original = adminRelease(adminLabelRequest());
    foreach ($original->workTasks as $task) {
        adminComplete($original, $task)->assertSessionHasNoErrors();
    }
    $source = SerialRange::sole();
    $reprint = adminLabelRequest(['folio_mode' => 'reprint_originals', 'quantity_requested' => 10, 'shipping_quantity' => 5]);
    $data = adminReviewInput($reprint, ['job_status' => 'RE', 'control_week' => 38, 'originals_received' => 1]);
    foreach ($data['tasks'] as &$task) {
        $task += ['source_range_id' => $source->id, 'folio_start' => 50, 'folio_end' => 59];
    }
    unset($task);
    adminRelease($reprint, $data);
    expect($week->refresh()->last_serial_number)->toBe(201)->and(SerialRange::count())->toBe(1)
        ->and($reprint->workTasks()->sum('evidence_quantity'))->toBe(0)
        ->and($reprint->control_week)->toBe(37)
        ->and(app(LabelRequestJobAvailabilityService::class)->calculate($this->job)['reserved_quantity'])->toBe(200);
    foreach ($reprint->workTasks->take(3) as $task) {
        adminComplete($reprint, $task)->assertSessionHasNoErrors();
    }
    $last = $reprint->workTasks->last();
    adminComplete($reprint, $last)->assertSessionHasErrors('physical_signed');
    expect($last->refresh()->status)->toBe('pending');
    adminComplete($reprint, $last, $this->shiftB, true)->assertSessionHasNoErrors();
    expect($reprint->refresh()->physical_signed_at)->not->toBeNull()->and(LabelJobEntry::count())->toBe(2);
});

it('rejects mismatched or evidence folios in an original reprint', function () {
    adminSeedWeek();
    $original = adminRelease(adminLabelRequest());
    $range = SerialRange::sole();
    $reprint = adminLabelRequest(['folio_mode' => 'reprint_originals', 'quantity_requested' => 10]);
    $data = adminReviewInput($reprint, ['originals_received' => 1]);
    foreach ($data['tasks'] as &$task) {
        $task += ['source_range_id' => $range->id, 'folio_start' => 192, 'folio_end' => 201];
    }
    unset($task);
    $this->post(route('label_requests.preview', $reprint), $data)->assertSessionHasErrors('tasks');
    expect(SerialRange::count())->toBe(1);
});

it('preserves reserved folios when a released requisition is cancelled', function () {
    $week = adminSeedWeek();
    $request = adminRelease(adminLabelRequest());
    $this->post(route('label_requests.cancel', $request))->assertSessionHasNoErrors();
    expect($week->refresh()->last_serial_number)->toBe(201)
        ->and(SerialRange::sole()->status)->toBe('cancelled')
        ->and($request->workTasks()->where('status', 'cancelled')->count())->toBe(4);
    $next = adminRelease(adminLabelRequest());
    expect($next->folio_start)->toBe(202);
});

it('does not allow task IDs from another requisition or inactive operators', function () {
    adminSeedWeek();
    $one = adminRelease(adminLabelRequest());
    $two = adminRelease(adminLabelRequest());
    $task = $one->workTasks()->first();
    adminComplete($two, $task)->assertNotFound();
    $this->operator->update(['is_active' => false]);
    adminComplete($one, $task)->assertSessionHasErrors('operator');
    expect($task->refresh()->status)->toBe('pending');
});

it('keeps independent classification changes audited after closing', function () {
    adminSeedWeek();
    $request = adminRelease(adminLabelRequest());
    foreach ($request->workTasks as $task) {
        adminComplete($request, $task)->assertSessionHasNoErrors();
    }
    $this->post(route('label_requests.classify', $request), ['job_status' => 'OF', 'reason' => 'Planeador confirmó fin de orden'])->assertSessionHasNoErrors();
    expect($request->refresh()->job_status)->toBe('OF')->and($request->status)->toBe('attended');
    $this->get(route('label_requests.jobs', ['job_status' => 'OF']))->assertOk()->assertSee('MP-TEST');
    $this->assertDatabaseHas('label_administration_events', ['label_request_id' => $request->id, 'action' => 'classified']);
});

it('keeps LPK Shipping shared while resolving each Jobs own assembly and SKU', function () {
    adminSeedWeek();
    adminSeedWeek(family: '2563-22W');
    OracleJob::create(['job_number' => 'MP-SECOND', 'assembly' => '018-SECOND']);
    MasterModelMapping::create(['np' => '018-SECOND', 'sku' => '2563-22W', 'master_sheet_type' => 'assembly_packaging', 'active' => true]);
    $request = adminLabelRequest(['request_kind' => 'lpk', 'include_inner' => false]);
    foreach (['serial' => '95041000', 'rating' => '941703002'] as $type => $part) {
        $group = $request->lpkLabelGroups()->create(['label_type' => $type, 'part_number' => $part, 'position' => 1]);
        $group->items()->createMany([
            ['job_number' => 'MP-TEST', 'model' => '2563-20', 'quantity' => 20, 'position' => 1],
            ['job_number' => 'MP-SECOND', 'model' => '2563-22W', 'quantity' => 30, 'position' => 2],
        ]);
    }
    $shipping = $request->lpkShippingGroups()->create(['part_number' => 'SHIP', 'quantity' => 25, 'po_number' => 'PO-SHARED', 'position' => 1]);
    $shipping->items()->createMany([
        ['job_number' => 'MP-TEST', 'model' => '2563-20', 'position' => 1],
        ['job_number' => 'MP-SECOND', 'model' => '2563-22W', 'position' => 2],
    ]);
    adminRelease($request);
    expect(SerialRange::count())->toBe(2)->and($request->workTasks()->count())->toBe(5)
        ->and(SerialWeek::where('folio_family', '2563-20')->sole()->last_serial_number)->toBe(21)
        ->and(SerialWeek::where('folio_family', '2563-22W')->sole()->last_serial_number)->toBe(31)
        ->and($request->folio_start)->toBeNull();
    expect($request->workTasks()->where('job_number', 'MP-SECOND')->pluck('folio_family')->unique()->all())->toBe(['2563-22W']);
    foreach ($request->workTasks as $task) {
        adminComplete($request, $task)->assertSessionHasNoErrors();
    }
    expect(LabelJobEntry::count())->toBe(2)->and(LabelJobEntry::sum('shipping_quantity'))->toBe(0);
    foreach (LabelJobEntry::all() as $entry) {
        expect($entry->shared_shipping[0]['quantity'])->toBe(25);
    }
});

it('preserves historical high water marks without guessing the family', function () {
    $legacy = SerialWeek::create(['label_part_number' => '941703002', 'year' => 2026, 'week' => 37, 'last_serial_number' => 500, 'serial_standard' => 'UL']);
    $this->post(route('label_requests.weeks.initialize'), [
        'label_part_number' => '941703002', 'folio_family' => '2563-20', 'year' => 2026, 'week' => 37,
        'last_serial_number' => 100, 'opening_notes' => 'Excel', 'history_checked' => 1,
    ])->assertSessionHasErrors('last_serial_number');
    expect(SerialWeek::count())->toBe(1)->and($legacy->refresh()->folio_family)->toBeNull();
});

it('uses the complete assembly SKU without adding a column to master model mapping', function () {
    $request = adminLabelRequest();
    $this->get(route('label_requests.review', $request))->assertOk()->assertSee('value="2563-20"', false);
    expect(app(LabelWorkDefinitionService::class)->forRequest($request)->first()['folio_family'])->toBe('2563-20')
        ->and(Schema::hasColumn('master_model_mappings', 'folio_family'))->toBeFalse();
});

it('keeps existing label-room module access protections', function () {
    $this->leader->update(['module_permissions' => ['master']]);
    $this->get(route('label_requests.weeks'))->assertForbidden();
    $this->get(route('label_requests.jobs'))->assertForbidden();
    $this->post(route('label_requests.release', adminLabelRequest()), [])->assertForbidden();
});

it('records physical originals from Excel without inventing an allocation', function () {
    $week = adminSeedWeek(500);
    $request = adminLabelRequest(['folio_mode' => 'reprint_originals', 'quantity_requested' => 10, 'include_shipping' => false, 'include_inner' => false]);
    $data = adminReviewInput($request, ['originals_received' => 1, 'job_status' => 'RE']);
    foreach ($data['tasks'] as &$task) {
        $task += ['folio_start' => 50, 'folio_end' => 59, 'original_year' => 2026, 'original_week' => 36,
            'original_reference' => 'Requisición física 123 / Excel hoja 941703002-2563'];
    }
    unset($task);
    adminRelease($request, $data);
    expect(SerialRange::count())->toBe(0)->and($week->refresh()->last_serial_number)->toBe(500)
        ->and($request->control_week)->toBe(36)->and($request->workTasks()->sum('evidence_quantity'))->toBe(0);
    $this->get(route('label_requests.weeks', ['year' => 2026, 'week' => 36]))->assertOk()->assertSee('Requisición física 123');
    foreach ($request->workTasks as $task) {
        adminComplete($request, $task, $this->shiftB, true)->assertSessionHasNoErrors();
    }
    expect(LabelJobEntry::sole()->quantity)->toBe(10);
});

it('requires a fresh proposal if review details are changed before release', function () {
    adminSeedWeek();
    $request = adminLabelRequest();
    $data = adminPreview($request, adminReviewInput($request));
    $data['job_status'] = 'OF';
    $this->post(route('label_requests.release', $request), $data)->assertSessionHasErrors('tasks');
    expect($request->refresh()->released_at)->toBeNull()->and(SerialRange::count())->toBe(0);
});

it('ignores a submitted family and always reserves against the assembly SKU', function () {
    $week = adminSeedWeek(769);
    $other = adminSeedWeek(100, family: 'OTHER');
    $request = adminLabelRequest(['model' => 'MANUALLY-ENTERED-MODEL']);
    $data = adminReviewInput($request);
    $data['tasks']['line_0']['folio_family'] = 'OTHER';
    $data['tasks']['line_1']['folio_family'] = '0';
    adminRelease($request, $data);
    expect($request->folio_start)->toBe(770)->and($week->refresh()->last_serial_number)->toBe(970)
        ->and($other->refresh()->last_serial_number)->toBe(100)
        ->and($request->workTasks()->whereIn('label_type', ['serial', 'rating'])->pluck('folio_family')->unique()->all())->toBe(['2563-20']);
});

it('blocks allocation when the Job cannot resolve to an active assembly SKU', function (string $missing) {
    adminSeedWeek();
    $request = adminLabelRequest();
    $data = adminReviewInput($request);
    $data['tasks']['line_0']['folio_family'] = '2563-20';
    match ($missing) {
        'job' => $this->job->delete(),
        'assembly' => $this->job->update(['assembly' => '']),
        'mapping' => MasterModelMapping::query()->delete(),
        'inactive' => MasterModelMapping::query()->update(['active' => false]),
    };
    $this->post(route('label_requests.preview', $request), $data)->assertSessionHasErrors('tasks');
    expect(session('errors')->first('tasks'))->toContain('No se encontró un SKU activo')
        ->and(SerialRange::count())->toBe(0);
})->with(['job', 'assembly', 'mapping', 'inactive']);

it('invalidates a proposal if the assembly SKU changes before release', function () {
    $oldWeek = adminSeedWeek();
    $newWeek = adminSeedWeek(family: '2563-22W');
    $request = adminLabelRequest();
    $data = adminPreview($request, adminReviewInput($request));
    MasterModelMapping::query()->update(['sku' => '2563-22W']);
    $this->post(route('label_requests.release', $request), $data)->assertSessionHasErrors('tasks');
    expect(SerialRange::count())->toBe(0)->and($oldWeek->refresh()->last_serial_number)->toBe(0)
        ->and($newWeek->refresh()->last_serial_number)->toBe(0);
});

it('allows a new kiosk reprint when the production Job has no unrequested units', function () {
    adminLabelRequest(['quantity_requested' => 1000]);
    $kiosk = User::create(['employee_no' => 'PROD-REPRINT', 'name' => 'Producción', 'password' => 'test', 'is_active' => true,
        'production_line_id' => $this->line->id, 'shift_id' => $this->shiftA->id, 'position' => 'operator']);
    $kiosk->roles()->attach(Role::firstOrCreate(['name' => 'kiosk']));
    $this->withSession(['kiosk_user_id' => $kiosk->id, 'kiosk_employee_no' => $kiosk->employee_no]);
    $payload = ['folio_mode' => 'reprint_originals', 'request_date' => '2026-09-09', 'week' => 37,
        'line_id' => $this->line->id, 'shift_id' => $this->shiftA->id, 'leader_name' => 'Lider Produccion',
        'job_number' => $this->job->job_number, 'quantity_requested' => 10, 'include_serial' => true,
        'serial_items' => [['part_number' => '95041000', 'model' => '2563-20']]];
    $this->post(route('kiosk.label_requests.store'), $payload)->assertSessionHasNoErrors();
    expect(LabelRequest::where('folio_mode', 'reprint_originals')->count())->toBe(1)
        ->and(app(LabelRequestJobAvailabilityService::class)->calculate($this->job)['available_quantity'])->toBe(0);
    $payload['folio_mode'] = 'new';
    $this->post(route('kiosk.label_requests.store'), $payload)->assertSessionHasErrors('quantity_requested');
});

it('explains a test control that does not match the assembly SKU without reassigning its counter', function () {
    $week = adminSeedWeek(769, family: '3030');
    $request = adminLabelRequest();
    $this->get(route('label_requests.review', $request))->assertOk()
        ->assertSee('Controles registrados')->assertSee('3030')->assertSee('769')
        ->assertSee('value="2563-20"', false)->assertSee('Ensamble 018-TEST');
    $this->from(route('label_requests.review', $request))
        ->post(route('label_requests.preview', $request), adminReviewInput($request))->assertSessionHasErrors('tasks');
    expect(session('errors')->first('tasks'))->toContain('familia 2563-20')
        ->toContain('ya hay controles con familia: 3030');
    expect(SerialWeek::count())->toBe(1)->and(SerialRange::count())->toBe(0)
        ->and($week->refresh()->last_serial_number)->toBe(769);
});

it('shows registered controls for the year and week being reviewed', function () {
    adminSeedWeek(769);
    $request = adminLabelRequest();
    $data = adminReviewInput($request, ['control_week' => 38]);
    $response = adminPreviewResponse($request, $data);
    expect($response->viewData('controlsWeek'))->toBe(38)
        ->and($response->viewData('availableControls'))->toHaveCount(0);
    MasterModelMapping::query()->update(['sku' => 'OTHER']);
    $this->from(route('label_requests.review', $request))
        ->post(route('label_requests.preview', $request), $data)->assertSessionHasErrors('tasks');
    expect(session('errors')->first('tasks'))->toContain('Captura 0 en Último folio utilizado');
    $response = $this->get(route('label_requests.review', $request))->assertOk();
    expect($response->viewData('controlsWeek'))->toBe(38)
        ->and($response->viewData('availableControls'))->toHaveCount(0);
});

it('keeps the standard unknown for new controls while preserving existing standards and counters', function () {
    $week = adminSeedWeek(1488);
    expect($week->serial_standard)->toBeNull();
    $request = adminRelease(adminLabelRequest());
    expect($request->serial_standard)->toBeNull()
        ->and($request->folio_start)->toBe(1489)
        ->and($week->refresh()->serial_standard)->toBeNull()
        ->and($week->last_serial_number)->toBe(1689);

    $week->update(['serial_standard' => 'EMEA']);
    adminSeedWeek(1689);
    expect($week->refresh()->serial_standard)->toBe('EMEA');
    $next = adminLabelRequest();
    adminRelease($next, adminReviewInput($next, ['control_week' => 38]));
    $newWeek = SerialWeek::where('week', 38)->sole();
    expect($newWeek->serial_standard)->toBeNull()->and($newWeek->last_serial_number)->toBe(201)
        ->and($week->refresh()->serial_standard)->toBe('EMEA')->and($week->last_serial_number)->toBe(1689);
});

it('returns release validation errors to the review page instead of the POST preview URL', function () {
    adminSeedWeek();
    $request = adminLabelRequest();
    $data = adminPreview($request, adminReviewInput($request));
    $data['review_notes'] = 'La revisión cambió';
    $this->from(route('label_requests.preview', $request))
        ->post(route('label_requests.release', $request), $data)
        ->assertRedirect(route('label_requests.review', $request))->assertSessionHasErrors('tasks');
    $this->get(route('label_requests.review', $request))->assertOk()->assertSee('Recalcula la propuesta')
        ->assertSee('La revisión cambió');
    expect(SerialRange::count())->toBe(0)->and($request->refresh()->released_at)->toBeNull();

    $data = adminPreview($request, $data);
    $this->post(route('label_requests.release', $request), $data)
        ->assertRedirect(route('label_requests.show', $request))->assertSessionHasNoErrors();
    expect(SerialRange::count())->toBe(1)->and($request->refresh()->review_notes)->toBe('La revisión cambió');
});

it('returns invalid preview input to a GET page and keeps the entered values', function () {
    $request = adminLabelRequest();
    $data = adminReviewInput($request, ['control_week' => 54, 'review_notes' => 'Conservar observaciones']);
    $this->from(route('label_requests.preview', $request))
        ->post(route('label_requests.preview', $request), $data)
        ->assertRedirect(route('label_requests.review', $request))->assertSessionHasErrors('control_week');
    $this->get(route('label_requests.review', $request))->assertOk()->assertSee('Conservar observaciones');
    expect(SerialRange::count())->toBe(0);
});

function adminRenderedReviewForm(TestResponse $response): array
{
    $document = new DOMDocument;
    $previousErrors = libxml_use_internal_errors(true);
    try {
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
    }

    $xpath = new DOMXPath($document);
    $form = $xpath->query('//form[.//input[@name="control_year"]]')->item(0);
    expect($form)->toBeInstanceOf(DOMElement::class);
    $fields = [];
    foreach ($xpath->query('.//input[@name] | .//select[@name] | .//textarea[@name]', $form) as $field) {
        if ($field->hasAttribute('disabled')) {
            continue;
        }
        if ($field->tagName === 'select') {
            $option = $xpath->query('.//option[@selected]', $field)->item(0)
                ?? $xpath->query('.//option', $field)->item(0);
            $value = $option->getAttribute('value');
        } elseif ($field->tagName === 'textarea') {
            $value = $field->textContent;
        } else {
            if (in_array($field->getAttribute('type'), ['checkbox', 'radio'], true) && ! $field->hasAttribute('checked')) {
                continue;
            }
            $value = $field->getAttribute('value');
        }
        $fields[] = urlencode($field->getAttribute('name')).'='.urlencode($value);
    }
    parse_str(implode('&', $fields), $data);

    return ['action' => $form->getAttribute('action'), 'data' => $data];
}

it('releases from the rendered review form with optional fields empty', function () {
    $week = adminSeedWeek();
    $request = adminLabelRequest();
    $form = adminRenderedReviewForm($this->get(route('label_requests.review', $request))->assertOk());
    expect($form['action'])->toBe(route('label_requests.preview', $request));
    $form['data']['plan_checked'] = '1';
    $this->from(route('label_requests.review', $request))->post($form['action'], $form['data'])
        ->assertStatus(303)->assertRedirect(route('label_requests.review', $request))->assertSessionHasNoErrors();

    $form = adminRenderedReviewForm($this->get(route('label_requests.review', $request))->assertOk());
    expect($form['action'])->toBe(route('label_requests.release', $request))
        ->and($form['data']['review_notes'])->toBe('')
        ->and(SerialRange::count())->toBe(0);
    $this->from(route('label_requests.review', $request))->post($form['action'], $form['data'])
        ->assertRedirect(route('label_requests.show', $request))->assertSessionHasNoErrors();
    $this->get(route('label_requests.show', $request))->assertOk()->assertSee('Trabajo liberado');
    expect($request->refresh()->released_at)->not->toBeNull()
        ->and($week->refresh()->last_serial_number)->toBe(201)
        ->and(SerialRange::count())->toBe(1)
        ->and($request->workTasks()->whereNotNull('assigned_to_user_id')->count())->toBe(0);
});

it('redirects old preview links without reserving folios and rejects GET release', function () {
    $week = adminSeedWeek();
    $request = adminLabelRequest();
    $this->get(route('label_requests.preview', $request))->assertRedirect(route('label_requests.review', $request));
    $this->get(route('label_requests.review', $request))->assertOk();
    $this->get(route('label_requests.release', $request))->assertStatus(405);
    expect(SerialRange::count())->toBe(0)->and($week->refresh()->last_serial_number)->toBe(0)
        ->and($request->refresh()->released_at)->toBeNull();
});

it('keeps previews scoped to the requisition and never reserves on refresh', function () {
    $week = adminSeedWeek();
    $first = adminLabelRequest();
    $second = adminLabelRequest();
    $this->post(route('label_requests.preview', $first), adminReviewInput($first))
        ->assertStatus(303)->assertSessionHasNoErrors();
    $this->get(route('label_requests.review', $second))->assertOk()->assertViewMissing('proposal');
    $this->get(route('label_requests.review', $first))->assertOk();
    $this->get(route('label_requests.review', $first))->assertOk();
    expect(SerialRange::count())->toBe(0)->and($week->refresh()->last_serial_number)->toBe(0);
});
