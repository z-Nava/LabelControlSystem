<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\IndexMasterRequestRequest;
use App\Http\Requests\Masters\LookupOracleJobRequest;
use App\Http\Requests\Masters\StoreMasterRequestRequest;
use App\Services\Masters\MasterRequestService;
use App\Models\MasterRequest;
use App\Services\Masters\MasterRequestReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MasterRequestController extends Controller
{
    public function __construct(
        private readonly MasterRequestService $service,
        private readonly MasterRequestReadService $readService,
    ) {}

    public function index(IndexMasterRequestRequest $request): View
    {
        $result = $this->readService->paginateForIndex($request->validated());

        return view('master_requests.index', [
            'masterRequests' => $result['masterRequests'],
            'filters' => $result['filters'],
        ]);
    }

    public function create(): View
    {
        $formData = $this->readService->buildCreateFormData();

        return view('master_requests.create', $formData);
    }

    public function store(StoreMasterRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // ✅ trazabilidad (del sistema, no del form)
        $data['requested_by_user_id'] = auth()->id();
        $data['requested_by_name'] = (string) auth()->user()?->name;

        $mr = $this->service->create($data);

        return redirect()
            ->route('master_requests.show', $mr)
            ->with('success', 'Requisición Master creada.');
    }

    public function show(int $id): View
    {
        $mr = $this->readService->findForShow($id);

        return view('master_requests.show', compact('mr'));
    }

    // Endpoint para autollenar (AJAX)
    public function lookup(LookupOracleJobRequest $request)
    {
        return response()->json(
            $this->service->lookupOracleJob($request->string('job_number')->toString())
        );
    }
}
