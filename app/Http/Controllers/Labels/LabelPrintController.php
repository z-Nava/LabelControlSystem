<?php

namespace App\Http\Controllers\Labels;

use App\Http\Controllers\Controller;
use App\Http\Requests\Labels\StoreLabelPrintBatchRequest;
use App\Models\LabelPrintBatch;
use App\Models\LabelRequest;
use App\Services\Labels\LabelBatchPrintExecutionService;
use App\Services\Labels\LabelPrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelPrintController extends Controller
{
    public function __construct(
        private readonly LabelPrintService $service,
        private readonly LabelBatchPrintExecutionService $batchExecutionService,
    ) {
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
            ->route('label_requests.print_batches.print', ['label_request' => $label_request, 'batch' => $batch])
            ->with('success', 'Batch de impresión registrado.');
    }

    public function printCenter(LabelRequest $label_request, LabelPrintBatch $batch): View
    {
        abort_unless((int) $batch->label_request_id === (int) $label_request->id, 404);

        $batch->load(['items']);

        return view('label_print.print_center', [
            'labelRequest' => $label_request,
            'batch' => $batch,
        ]);
    }

    public function preview(LabelRequest $label_request, LabelPrintBatch $batch): JsonResponse
    {
        abort_unless((int) $batch->label_request_id === (int) $label_request->id, 404);

        return response()->json($this->batchExecutionService->buildPreview($batch));
    }

    public function confirm(Request $request, LabelRequest $label_request, LabelPrintBatch $batch): JsonResponse
    {
        abort_unless((int) $batch->label_request_id === (int) $label_request->id, 404);

        $data = $request->validate([
            'printed_ok' => ['required', 'boolean'],
        ]);

        if (!$data['printed_ok']) {
            return response()->json(['message' => 'Impresión no confirmada por el cliente.'], 422);
        }

        $result = $this->batchExecutionService->confirmPrinted($batch);

        return response()->json($result);
    }
}
