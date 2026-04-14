<?php

namespace App\Http\Controllers\Dummies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dummies\StoreDummyPrintBatchRequest;
use App\Models\DummyPrintBatch;
use App\Models\DummyQrTemplate;
use App\Models\DummyRequest;
use App\Services\Dummies\DummyPrintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DummyPrintController extends Controller
{
    public function __construct(
        private readonly DummyPrintService $service,
    ) {}

    public function create(DummyRequest $dummy_request): View
    {
        $dummyRequest = $dummy_request->loadCount('items')->load('printBatches');

        return view('dummy_print.create', [
            'dummyRequest' => $dummyRequest,
            'hasPrintBatch' => $dummyRequest->printBatches->contains(fn ($batch) => $batch->batch_type === 'print'),
        ]);
    }

    public function store(StoreDummyPrintBatchRequest $request, DummyRequest $dummy_request): RedirectResponse
    {
        $batch = $this->service->createBatch(
            $dummy_request,
            $request->validated(),
            auth()->id(),
            (string) auth()->user()?->name,
        );

        return redirect()->route('dummy_requests.print_batches.print', ['dummy_request' => $dummy_request, 'batch' => $batch])
            ->with('success', 'Batch dummy generado correctamente.');
    }

    public function print(DummyRequest $dummy_request, DummyPrintBatch $batch): View
    {
        abort_unless($batch->dummy_request_id === $dummy_request->id, 404);

        $batch->load([
            'dummyRequest.line:id,code,name',
            'dummyRequest.shift:id,code,name',
            'items.requestItem:id,dummy_request_id,consecutive,consecutive_10d,dummy_type,qr_payload',
        ]);

        $templates = DummyQrTemplate::query()
            ->whereIn('dummy_type', ['rmt', 'rw'])
            ->where('is_active', true)
            ->get()
            ->keyBy('dummy_type');

        return view('dummy_print.print', [
            'dummyRequest' => $dummy_request,
            'batch' => $batch,
            'templatesByType' => [
                'rmt' => optional($templates->get('rmt'))->zpl,
                'rw' => optional($templates->get('rw'))->zpl,
            ],
        ]);
    }
}
