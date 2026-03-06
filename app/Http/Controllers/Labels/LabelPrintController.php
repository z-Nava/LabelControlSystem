<?php

namespace App\Http\Controllers\Labels;

use App\Http\Controllers\Controller;
use App\Http\Requests\Labels\StoreLabelPrintBatchRequest;
use App\Models\LabelRequest;
use App\Services\Labels\LabelPrintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LabelPrintController extends Controller
{
    public function __construct(private readonly LabelPrintService $service)
    {
    }

    public function create(LabelRequest $label_request): View
    {
        return view('label_print.create', ['labelRequest' => $label_request]);
    }

    public function store(StoreLabelPrintBatchRequest $request, LabelRequest $label_request): RedirectResponse
    {
        $batch = $this->service->createBatch(
            labelRequest: $label_request,
            data: $request->validated(),
            printedByUserId: auth()->id(),
            printedByName: (string) auth()->user()?->name,
        );

        return redirect()
            ->route('label_requests.show', $label_request)
            ->with('success', 'Batch de impresión registrado.')
            ->with('batch_id', $batch->id);
    }
}
