<?php

use App\Services\Labels\SerialTemplateZplBuilder;

it('uses the configured physical dimensions for serial templates', function () {
    $zpl = app(SerialTemplateZplBuilder::class)->build(
        'serial',
        [
            'qr' => ['x' => 30, 'y' => 30, 'magnification' => 4],
            'sku' => ['x' => 170, 'y' => 35, 'font_size' => 44],
            'sn' => ['x' => 170, 'y' => 95, 'font_size' => 22],
        ],
        'UL',
        [
            'dpi' => 203,
            'width_mm' => 50.8,
            'height_mm' => 25.4,
        ],
    );

    expect($zpl)
        ->toContain('^PW406')
        ->toContain('^LL203')
        ->toContain('^LH0,0')
        ->toContain('^LS0')
        ->and(substr_count($zpl, '^LL'))->toBe(1);
});

it('uses the configured physical dimensions for text-only rating templates', function () {
    $zpl = app(SerialTemplateZplBuilder::class)->build(
        'rating',
        [
            'text' => ['x' => 40, 'y' => 40, 'font_size' => 40],
            'rating_qr' => false,
        ],
        'UL',
        [
            'dpi' => 300,
            'width_mm' => 25.4,
            'height_mm' => 12.7,
        ],
    );

    expect($zpl)
        ->toContain('^PW300')
        ->toContain('^LL150')
        ->toContain('^LH0,0')
        ->toContain('^LS0')
        ->toContain('^FD{{serial_full}}^FS');
});

it('preserves legacy generation when physical dimensions are incomplete', function () {
    $serialZpl = app(SerialTemplateZplBuilder::class)->build(
        'serial',
        [],
        'UL',
        [
            'dpi' => 203,
            'width_mm' => 50.8,
            'height_mm' => null,
        ],
    );

    $ratingZpl = app(SerialTemplateZplBuilder::class)->build(
        'rating',
        ['rating_qr' => false],
        'UL',
        [
            'dpi' => 203,
            'width_mm' => 50.8,
            'height_mm' => null,
        ],
    );

    expect($serialZpl)
        ->not->toContain('^PW')
        ->not->toContain('^LH0,0')
        ->not->toContain('^LS0')
        ->toContain('^LL')
        ->and($ratingZpl)
        ->not->toContain('^PW')
        ->not->toContain('^LL')
        ->not->toContain('^LH0,0')
        ->not->toContain('^LS0');
});
