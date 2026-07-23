<?php

use App\Services\Dummies\DummyQRTemplateZplBuilder;

it('builds dummy elements in the stable order used by the alignment preview', function () {
    $zpl = app(DummyQRTemplateZplBuilder::class)->build([
        'dummy_type' => 'rw',
        'dpi' => 203,
        'width_mm' => 102.6,
        'height_mm' => 50,
        'title_x' => 18,
        'title_y' => 12,
        'qr_x' => 26,
        'qr_y' => 62,
        'qr_orientation' => 'R',
        'qr_magnification' => 5,
        'fg_x' => 350,
        'fg_y' => 68,
        'job_x' => 350,
        'job_y' => 126,
        'consecutive_x' => 370,
        'consecutive_y' => 246,
    ]);

    $elementPositions = [
        strpos($zpl, '^FDRW Dummy QR^FS'),
        strpos($zpl, '^BQR,2,5'),
        strpos($zpl, '^FD^FG^^FS'),
        strpos($zpl, '^FD^JOB^^FS'),
        strpos($zpl, '^FD^CONSECUTIVO^^FS'),
    ];

    expect($zpl)
        ->toContain('^PW820')
        ->toContain('^LL400')
        ->and($elementPositions)->not->toContain(false)
        ->and($elementPositions)->toBe(collect($elementPositions)->sort()->values()->all());
});
