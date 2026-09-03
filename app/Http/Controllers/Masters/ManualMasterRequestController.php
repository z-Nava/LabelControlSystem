<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreManualMasterRequestRequest;
use App\Http\Requests\Masters\StoreMasterRequestRequest;
use App\Services\Masters\MasterRequestPrintWorkflowService;
use App\Services\Masters\MasterRequestReadService;
use App\Services\Masters\MasterRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ManualMasterRequestController extends Controller
{
    public function __construct(
        private readonly MasterRequestService $service,
        private readonly MasterRequestReadService $readService,
        private readonly MasterRequestPrintWorkflowService $printWorkflowService,
    ) {}

    public function create(): View
    {
        return view(
            'master_requests.manual_create',
            $this->readService->buildLabelRoomCreateFormData(),
        );
    }

    public function store(StoreManualMasterRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $submissionAction = $data['submission_action'];
        unset($data['submission_action']);

        $data['requested_by_user_id'] = $request->user()?->id;
        $data['requested_by_name'] = (string) $request->user()?->name;

        if ($submissionAction === StoreMasterRequestRequest::ACTION_SAVE_AND_PRINT) {
            $batch = $this->printWorkflowService->createAndStartManualPrint(
                data: $data,
                userId: $request->user()?->id,
                userName: (string) $request->user()?->name,
            );

            return redirect()->route('master_print_batches.print', $batch);
        }

        $masterRequest = $this->service->createManual($data);

        return redirect()
            ->route('master_requests.show', $masterRequest)
            ->with('success', 'Requisición Master Manual creada.');
    }
}
