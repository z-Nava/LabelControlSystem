<?php

use App\Models\LabelRequest;
use App\Models\LabelRequestShippingItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ViewErrorBag;

it('keeps long LPK item lists and workflow actions compact in the index table', function (): void {
    $user = new User;
    $user->forceFill([
        'id' => 10,
        'name' => 'Label Room Test',
        'employee_no' => '20000',
        'is_active' => true,
    ]);
    $user->setRelation('shift', null);
    $this->actingAs($user);

    $labelRequest = new LabelRequest;
    $labelRequest->forceFill([
        'id' => 25,
        'request_kind' => LabelRequest::KIND_LPK,
        'request_date' => '2026-08-24',
        'week' => 35,
        'leader_name' => 'Líder de prueba',
        'requested_by_name' => 'Operador de prueba',
        'job_number' => '12345678',
        'model' => 'ENSAMBLE FINAL LPK',
        'quantity_requested' => 250,
        'shipping_quantity' => 125,
        'include_serial' => false,
        'include_rating' => false,
        'include_inner' => false,
        'include_shipping' => true,
        'status' => LabelRequest::STATUS_REQUESTED,
    ]);
    $labelRequest->setRelation('line', null);
    $labelRequest->setRelation('shift', null);
    $labelRequest->setRelation('serials', new EloquentCollection);
    $labelRequest->setRelation('ratings', new EloquentCollection);
    $labelRequest->setRelation('shippingItems', new EloquentCollection(array_map(
        fn (int $position): LabelRequestShippingItem => new LabelRequestShippingItem([
            'item_reference' => sprintf('MODELO SHIPPING %02d', $position),
            'position' => $position,
        ]),
        range(1, 8),
    )));

    $paginator = new LengthAwarePaginator(
        collect([$labelRequest]),
        1,
        15,
        1,
        ['path' => route('label_requests.index')],
    );

    $html = view('label_requests.index', [
        'labelRequests' => $paginator,
        'filters' => [
            'date_from' => null,
            'date_to' => null,
            'line_id' => null,
            'shift_id' => null,
            'request_kind' => null,
            'status' => 'active',
            'sku_np' => '',
        ],
        'lines' => collect(),
        'shifts' => collect(),
        'errors' => new ViewErrorBag,
    ])->render();

    expect($html)
        ->toContain('Buscar por Job, NP o modelo')
        ->toContain('MODELO SHIPPING 01, MODELO SHIPPING 02')
        ->toContain('>+6</span>')
        ->toContain('sticky right-0')
        ->toContain('grid gap-1.5');
});
