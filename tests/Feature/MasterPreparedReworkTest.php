<?php

use App\Models\MasterActionRequest;
use App\Models\MasterRequest;
use App\Models\ProductionLine;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Services\Kiosk\KioskMasterRequisitionLabelZplBuilder;
use App\Services\Masters\MasterActionRequestService;
use App\Services\Masters\MasterPrintService;
use App\Services\Masters\MasterReworkService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps the Kiosk rework details for Label Room approval', function () {
    $shift = Shift::query()->create(['code' => 'A', 'name' => 'Turno A']);
    $line = ProductionLine::query()->create([
        'code' => 'MXC007', 'name' => 'Línea de prueba', 'line_type' => 'consoles',
    ]);
    $requester = User::query()->create([
        'employee_no' => '1001', 'name' => 'Operador', 'password' => 'test', 'shift_id' => $shift->id,
    ]);
    $reviewer = User::query()->create([
        'employee_no' => '1002', 'name' => 'Validador', 'password' => 'test', 'shift_id' => $shift->id,
    ]);
    $source = MasterRequest::query()->create([
        'request_date' => now()->toDateString(),
        'week' => 42,
        'line_id' => $line->id,
        'shift_id' => $shift->id,
        'leader_name' => 'Líder',
        'requested_by_name' => $requester->name,
        'job_assembly' => 'JOB-1',
        'folios_from' => 1,
        'folios_to' => 2,
        'std_pack_qty' => 100,
        'request_type' => 'assembly',
        'status' => MasterRequest::STATUS_IN_PROGRESS,
    ]);
    $folio = $source->folios()->create([
        'folio_number' => 1, 'qty_for_folio' => 100, 'status' => 'printed',
    ]);
    $source->folios()->create([
        'folio_number' => 2, 'qty_for_folio' => 100, 'status' => 'printed',
    ]);

    $requester->update(['production_line_id' => $line->id, 'position' => 'operator']);
    $requester->roles()->attach(Role::query()->firstOrCreate(['name' => 'kiosk'])->id);
    $this->withoutVite();
    $this->withSession([
        'kiosk_user_id' => $requester->id,
        'kiosk_employee_no' => $requester->employee_no,
    ])->get(route('kiosk.master_action_requests.create', [
        'type' => 'rework', 'source_id' => $source->id,
    ]))->assertOk()
        ->assertSee('Enviar retrabajo a Label Room')
        ->assertSee('Std pack (pzas/pallet)');

    $proposal = [
        'selected_folio_numbers' => [1],
        'additional_folios_count' => 1,
        'job_assembly' => 'JOB-1',
        'job_packaging' => null,
        'request_type' => 'assembly',
        'line_id' => $line->id,
        'local' => 'WIP',
        'subinventory' => 'WIP',
        'model' => 'MODELO-A',
        'po_number' => null,
        'destination' => null,
        'std_pack_qty' => 80,
        'partial_folio' => 3,
        'partial_qty' => 20,
        'rework_reason' => 'Corrección de piezas por folio',
        'notes' => 'Solicitado por producción',
    ];
    $revision = $source->replicate();
    $revision->parent_master_request_id = $source->id;
    $revision->revision_number = 1;
    $revision->status = MasterRequest::STATUS_REQUESTED;
    $revision->save();

    $reworkService = Mockery::mock(MasterReworkService::class);
    $reworkService->shouldReceive('validateProposal')->once()
        ->withArgs(fn ($actualSource, $actualData) => $actualSource->id === $source->id && $actualData === $proposal);
    $reworkService->shouldReceive('createRevision')->once()
        ->withArgs(fn ($actualSource, $actualData, $userId, $userName) =>
            $actualSource->id === $source->id
            && $actualData === $proposal
            && $userId === $reviewer->id
            && $userName === $reviewer->name
        )->andReturn($revision);
    $service = new MasterActionRequestService(
        $reworkService,
        Mockery::mock(MasterPrintService::class),
    );

    $action = $service->submitPreparedRework($source, $proposal, $requester);

    $receipt = app(KioskMasterRequisitionLabelZplBuilder::class)->buildAction($action);
    expect($receipt)->toContain('F1: 100 PZ -> 80 PZ')
        ->toContain('F3 NUEVO: SIN ORIGINAL -> 20 PZ')
        ->toContain('STD PACK: 100 PZ -> 80 PZ')
        ->toContain('MODELO: NO REGISTRADO -> MODELO-A')
        ->toContain('Corrección de piezas por folio');

    expect($action->status)->toBe(MasterActionRequest::STATUS_PENDING)
        ->and($action->fresh()->proposed_rework)->toBe($proposal)
        ->and($action->selectedFolios->modelKeys())->toBe([$folio->id]);

    $approved = $service->approve($action, $reviewer);

    expect($approved->status)->toBe(MasterActionRequest::STATUS_APPROVED)
        ->and($approved->resulting_master_request_id)->toBe($revision->id)
        ->and($approved->reviewed_by_user_id)->toBe($reviewer->id);

    for ($number = 3; $number <= 42; $number++) {
        $source->folios()->create([
            'folio_number' => $number, 'qty_for_folio' => 100, 'status' => 'printed',
        ]);
    }

    $reprint = $service->submit([
        'action_type' => MasterActionRequest::TYPE_REPRINT,
        'master_request_id' => $source->id,
        'folio_ids' => $source->folios()->pluck('id')->all(),
        'copies' => 3,
        'reason' => str_repeat('Se necesitan copias físicas de las hojas. ', 10).'FIN DEL MOTIVO',
    ], $requester);
    $reprintReceipt = app(KioskMasterRequisitionLabelZplBuilder::class)->buildAction($reprint);
    preg_match_all('/\^FD(F\d+):/', $reprintReceipt, $folioMatches);

    expect(substr_count($reprintReceipt, '^XA'))->toBeGreaterThan(1)
        ->and(substr_count($reprintReceipt, '^XZ'))->toBe(substr_count($reprintReceipt, '^XA'))
        ->and($folioMatches[1])->toBe(array_map(fn (int $number): string => 'F'.$number, range(1, 42)))
        ->and($reprintReceipt)->toContain('F42: 100 PZ | 3 COPIAS')
        ->toContain('TOTAL DE HOJAS SOLICITADAS: 126')
        ->toContain('REIMPRESION SIN CAMBIOS')
        ->toContain('FIN DEL MOTIVO')
        ->not->toContain('FOLIOS MAS');
});
