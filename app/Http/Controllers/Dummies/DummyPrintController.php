<?php

namespace App\Http\Controllers\Dummies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dummies\StoreDummyPrintBatchRequest;
use App\Models\DummyPrintBatch;
use App\Models\DummyQrTemplate;
use App\Models\DummyRequest;
use App\Services\Dummies\DummyPrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DummyPrintController extends Controller
{
    public function __construct(
        private readonly DummyPrintService $service,
    ) {}

    public function create(Request $request, DummyRequest $dummy_request): View|RedirectResponse
    {
        $dummyRequest = $dummy_request->loadCount('items')->load('printBatches');
        $hasPrintBatch = $dummyRequest->printBatches->contains(fn ($batch) => $batch->batch_type === 'print');
        $hasPrintedPrintBatch = $dummyRequest->printBatches->contains(
            fn ($batch) => $batch->batch_type === 'print' && $batch->printed_at !== null
        );
        $mode = trim((string) $request->query('mode', ''));

        if ($mode === 'reprint' && !$hasPrintedPrintBatch) {
            return redirect()
                ->route('dummy_requests.show', $dummyRequest)
                ->with('error', 'Reimpresión por selección bloqueada: primero debes tener un batch print confirmado como impreso.');
        }

        if ($dummyRequest->status === 'completed' && $hasPrintBatch) {
            return redirect()
                ->route('dummy_requests.show', $dummyRequest)
                ->with('error', 'Esta requisición ya fue confirmada como completada. No puedes volver a entrar al centro de impresión inicial.');
        }

        return view('dummy_print.create', [
            'dummyRequest' => $dummyRequest,
            'hasPrintBatch' => $hasPrintBatch,
            'hasPrintedPrintBatch' => $hasPrintedPrintBatch,
            'mode' => $mode,
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

    public function print(DummyRequest $dummy_request, DummyPrintBatch $batch): View|RedirectResponse
    {
        abort_unless($batch->dummy_request_id === $dummy_request->id, 404);
        if ($batch->printed_at !== null) {
            return redirect()
                ->route('dummy_requests.show', $dummy_request)
                ->with('error', 'Este batch ya fue impreso y confirmado. El Centro de impresión está bloqueado para evitar duplicados.');
        }

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
            'alreadyPrinted' => $batch->printed_at !== null,
            'templatesByType' => [
                'rmt' => optional($templates->get('rmt'))->zpl,
                'rw' => optional($templates->get('rw'))->zpl,
            ],
        ]);
    }

    public function confirm(Request $request, DummyRequest $dummy_request, DummyPrintBatch $batch): JsonResponse
    {
        abort_unless((int) $batch->dummy_request_id === (int) $dummy_request->id, 404);

        $data = $request->validate([
            'printed_ok' => ['required', 'boolean'],
        ]);

        if (!$data['printed_ok']) {
            return response()->json(['message' => 'Impresión no confirmada por el cliente.'], 422);
        }

        if ($batch->printed_at !== null) {
            return response()->json([
                'message' => 'Este batch ya estaba confirmado como impreso.',
                'batch_id' => $batch->id,
                'already_printed' => true,
            ]);
        }

        $batch->forceFill([
            'printed_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Impresión confirmada correctamente.',
            'batch_id' => $batch->id,
            'printed_at' => optional($batch->printed_at)->toDateTimeString(),
            'already_printed' => false,
        ]);
    }
}
