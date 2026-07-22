<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dummies\LookupOracleDummyJobRequest;
use App\Http\Requests\Dummies\StoreDummyRequestRequest;
use App\Services\Dummies\DummyRequestReadService;
use App\Services\Dummies\DummyRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KioskDummyRequestController extends Controller
{
    public function __construct(
        private readonly DummyRequestReadService $readService,
        private readonly DummyRequestService $service,
    ) {}

    public function create(): View
    {
        return view('kiosk.dummy-requests.create', $this->readService->buildCreateFormData());
    }

    public function store(StoreDummyRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['requested_by_user_id'] = null;
        $data['requested_by_name'] = $request->session()->get('kiosk_employee_no');

        $dummyRequest = $this->service->create($data);

        return redirect()
            ->route('kiosk.dashboard')
            ->with('success', 'Requisición Dummy QR registrada correctamente.')
            ->with('kiosk_receipt', [
                'type' => 'Requisición Dummy QR',
                'request_id' => $dummyRequest->id,
                'created_at' => now()->format('d/m/Y H:i'),
            ]);
    }

    public function lookup(LookupOracleDummyJobRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->lookupOracleJob($request->string('job_number')->toString()),
        );
    }
}
