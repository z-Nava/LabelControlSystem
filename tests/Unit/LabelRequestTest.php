<?php

use App\Http\Requests\Kiosk\StoreKioskLabelRequestRequest;
use App\Http\Requests\Kiosk\StoreKioskLpkLabelRequestRequest;
use App\Models\LabelRequest;
use App\Models\LabelRequestLpkLabelGroup;
use App\Models\LabelRequestLpkLabelItem;
use App\Models\LabelRequestLpkShippingGroup;
use App\Models\LabelRequestLpkShippingItem;
use App\Models\LabelRequestRating;
use App\Models\LabelRequestSerial;
use App\Models\LabelRequestShippingItem;
use App\Services\Kiosk\KioskRequisitionLabelZplBuilder;
use App\Services\Labels\LpkJobReservationCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

it('keeps the optional model associated with every requested part number', function () {
    $request = new LabelRequest;
    $request->forceFill([
        'request_kind' => LabelRequest::KIND_STANDARD,
        'include_serial' => true,
        'include_rating' => true,
        'include_inner' => true,
        'include_shipping' => true,
        'inner_part_number' => '950405000',
        'inner_model' => 'M12FDGS2-04',
        'shipping_part_number' => '95014300',
        'shipping_model' => null,
        'quantity_requested' => 32,
        'shipping_quantity' => 4,
    ]);
    $request->setRelation('serials', new Collection([
        new LabelRequestSerial(['part_number' => '950410000', 'model' => 'M12FDGS2-04', 'position' => 1]),
        new LabelRequestSerial(['part_number' => '950410001', 'model' => null, 'position' => 2]),
    ]));
    $request->setRelation('ratings', new Collection([
        new LabelRequestRating(['part_number' => '941941001', 'model' => 'M12FDGS2-04', 'position' => 1]),
    ]));
    $request->setRelation('shippingItems', new Collection);
    $request->setRelation('line', null);
    $request->setRelation('shift', null);

    expect($request->requestedLabelLines())->toBe([
        ['type' => 'Serial', 'part_number' => '950410000', 'model' => 'M12FDGS2-04', 'quantity' => 32],
        ['type' => 'Serial', 'part_number' => '950410001', 'model' => null, 'quantity' => 32],
        ['type' => 'Rating', 'part_number' => '941941001', 'model' => 'M12FDGS2-04', 'quantity' => 32],
        ['type' => 'Inner', 'part_number' => '950405000', 'model' => 'M12FDGS2-04', 'quantity' => 32],
        ['type' => 'Shipping', 'part_number' => '95014300', 'model' => null, 'quantity' => 4],
    ])->and((new KioskRequisitionLabelZplBuilder)->build($request))
        ->toContain('SERIAL NP / MODELO: 950410000 (M12FDGS2-04), 950410001')
        ->toContain('INNER NP / MODELO: 950405000 (M12FDGS2-04)')
        ->toContain('NP / MODELO: 95014300');
});

it('keeps a separate optional model for every lpk shipping item', function () {
    $request = new LabelRequest;
    $request->forceFill([
        'request_kind' => LabelRequest::KIND_LPK,
        'include_serial' => false,
        'include_rating' => false,
        'include_inner' => false,
        'include_shipping' => true,
        'quantity_requested' => 10,
        'shipping_quantity' => 2,
    ]);
    $request->setRelation('serials', new Collection);
    $request->setRelation('ratings', new Collection);
    $request->setRelation('shippingItems', new Collection([
        new LabelRequestShippingItem(['item_reference' => '95014300', 'model' => 'M12FDGS2-04', 'position' => 1]),
        new LabelRequestShippingItem(['item_reference' => '95014301', 'model' => null, 'position' => 2]),
    ]));

    expect($request->requestedLabelLines())->toBe([
        ['type' => 'Shipping LPK', 'part_number' => '95014300', 'model' => 'M12FDGS2-04', 'quantity' => 2],
        ['type' => 'Shipping LPK', 'part_number' => '95014301', 'model' => null, 'quantity' => 2],
    ]);
});

it('represents grouped lpk labels and one physical shipping quantity', function () {
    $request = new LabelRequest;
    $request->forceFill([
        'request_kind' => LabelRequest::KIND_LPK,
        'include_serial' => true,
        'include_rating' => false,
        'include_inner' => false,
        'include_shipping' => true,
        'quantity_requested' => 10,
        'shipping_quantity' => 12,
    ]);

    $serialGroup = new LabelRequestLpkLabelGroup([
        'label_type' => LabelRequestLpkLabelGroup::TYPE_SERIAL,
        'part_number' => '950410000',
        'position' => 1,
    ]);
    $serialGroup->setRelation('items', new Collection([
        new LabelRequestLpkLabelItem(['job_number' => '422064', 'model' => '2567', 'quantity' => 5, 'position' => 1]),
        new LabelRequestLpkLabelItem(['job_number' => '422063', 'model' => '2562', 'quantity' => 10, 'position' => 2]),
    ]));

    $shippingGroup = new LabelRequestLpkShippingGroup([
        'part_number' => '950143000',
        'quantity' => 12,
        'po_number' => '380086642',
        'destination' => 'BYHALA MFG',
        'position' => 1,
    ]);
    $shippingGroup->setRelation('items', new Collection([
        new LabelRequestLpkShippingItem(['job_number' => '422065', 'model' => '3403-W3424', 'position' => 1]),
        new LabelRequestLpkShippingItem(['job_number' => '422064', 'model' => '2567-BU3424', 'position' => 2]),
    ]));

    $request->setRelation('lpkLabelGroups', new Collection([$serialGroup]));
    $request->setRelation('lpkShippingGroups', new Collection([$shippingGroup]));

    expect($request->requestedLabelLines())->toBe([
        ['type' => 'Serial', 'part_number' => '950410000', 'model' => '2567', 'job_number' => '422064', 'quantity' => 5],
        ['type' => 'Serial', 'part_number' => '950410000', 'model' => '2562', 'job_number' => '422063', 'quantity' => 10],
        [
            'type' => 'Shipping LPK',
            'part_number' => '950143000',
            'model' => '3403-W3424, 2567-BU3424',
            'job_number' => '422065, 422064',
            'quantity' => 12,
            'po_number' => '380086642',
            'destination' => 'BYHALA MFG',
            'models_count' => 2,
        ],
    ]);
});

it('reserves the maximum quantity once when a job uses several lpk label types', function () {
    $reservations = (new LpkJobReservationCalculator)->calculate([
        ['label_type' => 'serial', 'items' => [
            ['job_number' => '422064', 'quantity' => 5],
            ['job_number' => '422063', 'quantity' => 8],
        ]],
        ['label_type' => 'rating', 'items' => [
            ['job_number' => '422064', 'quantity' => 5],
            ['job_number' => '422063', 'quantity' => 10],
        ]],
        ['label_type' => 'inner', 'items' => [
            ['job_number' => '422064', 'quantity' => 3],
        ]],
    ]);

    expect($reservations->all())->toBe([
        '422064' => 5,
        '422063' => 10,
    ])->and($reservations->sum())->toBe(15);
});

it('normalizes standard request part numbers without requiring their models', function () {
    $request = StoreKioskLabelRequestRequest::create('/', 'POST', [
        'include_serial' => '1',
        'include_inner' => '1',
        'serial_items' => [
            ['part_number' => ' 950410000 ', 'model' => ' m12fdgs2-04 '],
            ['part_number' => ' 950410001 ', 'model' => ''],
        ],
        'inner_part_number' => ' 950405000 ',
        'inner_model' => '',
        'model' => '',
    ]);

    invokePrepareForValidation($request);

    expect($request->input('serial_items'))->toBe([
        ['part_number' => '950410000', 'model' => 'M12FDGS2-04'],
        ['part_number' => '950410001', 'model' => null],
    ])->and($request->input('inner_part_number'))->toBe('950405000')
        ->and($request->input('inner_model'))->toBeNull()
        ->and($request->input('model'))->toBeNull();
});

it('normalizes grouped lpk labels and shipping metadata', function () {
    $request = StoreKioskLpkLabelRequestRequest::create('/', 'POST', [
        'lpk_label_groups' => [[
            'label_type' => ' Serial ',
            'part_number' => ' 950410000 ',
            'items' => [
                ['job_number' => ' 422064 ', 'model' => ' 2567 ', 'quantity' => '5'],
                ['job_number' => ' 422063 ', 'model' => '', 'quantity' => '10'],
            ],
        ]],
        'lpk_shipping_groups' => [[
            'part_number' => ' 950143000 ',
            'quantity' => '12',
            'po_number' => ' 380086642 ',
            'destination' => ' byhala mfg ',
            'items' => [
                ['job_number' => ' 422065 ', 'model' => ' 3403-w3424 '],
                ['job_number' => ' 422064 ', 'model' => ''],
            ],
        ]],
    ]);

    invokePrepareForValidation($request);

    expect($request->input('lpk_label_groups'))->toBe([[
        'label_type' => 'serial',
        'part_number' => '950410000',
        'items' => [
            ['job_number' => '422064', 'model' => '2567', 'quantity' => '5'],
            ['job_number' => '422063', 'model' => null, 'quantity' => '10'],
        ],
    ]])->and($request->input('lpk_shipping_groups'))->toBe([[
        'part_number' => '950143000',
        'quantity' => '12',
        'po_number' => '380086642',
        'destination' => 'BYHALA MFG',
        'items' => [
            ['job_number' => '422065', 'model' => '3403-W3424'],
            ['job_number' => '422064', 'model' => null],
        ],
    ]]);
});

function invokePrepareForValidation(FormRequest $request): void
{
    $method = new ReflectionMethod($request, 'prepareForValidation');
    $method->invoke($request);
}
