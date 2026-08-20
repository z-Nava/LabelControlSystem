<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
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

class KioskLpkLabelRequestController extends Controller
{
    public function __construct(
        private readonly LabelRequestReadService $readService,
        private readonly LabelRequestService $service,
        private readonly KioskRequisitionPrintService $printService,
    ) {}

    public function create(): View
    {
        return view('kiosk.lpk-label-requests.create', $this->readService->buildKioskCreateFormData());
    }

    public function store(StoreKioskLpkLabelRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        /** @var User $kioskUser */
        $kioskUser = $request->attributes->get('kiosk_user');
        $data['requested_by_user_id'] = $kioskUser->id;
        $data['requested_by_name'] = $kioskUser->name;

        $labelRequest = DB::transaction(function () use ($data, $kioskUser) {
            $labelRequest = $this->service->createKiosk($data, LabelRequest::KIND_LPK);
            $this->printService->prepare($labelRequest, $kioskUser);

            return $labelRequest;
        });

        return redirect()
            ->route('kiosk.dashboard')
            ->with('success', 'Requisición de etiquetas LPK registrada correctamente.')
            ->with('kiosk_receipt', [
                'type' => 'Requisición de etiquetas LPK',
                'request_kind' => 'label',
                'request_id' => $labelRequest->id,
                'created_at' => now()->format('d/m/Y H:i'),
            ]);
    }

    public function lookup(LookupOracleLabelJobRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->lookupOracleJob($request->string('job_number')->toString()),
        );
    }
}
