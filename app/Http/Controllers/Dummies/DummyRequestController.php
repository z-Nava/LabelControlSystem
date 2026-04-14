<?php

namespace App\Http\Controllers\Dummies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dummies\IndexDummyRequestRequest;
use App\Http\Requests\Dummies\LookupOracleDummyJobRequest;
use App\Http\Requests\Dummies\StoreDummyRequestRequest;
use App\Services\Dummies\DummyRequestReadService;
use App\Models\DummyRequest;
use App\Services\Dummies\DummyRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DummyRequestController extends Controller
{
    public function __construct(
        private readonly DummyRequestReadService $readService,
        private readonly DummyRequestService $service,
    ) {}

    public function index(IndexDummyRequestRequest $request): View
    {
        $result = $this->readService->paginateForIndex($request->validated());

        return view('dummy_requests.index', $result);
    }

    public function create(): View
    {
        return view('dummy_requests.create', $this->readService->buildCreateFormData());
    }

    public function store(StoreDummyRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['requested_by_user_id'] = auth()->id();
        $data['requested_by_name'] = (string) auth()->user()?->name;

        $dummyRequest = $this->service->create($data);

        return redirect()->route('dummy_requests.show', $dummyRequest)->with('success', 'Requisición de Dummy QR creada.');
    }

    public function show(int $id): View
    {
        $dummyRequest = $this->readService->findForShow($id);

        return view('dummy_requests.show', compact('dummyRequest'));
    }


    public function cancel(DummyRequest $dummy_request): RedirectResponse
    {
        $this->service->cancel($dummy_request);

        return redirect()->route('dummy_requests.show', $dummy_request)->with('success', 'Requisición dummy cancelada.');
    }

    public function complete(DummyRequest $dummy_request): RedirectResponse
    {
        $this->service->complete($dummy_request);

        return redirect()->route('dummy_requests.show', $dummy_request)->with('success', 'Requisición dummy completada.');
    }

    public function lookup(LookupOracleDummyJobRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->lookupOracleJob($request->string('job_number')->toString())
        );
    }
}
