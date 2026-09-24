<?php

use App\Models\LabelRequest;
use App\Models\User;
use App\Services\Catalogs\RatingAssemblyMappingService;
use App\Services\Labels\LabelFolioService;
use App\Services\Labels\LabelRoomAdministrationService;
use App\Services\Labels\LabelWorkDefinitionService;
use App\Support\SerialPeriods;

afterEach(fn () => Mockery::close());

function proposedRangeForSelectedPeriod(string $market, int $week, ?int $month, int $next): array
{
    $request = new LabelRequest(['status' => LabelRequest::STATUS_REQUESTED, 'folio_mode' => 'new']);
    $request->id = 4;
    $line = [
        'source_key' => 'line_0', 'label_type' => 'rating', 'part_number' => '941923002',
        'rating_part_number' => '941923002', 'folio_family' => null,
        'catalog_rating' => null, 'catalog_market' => $market, 'assembly_number' => '018921001',
        'job_number' => 'JOB-1', 'model' => '3053-20', 'quantity' => 3280,
        'requires_folios' => true,
    ];
    $definitions = Mockery::mock(LabelWorkDefinitionService::class);
    $definitions->shouldReceive('forRequest')->with($request)->andReturn(collect(['line_0' => $line]));
    $folios = Mockery::mock(LabelFolioService::class);
    $folios->shouldReceive('nextNumber')->once()->with(
        '941923002', $market, SerialPeriods::forMarket($market), 2026,
        $market === 'UL' ? $week : $month,
    )->andReturn($next);
    $mappings = Mockery::mock(RatingAssemblyMappingService::class);
    $actor = Mockery::mock(User::class);
    $actor->shouldReceive('isLabelRoomLeader')->andReturn(false);

    $proposal = (new LabelRoomAdministrationService($definitions, $folios, $mappings))->proposal($request, [
        'serial_standard' => $market, 'control_year' => 2026, 'control_week' => $week,
        'serial_month' => $month, 'job_status' => 'P',
        'tasks' => ['line_0' => ['evidence_position' => 'first']],
    ], $actor);

    return array_values($proposal['bundles'])[0];
}

it('continues within the week selected by the label room and resets in another week', function () {
    $week38 = proposedRangeForSelectedPeriod('UL', 38, null, 3171);
    expect($week38['range_start'])->toBe(3171)
        ->and($week38['range_end'])->toBe(6451)
        ->and($week38['period_number'])->toBe(38);

    $week40 = proposedRangeForSelectedPeriod('UL', 40, null, 1);
    expect($week40['range_start'])->toBe(1)
        ->and($week40['range_end'])->toBe(3281)
        ->and($week40['period_number'])->toBe(40);
});

it('uses the month selected by the label room for monthly markets', function () {
    $august = proposedRangeForSelectedPeriod('EMEA', 38, 8, 701);
    expect($august['range_start'])->toBe(701)
        ->and($august['range_end'])->toBe(3981)
        ->and($august['period_type'])->toBe(SerialPeriods::MONTH)
        ->and($august['period_number'])->toBe(8);
});
