<?php

namespace App\Http\Controllers\Labels;

use App\Http\Controllers\Controller;
use App\Http\Requests\Labels\IndexLabelRequestRequest;
use App\Models\LabelRequest;
use App\Services\Labels\LabelRequestReadService;
use App\Services\Labels\LabelRequestService;
use Illuminate\Http\RedirectResponse;
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

    public function show(int $id): View
    {
        return view('label_requests.show', $this->readService->buildShowViewData($id));
    }

    public function requisitionSheet(LabelRequest $label_request): View
    {
        $labelRequest = $this->readService->findForShow($label_request->id);

        return view('label_requests.requisition_sheet', compact('labelRequest'));
    }

    public function cancel(LabelRequest $label_request): RedirectResponse
    {
        $this->service->cancel($label_request, auth()->id());

        return back()->with('success', 'Requisición cancelada.');
    }

    public function startPreparation(LabelRequest $label_request): RedirectResponse
    {
        $this->service->startPreparation($label_request, auth()->id());

        return back()->with('success', 'Preparación de la requisición iniciada.');
    }

    public function readyForDelivery(LabelRequest $label_request): RedirectResponse
    {
        $this->service->markReadyForDelivery($label_request, auth()->id());

        return back()->with('success', 'Requisición lista para entregar.');
    }

    public function deliver(LabelRequest $label_request): RedirectResponse
    {
        $this->service->confirmDelivery($label_request, auth()->id());

        return back()->with('success', 'Entrega de la requisición confirmada.');
    }
}
