<?php

use App\Models\LabelRequest;
use App\Models\LabelRequestRating;
use App\Models\LabelRequestSerial;
use App\Models\ProductionLine;
use App\Models\Shift;
use App\Services\Kiosk\KioskRequisitionLabelZplBuilder;
use Illuminate\Support\Collection;

function kioskLabelRequestForZpl(array $overrides = []): LabelRequest
{
    $labelRequest = new LabelRequest;
    $labelRequest->setRawAttributes(array_merge([
        'id' => 42,
        'request_date' => '2026-08-19',
        'week' => 34,
        'leader_name' => 'ANA LOPEZ',
        'requested_by_name' => 'JUAN PEREZ',
        'job_number' => 'JOB-12345',
        'model' => 'MODELO X',
        'po_number' => 'PO-789',
        'destination' => 'CEDIS NORTE',
        'quantity_requested' => 125,
        'shipping_quantity' => 25,
        'include_serial' => true,
        'include_rating' => true,
        'include_inner' => false,
        'include_shipping' => true,
        'created_at' => '2026-08-19 11:30:00',
    ], $overrides), true);
    $labelRequest->setRelation('line', new ProductionLine(['code' => 'L01', 'name' => 'LINEA UNO']));
    $labelRequest->setRelation('shift', new Shift(['code' => 'T1', 'name' => 'PRIMERO']));
    $labelRequest->setRelation('serials', new Collection([
        new LabelRequestSerial(['part_number' => 'SER-100', 'position' => 1]),
        new LabelRequestSerial(['part_number' => 'SER-200', 'position' => 2]),
    ]));
    $labelRequest->setRelation('ratings', new Collection([
        new LabelRequestRating(['part_number' => 'RAT-300', 'position' => 1]),
    ]));

    return $labelRequest;
}

it('builds the 100 millimeter requisition label with blank reception fields', function () {
    $zpl = (new KioskRequisitionLabelZplBuilder)->build(kioskLabelRequestForZpl(), 203);

    expect($zpl)
        ->toContain('^PW799')
        ->toContain('^LL799')
        ->toContain('JOB: JOB-12345')
        ->toContain('LINEA: L01 LINEA UNO')
        ->toContain('^FDLA,JOB-12345^FS')
        ->toContain('IMPRIMIO:')
        ->toContain('RECIBIO:')
        ->toContain('FOLIO INICIAL:')
        ->toContain('FOLIO FINAL:')
        ->toContain('TURNO:')
        ->not->toContain('TURNO: PRIMERO')
        ->not->toContain('ESTADO:')
        ->and(substr_count($zpl, '^XZ'))->toBe(1);
});

it('scales the physical label to 300 dpi', function () {
    $zpl = (new KioskRequisitionLabelZplBuilder)->build(kioskLabelRequestForZpl(), 300);

    expect($zpl)
        ->toContain('^PW1181')
        ->toContain('^LL1181');
});

it('escapes zpl control characters from requisition data', function () {
    $zpl = (new KioskRequisitionLabelZplBuilder)->build(
        kioskLabelRequestForZpl(['model' => 'ABC^XZ~TEST\\VALUE_UNDERSCORE']),
    );

    expect($zpl)
        ->toContain('MODELO: ABC_5EXZ_7ETEST_5CVALUE_5FUNDERSCORE')
        ->not->toContain('MODELO: ABC^XZ');
});
