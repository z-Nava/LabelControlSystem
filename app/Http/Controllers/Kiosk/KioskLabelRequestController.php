<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kiosk\StoreKioskLabelRequestRequest;
use App\Http\Requests\Kiosk\StoreKioskLpkLabelRequestRequest;
use App\Http\Requests\Labels\LookupOracleLabelJobRequest;
use App\Models\LabelRequest;
use App\Models\User;
use App\Services\Kiosk\KioskRequisitionPrintService;
use App\Services\Labels\LabelRequestReadService;
use App\Services\Labels\LabelRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KioskLabelRequestController extends Controller
{
    public function __construct(
        private readonly LabelRequestReadService $readService,
        private readonly LabelRequestService $service,
        private readonly KioskRequisitionPrintService $printService,
    ) {}

    public function createStandard(): View
    {
        return view('kiosk.label-requests.create', $this->readService->buildKioskCreateFormData());
    }

    public function createLpk(): View
    {
        return view('kiosk.lpk-label-requests.create', $this->readService->buildKioskCreateFormData());
    }

    public function storeStandard(StoreKioskLabelRequestRequest $request): RedirectResponse
    {
        return $this->storeForKind(
            request: $request,
            kind: LabelRequest::KIND_STANDARD,
            successMessage: 'Requisición de etiquetas registrada correctamente.',
            receiptType: 'Requisición de etiquetas',
        );
    }

    public function storeLpk(StoreKioskLpkLabelRequestRequest $request): RedirectResponse
    {
        return $this->storeForKind(
            request: $request,
            kind: LabelRequest::KIND_LPK,
            successMessage: 'Requisición de etiquetas LPK registrada correctamente.',
            receiptType: 'Requisición de etiquetas LPK',
        );
    }

    public function lookup(LookupOracleLabelJobRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->lookupOracleJob($request->string('job_number')->toString()),
        );
    }

    private function storeForKind(
        StoreKioskLabelRequestRequest|StoreKioskLpkLabelRequestRequest $request,
        string $kind,
        string $successMessage,
        string $receiptType,
    ): RedirectResponse {
        $data = $request->validated();
        /** @var User $kioskUser */
        $kioskUser = $request->attributes->get('kiosk_user');
        $data['requested_by_user_id'] = $kioskUser->id;
        $data['requested_by_name'] = $kioskUser->name;

        $labelRequest = DB::transaction(function () use ($data, $kioskUser, $kind) {
            $labelRequest = $kind === LabelRequest::KIND_LPK
                ? $this->service->createKioskLpk($data)
                : $this->service->createKiosk($data, $kind);
            $this->printService->prepare($labelRequest, $kioskUser);

            return $labelRequest;
        });

        return redirect()
            ->route('kiosk.dashboard')
            ->with('success', $successMessage)
            ->with('kiosk_receipt', [
                'type' => $receiptType,
                'request_kind' => 'label',
                'request_id' => $labelRequest->id,
                'created_at' => now()->format('d/m/Y H:i'),
            ]);
    }
}
