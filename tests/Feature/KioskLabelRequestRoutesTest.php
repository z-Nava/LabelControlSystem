<?php

use App\Http\Controllers\Kiosk\KioskLabelRequestController;
use App\Http\Requests\Kiosk\StoreKioskLabelRequestRequest;
use App\Http\Requests\Kiosk\StoreKioskLpkLabelRequestRequest;
use App\Models\LabelRequest;
use App\Models\User;
use App\Services\Kiosk\KioskRequisitionPrintService;
use App\Services\Labels\LabelRequestReadService;
use App\Services\Labels\LabelRequestService;
use Illuminate\Support\Facades\DB;

it('routes each kiosk label variant to its explicit controller action', function (string $routeName, string $action): void {
    $route = app('router')->getRoutes()->getByName($routeName);

    expect($route)->not->toBeNull()
        ->and($route->getActionName())->toBe(KioskLabelRequestController::class.'@'.$action);
})->with([
    'standard create' => ['kiosk.label_requests.create', 'createStandard'],
    'standard store' => ['kiosk.label_requests.store', 'storeStandard'],
    'standard lookup' => ['kiosk.label_requests.lookup_job', 'lookup'],
    'LPK create' => ['kiosk.lpk_label_requests.create', 'createLpk'],
    'LPK store' => ['kiosk.lpk_label_requests.store', 'storeLpk'],
    'LPK lookup' => ['kiosk.lpk_label_requests.lookup_job', 'lookup'],
]);

it('stores each kiosk label variant with the corresponding request kind', function (
    string $requestClass,
    string $action,
    string $kind,
    string $successMessage,
    string $receiptType,
): void {
    $readService = Mockery::mock(LabelRequestReadService::class);
    $labelRequestService = Mockery::mock(LabelRequestService::class);
    $printService = Mockery::mock(KioskRequisitionPrintService::class);

    DB::shouldReceive('transaction')
        ->once()
        ->andReturnUsing(fn (Closure $callback) => $callback());

    $kioskUser = new User;
    $kioskUser->forceFill(['id' => 41, 'name' => 'Kiosk Operator']);

    $createdRequest = new LabelRequest;
    $createdRequest->forceFill(['id' => 73]);

    $request = Mockery::mock($requestClass)->makePartial();
    $request->initialize();
    $request->attributes->set('kiosk_user', $kioskUser);
    $request->shouldReceive('validated')->once()->andReturn([
        'job_number' => 'JOB-100',
    ]);

    $labelRequestService
        ->shouldReceive('createKiosk')
        ->once()
        ->with(
            Mockery::on(fn (array $data): bool => $data === [
                'job_number' => 'JOB-100',
                'requested_by_user_id' => 41,
                'requested_by_name' => 'Kiosk Operator',
            ]),
            $kind,
        )
        ->andReturn($createdRequest);

    $printService
        ->shouldReceive('prepare')
        ->once()
        ->with($createdRequest, $kioskUser);

    $controller = new KioskLabelRequestController(
        $readService,
        $labelRequestService,
        $printService,
    );

    $response = $controller->{$action}($request);
    $receipt = $response->getSession()->get('kiosk_receipt');

    expect($response->getTargetUrl())->toBe(route('kiosk.dashboard'))
        ->and($response->getSession()->get('success'))->toBe($successMessage)
        ->and($receipt['type'])->toBe($receiptType)
        ->and($receipt['request_kind'])->toBe('label')
        ->and($receipt['request_id'])->toBe(73);
})->with([
    'standard' => [
        StoreKioskLabelRequestRequest::class,
        'storeStandard',
        LabelRequest::KIND_STANDARD,
        'Requisición de etiquetas registrada correctamente.',
        'Requisición de etiquetas',
    ],
    'LPK' => [
        StoreKioskLpkLabelRequestRequest::class,
        'storeLpk',
        LabelRequest::KIND_LPK,
        'Requisición de etiquetas LPK registrada correctamente.',
        'Requisición de etiquetas LPK',
    ],
]);
