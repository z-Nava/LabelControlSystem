<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\LookupOracleJobRequest;
use App\Http\Requests\Masters\StoreMasterRequestRequest;
use App\Services\Masters\MasterRequestReadService;
use App\Services\Masters\MasterRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KioskMasterRequestController extends Controller
{
    public function __construct(
        private readonly MasterRequestService $service,
        private readonly MasterRequestReadService $readService,
    ) {}

    public function create(): View
    {
        return view('kiosk.master-requests.create', $this->readService->buildCreateFormData());
    }

    public function store(StoreMasterRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['requested_by_user_id'] = null;
        $data['requested_by_name'] = $request->session()->get('kiosk_employee_no');

        $masterRequest = $this->service->create($data);

        return redirect()
            ->route('kiosk.dashboard')
            ->with('success', 'Requisición Master registrada correctamente.')
            ->with('kiosk_receipt', [
                'type' => 'Requisición Master',
                'request_id' => $masterRequest->id,
                'created_at' => now()->format('d/m/Y H:i'),
            ]);
    }

    public function lookup(LookupOracleJobRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->lookupOracleJob($request->string('job_number')->toString()),
        );
    }
}
