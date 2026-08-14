<?php

use App\Models\OracleJob;
use App\Services\Oracle\OracleJobService;

test('supported assembly part number prefixes are valid for master assembly', function (string $assembly) {
    $job = new OracleJob([
        'assembly' => $assembly,
        'line' => 'ASSEMBLY',
    ]);

    expect((new OracleJobService)->isAssemblyJob($job))->toBeTrue();
})->with([
    '290 prefix' => '290061329',
    '399 prefix' => '399000001',
]);
