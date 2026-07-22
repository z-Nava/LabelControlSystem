<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Labels\LookupOracleLabelJobRequest;
use App\Http\Requests\Labels\StoreLabelRequestRequest;
use App\Services\Labels\LabelRequestReadService;
use App\Services\Labels\LabelRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KioskLabelRequestController extends Controller
{
    public function __construct(
        private readonly LabelRequestReadService $readService,
        private readonly LabelRequestService $service,
    ) {}

    public function create(): View
    {
        return view('kiosk.label-requests.create', $this->readService->buildCreateFormData());
    }

    public function store(StoreLabelRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['requested_by_user_id'] = null;
        $data['requested_by_name'] = $request->session()->get('kiosk_employee_no');

        $labelRequest = $this->service->create($data);

        return redirect()
            ->route('kiosk.dashboard')
            ->with('success', 'Requisición de etiquetas registrada correctamente.')
            ->with('kiosk_receipt', [
                'type' => 'Requisición de etiquetas',
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
