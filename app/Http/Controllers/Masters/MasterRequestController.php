<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\CancelMasterRequestRequest;
use App\Http\Requests\Masters\IndexMasterRequestRequest;
use App\Http\Requests\Masters\LookupOracleJobRequest;
use App\Http\Requests\Masters\StoreMasterRequestRequest;
use App\Models\MasterRequest;
use App\Services\Masters\MasterRequestPrintWorkflowService;
use App\Services\Masters\MasterRequestReadService;
use App\Services\Masters\MasterRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MasterRequestController extends Controller
{
    public function __construct(
        private readonly MasterRequestService $service,
        private readonly MasterRequestReadService $readService,
        private readonly MasterRequestPrintWorkflowService $printWorkflowService,
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
        $formData = $this->readService->buildLabelRoomCreateFormData();

        return view('master_requests.create', $formData);
    }

    public function store(StoreMasterRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $submissionAction = $data['submission_action'];
        unset($data['submission_action']);

        $data['requested_by_user_id'] = auth()->id();
        $data['requested_by_name'] = (string) auth()->user()?->name;

        if ($submissionAction === StoreMasterRequestRequest::ACTION_SAVE_AND_PRINT) {
            $batch = $this->printWorkflowService->createAndStartInitialPrint(
                data: $data,
                requestSource: MasterRequest::SOURCE_LABEL_ROOM,
                userId: auth()->id(),
                userName: (string) auth()->user()?->name,
            );

            return redirect()->route('master_print_batches.print', $batch);
        }

        $mr = $this->service->create($data, MasterRequest::SOURCE_LABEL_ROOM);

        return redirect()
            ->route('master_requests.show', $mr)
            ->with('success', 'Requisición Master creada.');
    }

    public function show(int $id): View
    {
        $mr = $this->readService->findForShow($id);

        return view('master_requests.show', compact('mr'));
    }

    public function cancel(
        CancelMasterRequestRequest $request,
        MasterRequest $master_request,
    ): RedirectResponse {
        $this->service->cancel(
            masterRequest: $master_request,
            reason: $request->string('cancellation_reason')->toString(),
            cancelledByUserId: $request->user()?->id,
            cancelledByName: (string) $request->user()?->name,
        );

        return redirect()
            ->back()
            ->with('success', "Requisición Master #{$master_request->id} cancelada.");
    }

    // Endpoint para autollenar (AJAX)
    public function lookup(LookupOracleJobRequest $request)
    {
        return response()->json(
            $this->service->lookupOracleJob(
                $request->string('job_number')->toString(),
                includeLabelRoomState: true,
            )
        );
    }
}
