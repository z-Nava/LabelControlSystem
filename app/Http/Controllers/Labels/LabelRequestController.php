<?php

namespace App\Http\Controllers\Labels;

use App\Http\Controllers\Controller;
use App\Http\Requests\Labels\IndexLabelRequestRequest;
use App\Http\Requests\Labels\LookupOracleLabelJobRequest;
use App\Http\Requests\Labels\StoreLabelRequestRequest;
use App\Models\LabelRequest;
use App\Services\Labels\LabelRequestReadService;
use App\Services\Labels\LabelRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelRequestController extends Controller
{
    public function __construct(
        private readonly LabelRequestReadService $readService,
        private readonly LabelRequestService $service,
    ) {}

    public function index(IndexLabelRequestRequest $request): View
    {
        $result = $this->readService->paginateForIndex($request->validated());

        return view('label_requests.index', $result);
    }

    public function create(): View
    {
        return view('label_requests.create', $this->readService->buildCreateFormData());
    }

    public function store(StoreLabelRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['requested_by_user_id'] = auth()->id();
        $data['requested_by_name'] = (string) auth()->user()?->name;

        $labelRequest = $this->service->create($data);

        return redirect()->route('label_requests.show', $labelRequest)->with('success', 'Requisición de etiquetas creada.');
    }

    public function show(int $id): View
    {
        $labelRequest = $this->readService->findForShow($id);

        return view('label_requests.show', compact('labelRequest'));
    }

    public function lookup(LookupOracleLabelJobRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->lookupOracleJob($request->string('job_number')->toString())
        );
    }

    public function cancel(LabelRequest $label_request): RedirectResponse
    {
        $this->service->cancel($label_request);

        return redirect()->route('label_requests.show', $label_request)->with('success', 'Requisición cancelada.');
    }

    public function complete(Request $request, LabelRequest $label_request): RedirectResponse
    {
        $this->service->complete(
            $label_request,
            $request->boolean('force_without_printed_batch'),
        );

        return redirect()->route('label_requests.show', $label_request)->with('success', 'Requisición completada.');
    }
}
