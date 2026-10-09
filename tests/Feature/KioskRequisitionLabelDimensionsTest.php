<?php

use App\Models\DummyRequest;
use App\Models\KioskRequisitionPrintJob;
use App\Models\LabelRequest;
use App\Models\LabelRequestLpkLabelGroup;
use App\Models\LabelRequestLpkLabelItem;
use App\Models\LabelRequestLpkShippingGroup;
use App\Models\LabelRequestLpkShippingItem;
use App\Models\LabelRequestShippingItem;
use App\Models\MasterRequest;
use App\Models\User;
use App\Services\Kiosk\KioskDummyRequisitionLabelZplBuilder;
use App\Services\Kiosk\KioskMasterRequisitionLabelZplBuilder;
use App\Services\Kiosk\KioskRequisitionLabelZplBuilder;
use App\Services\Kiosk\KioskRequisitionPrintService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

test('Kiosk requisitions print as one portrait label on 102 by 165 mm stock at supported resolutions', function () {
    $standard = new LabelRequest([
        'request_kind' => LabelRequest::KIND_STANDARD,
        'job_number' => 'MP018751019-A2641UN-PC02',
        'quantity_requested' => 40,
        'shipping_quantity' => 40,
        'include_shipping' => true,
    ]);
    $standard->id = 1;
    $standard->setRelation('line', null);
    $standard->setRelation('serials', collect());
    $standard->setRelation('ratings', collect());
    $standard->setRelation('shippingItems', collect([
        new LabelRequestShippingItem(['item_reference' => '950143000', 'model' => '3651-21XCW']),
        new LabelRequestShippingItem(['item_reference' => '950143001', 'model' => '50-11-1841W4']),
    ]));
    $standard->setRelation('lpkLabelGroups', collect());
    $standard->setRelation('lpkShippingGroups', collect());

    $lpk = clone $standard;
    $lpk->request_kind = LabelRequest::KIND_LPK;
    $lpk->id = 2;
    $group = new LabelRequestLpkLabelGroup(['label_type' => 'serial', 'part_number' => '950410000']);
    $group->setRelation('items', collect([
        new LabelRequestLpkLabelItem(['job_number' => 'MP018751019-A2641UN-PC02', 'quantity' => 40]),
    ]));
    $lpk->setRelation('lpkLabelGroups', collect([$group]));

    $master = new MasterRequest([
        'job_assembly' => 'MP018751019-A2641UN-PC02',
        'folios_from' => 1,
        'folios_to' => 10,
        'kind' => 'new',
    ]);
    $master->id = 3;
    $master->setRelation('line', null);
    $master->setRelation('shift', null);

    $dummy = new DummyRequest([
        'job_number' => 'MP018751019-A2641UN-PC02',
        'range_from' => 1,
        'range_to' => 10,
    ]);
    $dummy->id = 4;
    $dummy->setRelation('line', null);
    $dummy->setRelation('shift', null);

    $labels = [
        [(new KioskRequisitionLabelZplBuilder), $standard],
        [(new KioskRequisitionLabelZplBuilder), $lpk],
        [(new KioskMasterRequisitionLabelZplBuilder), $master],
        [(new KioskDummyRequisitionLabelZplBuilder), $dummy],
    ];

    foreach ([203 => [815, 1319], 300 => [1205, 1949]] as $dpi => [$width, $height]) {
        foreach ($labels as [$builder, $request]) {
            $zpl = $builder->build($request, $dpi);

            expect($zpl)->toContain("^PW{$width}", "^LL{$height}", '^A0N');
            expect($zpl)->not->toContain('^A0R');
            expect(substr_count($zpl, '^XA'))->toBe(1);
            expect(substr_count($zpl, '^XZ'))->toBe(1);

            preg_match_all('/\^FO(\d+),(\d+)/', $zpl, $positions);

            expect(max(array_map('intval', $positions[1])))->toBeLessThan($width);
            expect(max(array_map('intval', $positions[2])))->toBeLessThan($height);
        }
    }

    $standardZpl = (new KioskRequisitionLabelZplBuilder)->build($standard);
    expect($standardZpl)->toContain('MP018751019-A2641UN-PC02', 'NP: 950143000', 'NP: 950143001');
});

test('LPK shipping keeps eight components on one label and references any remaining lines', function () {
    $request = new LabelRequest([
        'request_kind' => LabelRequest::KIND_LPK,
        'quantity_requested' => 96,
    ]);
    $request->id = 42;
    $request->setRelation('line', null);
    $request->setRelation('serials', collect());
    $request->setRelation('ratings', collect());
    $request->setRelation('shippingItems', collect());
    $request->setRelation('lpkLabelGroups', collect());

    $group = new LabelRequestLpkShippingGroup([
        'part_number' => '950143000',
        'quantity' => 96,
    ]);
    $group->setRelation('items', collect(range(1, 8))->map(fn (int $index) => new LabelRequestLpkShippingItem([
        'job_number' => sprintf('MP%08d-A2641UN-PC02', $index),
        'model' => '3651-21XCW',
        'po_number' => sprintf('380099%03d', $index),
        'destination' => 'BHY MFG',
    ])));
    $request->setRelation('lpkShippingGroups', collect([$group]));

    $zpl = (new KioskRequisitionLabelZplBuilder)->build($request);

    expect(substr_count($zpl, '^XA'))->toBe(1);
    expect(substr_count($zpl, '^XZ'))->toBe(1);
    expect($zpl)->not->toContain('RENGLONES MAS');
    expect($zpl)->toContain('^FDLA,LPK:42^FS');

    foreach (range(1, 8) as $index) {
        expect($zpl)->toContain(sprintf('MP%08d-A2641UN-PC02', $index));
        expect($zpl)->toContain(sprintf('380099%03d', $index));
    }

    $serialGroup = new LabelRequestLpkLabelGroup(['label_type' => 'serial', 'part_number' => '950410000']);
    $serialGroup->setRelation('items', collect([
        new LabelRequestLpkLabelItem(['job_number' => 'SERIAL-EXTRA', 'quantity' => 10]),
    ]));
    $ratingGroup = new LabelRequestLpkLabelGroup(['label_type' => 'rating', 'part_number' => '941939001']);
    $ratingGroup->setRelation('items', collect([
        new LabelRequestLpkLabelItem(['job_number' => 'RATING-EXTRA', 'quantity' => 10]),
    ]));
    $request->setRelation('lpkLabelGroups', collect([$serialGroup, $ratingGroup]));
    $group->setRelation('items', $group->items->concat([
        new LabelRequestLpkShippingItem([
            'job_number' => 'SHIPPING-EXTRA',
            'model' => '3651-21XCW',
            'po_number' => '380099009',
            'destination' => 'BHY MFG',
        ]),
    ]));

    $overflowZpl = (new KioskRequisitionLabelZplBuilder)->build($request);

    expect(substr_count($overflowZpl, '^XA'))->toBe(1);
    expect(substr_count($overflowZpl, '^XZ'))->toBe(1);
    expect($overflowZpl)->toContain('+3 RENGLONES MAS: CONSULTAR REQUISICION #000042');
    expect($overflowZpl)->toContain('RATING-EXTRA', 'SERIAL-EXTRA', 'MP00000006-A2641UN-PC02');
    expect($overflowZpl)->not->toContain('MP00000007-A2641UN-PC02', 'SHIPPING-EXTRA', '380099009');
    expect($overflowZpl)->toContain('^FDLA,RATING-EXTRA^FS');
    expect($overflowZpl)->not->toContain('^FDLA,LPK:42^FS', '^FDLA,SERIAL-EXTRA^FS');
    expect($overflowZpl)->toContain('TIPOS: Rating, Serial, Shipping LPK');
    expect($overflowZpl)->toContain('^FDRATING^FS', '^FDSERIAL^FS', '^FDSHIPPING LPK^FS');
    expect(strpos($overflowZpl, '^FDRATING^FS'))->toBeLessThan(strpos($overflowZpl, '^FDSERIAL^FS'));
    expect(strpos($overflowZpl, '^FDSERIAL^FS'))->toBeLessThan(strpos($overflowZpl, '^FDSHIPPING LPK^FS'));
});

test('claiming an older unprinted Kiosk requisition replaces its multi-label ZPL', function () {
    if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        $this->markTestSkipped('PDO SQLite is not installed.');
    }

    Schema::create('kiosk_requisition_print_jobs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('dummy_request_id');
        $table->unsignedBigInteger('requested_by_user_id');
        $table->string('token');
        $table->string('status');
        $table->unsignedInteger('attempts')->default(0);
        $table->longText('zpl');
        $table->text('last_error')->nullable();
        $table->timestamp('dispatched_at')->nullable();
        $table->timestamps();
    });

    try {
        $dummy = new DummyRequest([
            'job_number' => 'MP018751019-A2641UN-PC02',
            'range_from' => 1,
            'range_to' => 10,
        ]);
        $dummy->id = 44;
        $dummy->exists = true;
        $dummy->setRelation('line', null);
        $dummy->setRelation('shift', null);

        $user = new User;
        $user->id = 8;

        KioskRequisitionPrintJob::create([
            'dummy_request_id' => $dummy->id,
            'requested_by_user_id' => $user->id,
            'token' => 'legacy-label',
            'status' => KioskRequisitionPrintJob::STATUS_PENDING,
            'attempts' => 0,
            'zpl' => '^XA^A0R^XZ^XA^A0R^XZ',
        ]);

        $result = app(KioskRequisitionPrintService::class)->claimDummy($dummy, $user, 'legacy-label');
        $zpl = $result['job']->zpl;

        expect($result['status'])->toBe('claimed');
        expect($result['job']->attempts)->toBe(1);
        expect(substr_count($zpl, '^XA'))->toBe(1);
        expect(substr_count($zpl, '^XZ'))->toBe(1);
        expect($zpl)->toContain('^A0N', 'MP018751019-A2641UN-PC02');
        expect($zpl)->not->toContain('^A0R');
    } finally {
        Schema::dropIfExists('kiosk_requisition_print_jobs');
    }
});
