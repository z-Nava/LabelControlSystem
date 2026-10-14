<?php

use App\Models\KioskRequisitionPrintJob;
use App\Models\MasterActionRequest;
use App\Models\MasterRequest;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Kiosk\KioskMasterRequisitionLabelZplBuilder;
use App\Services\Kiosk\KioskRequisitionPrintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $shift = Shift::query()->create(['code' => 'A', 'name' => 'Turno A']);
    $line = ProductionLine::query()->create([
        'code' => 'MXC005', 'name' => 'Línea de prueba', 'line_type' => 'consoles',
    ]);
    $this->kioskUser = User::query()->create([
        'employee_no' => '30000', 'name' => 'Líder de prueba', 'password' => 'test',
        'shift_id' => $shift->id, 'production_line_id' => $line->id, 'position' => 'leader',
    ]);
    $this->kioskUser->roles()->attach(Role::query()->firstOrCreate(['name' => 'kiosk'])->id);
    $this->masterRequest = MasterRequest::query()->create([
        'request_date' => now()->toDateString(), 'week' => 42,
        'line_id' => $line->id, 'shift_id' => $shift->id,
        'leader_name' => $this->kioskUser->name,
        'requested_by_name' => $this->kioskUser->name,
        'job_assembly' => 'JOB-25', 'folios_from' => 1, 'folios_to' => 1,
        'std_pack_qty' => 100, 'request_type' => 'assembly',
        'status' => MasterRequest::STATUS_REQUESTED,
    ]);
    $this->printJob = KioskRequisitionPrintJob::query()->create([
        'token' => (string) Str::uuid(),
        'master_request_id' => $this->masterRequest->id,
        'requested_by_user_id' => $this->kioskUser->id,
        'status' => KioskRequisitionPrintJob::STATUS_PENDING,
        'attempts' => 0,
        'zpl' => '^XA^FDPRUEBA^FS^XZ',
    ]);
    $this->withSession([
        'kiosk_user_id' => $this->kioskUser->id,
        'kiosk_employee_no' => $this->kioskUser->employee_no,
    ])->withoutVite();
});

it('does not automatically offer an old pending receipt after a reload', function () {
    $this->get(route('kiosk.dashboard'))
        ->assertOk()
        ->assertDontSee('kiosk-requisition-print-config')
        ->assertSee('Mis comprobantes')
        ->assertDontSee('Comprobantes recientes');

    $this->get(route('kiosk.receipts.index'))
        ->assertOk()
        ->assertSee('Comprobantes recientes')
        ->assertSee('Requisición Master #'.$this->masterRequest->id);

    $this->get(route('kiosk.receipts.index', ['receipt_job' => $this->printJob->id]))
        ->assertOk()
        ->assertSee('"autoStart":false', false)
        ->assertSee('Imprimir comprobante');

    $this->get(route('kiosk.dashboard', ['receipt_job' => $this->printJob->id]))
        ->assertRedirect(route('kiosk.receipts.index', ['receipt_job' => $this->printJob->id]));
});

it('automatically offers only the receipt of a newly created request', function () {
    $this->withSession(['kiosk_receipt' => [
        'request_kind' => 'master',
        'request_id' => $this->masterRequest->id,
        'type' => 'Master',
        'created_at' => now()->format('d/m/Y H:i'),
    ]])->get(route('kiosk.dashboard'))
        ->assertOk()
        ->assertSee('"autoStart":true', false);
});

it('shows receipt times and builds the physical label in Torreon time', function () {
    $utcMoment = \Illuminate\Support\Carbon::parse('2026-10-15 02:30:00', 'UTC');
    $this->printJob->forceFill(['created_at' => $utcMoment])->save();
    $this->masterRequest->forceFill(['created_at' => $utcMoment])->save();

    $this->get(route('kiosk.receipts.index'))
        ->assertOk()
        ->assertSee('14/10/2026 20:30')
        ->assertDontSee('15/10/2026 02:30');

    $zpl = app(KioskMasterRequisitionLabelZplBuilder::class)->build($this->masterRequest->fresh());
    expect($zpl)->toContain('REGISTRADA: 14/10/2026 20:30');
});

it('prints a separate labelled QR for each available Master Job', function () {
    $builder = app(KioskMasterRequisitionLabelZplBuilder::class);
    $assemblyOnly = $builder->build($this->masterRequest->fresh());
    expect(substr_count($assemblyOnly, '^BQN'))->toBe(1)
        ->and($assemblyOnly)->toContain('^FDLA,JOB-25^FS')
        ->toContain('^FDQR ENSAMBLE^FS')
        ->not->toContain('^FDQR EMPAQUE^FS');

    $this->masterRequest->update(['job_assembly' => null, 'job_packaging' => 'PACK-25']);
    $packagingOnly = $builder->build($this->masterRequest->fresh());
    expect(substr_count($packagingOnly, '^BQN'))->toBe(1)
        ->and($packagingOnly)->toContain('^FDLA,PACK-25^FS')
        ->toContain('^FDQR EMPAQUE^FS')
        ->not->toContain('^FDQR ENSAMBLE^FS');

    $this->masterRequest->update(['job_assembly' => 'ASM-25']);
    $both = $builder->build($this->masterRequest->fresh());
    expect(substr_count($both, '^BQN'))->toBe(2)
        ->and($both)->toContain('^FDLA,ASM-25^FS')
        ->toContain('^FDLA,PACK-25^FS')
        ->toContain('^FDQR ENSAMBLE^FS')
        ->toContain('^FDQR EMPAQUE^FS');

    $claim = app(KioskRequisitionPrintService::class)->claimMaster(
        $this->masterRequest->fresh(), $this->kioskUser, $this->printJob->token,
    );
    expect($claim['status'])->toBe('claimed')
        ->and(substr_count($claim['job']->zpl, '^BQN'))->toBe(2);
});

it('uses the requested Jobs on a Master rework receipt and original Jobs on a reprint receipt', function () {
    $this->masterRequest->update([
        'job_assembly' => 'ASM-ORIGINAL',
        'job_packaging' => 'PACK-ORIGINAL',
    ]);
    $base = [
        'master_request_id' => $this->masterRequest->id,
        'status' => MasterActionRequest::STATUS_PENDING,
        'reason' => 'Se requiere revisar el Job',
        'requested_by_user_id' => $this->kioskUser->id,
        'requested_by_name' => $this->kioskUser->name,
    ];
    $rework = MasterActionRequest::query()->create([
        ...$base,
        'action_type' => MasterActionRequest::TYPE_REWORK,
        'proposed_rework' => [
            'job_assembly' => 'ASM-NUEVO',
            'job_packaging' => 'PACK-NUEVO',
        ],
    ]);
    $reprint = MasterActionRequest::query()->create([
        ...$base,
        'action_type' => MasterActionRequest::TYPE_REPRINT,
        'copies' => 1,
    ]);

    $builder = app(KioskMasterRequisitionLabelZplBuilder::class);
    $reworkZpl = $builder->buildAction($rework);
    $reprintZpl = $builder->buildAction($reprint);

    expect(substr_count($reworkZpl, '^BQN'))->toBe(2)
        ->and($reworkZpl)->toContain('^FDLA,ASM-NUEVO^FS')
        ->toContain('^FDLA,PACK-NUEVO^FS')
        ->not->toContain('^FDLA,ASM-ORIGINAL^FS')
        ->not->toContain('^FDLA,PACK-ORIGINAL^FS');
    expect(substr_count($reprintZpl, '^BQN'))->toBe(2)
        ->and($reprintZpl)->toContain('^FDLA,ASM-ORIGINAL^FS')
        ->toContain('^FDLA,PACK-ORIGINAL^FS');
});

it('reprints an explicitly selected confirmed receipt and records one physical resend', function () {
    $this->masterRequest->update(['job_packaging' => 'PACK-25']);
    $originalPrintedAt = now()->subDay()->startOfSecond();
    $this->printJob->update([
        'status' => KioskRequisitionPrintJob::STATUS_PRINTED,
        'printed_at' => $originalPrintedAt,
    ]);
    $this->get(route('kiosk.receipts.index', ['receipt_job' => $this->printJob->id]))
        ->assertOk()
        ->assertSee('"mode":"reprint"', false)
        ->assertSee('"autoStart":false', false);

    $attempt = (string) Str::uuid();
    $response = $this->postJson(route('kiosk.receipts.reprint', $this->printJob), [
        'token' => $this->printJob->token,
    ])->assertOk();
    expect(substr_count($response->json('zpl'), '^BQN'))->toBe(2)
        ->and($response->json('zpl'))->toContain('^FDLA,JOB-25^FS')
        ->toContain('^FDLA,PACK-25^FS');

    $payload = [
        'token' => $this->printJob->token,
        'attempt_token' => $attempt,
        'printer_name' => 'Zebra de pruebas',
    ];
    $this->postJson(route('kiosk.receipts.confirm_reprint', $this->printJob), $payload)->assertOk();
    $this->postJson(route('kiosk.receipts.confirm_reprint', $this->printJob), $payload)->assertOk();

    expect(DB::table('kiosk_requisition_receipt_reprints')->count())->toBe(1)
        ->and($this->printJob->fresh()->printed_at->equalTo($originalPrintedAt))->toBeTrue()
        ->and($this->printJob->fresh()->status)->toBe(KioskRequisitionPrintJob::STATUS_PRINTED)
        ->and($this->printJob->fresh()->zpl)->toBe('^XA^FDPRUEBA^FS^XZ');
});

it('does not expose another operator receipt', function () {
    $other = User::query()->create([
        'employee_no' => '30001', 'name' => 'Otro operador', 'password' => 'test',
        'shift_id' => $this->kioskUser->shift_id,
        'production_line_id' => $this->kioskUser->production_line_id,
        'position' => 'operator',
    ]);
    $this->printJob->update([
        'requested_by_user_id' => $other->id,
        'status' => KioskRequisitionPrintJob::STATUS_PRINTED,
    ]);

    $this->get(route('kiosk.receipts.index', ['receipt_job' => $this->printJob->id]))->assertNotFound();
    $this->postJson(route('kiosk.receipts.reprint', $this->printJob), [
        'token' => $this->printJob->token,
    ])->assertNotFound();
});
