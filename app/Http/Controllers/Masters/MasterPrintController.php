<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreMasterPrintBatchRequest;
use App\Models\MasterPrintBatch;
use App\Models\MasterRequest;
use App\Services\Masters\MasterPrintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MasterPrintController extends Controller
{
    public function __construct(private readonly MasterPrintService $service)
    {
    }

    public function create(MasterRequest $master_request): View
    {
        $mr = $master_request->load([
            'line',
            'shift',
            'folios' => fn ($q) => $q->orderBy('folio_number'),
        ]);

        return view('master_print.create', compact('mr'));
    }

    public function store(StoreMasterPrintBatchRequest $request, MasterRequest $master_request): RedirectResponse
    {
        $data = $request->validated();

        // reason obligatorio si es reprint/rework
        if (
            in_array($data['batch_type'], ['reprint', 'rework'], true)
            && empty(trim((string) ($data['reason'] ?? '')))
        ) {
            return back()
                ->withErrors(['reason' => 'El motivo es obligatorio para reimpresión o retrabajo.'])
                ->withInput();
        }

        $batch = $this->service->createBatch(
            masterRequest: $master_request,
            folioIds: $data['folio_ids'],
            batchType: $data['batch_type'],
            copies: (int) $data['copies'],
            reason: $data['reason'] ?? null,
            printedByUserId: auth()->id(),
            printedByName: (string) auth()->user()?->name
        );

        return redirect()
            ->route('master_requests.show', $master_request)
            ->with('success', "Batch creado (#{$batch->id}). Listo para imprimir.")
            ->with('batch_id', $batch->id)
            ->with('pdf_url', route('master_print_batches.pdf', $batch));
    }

    public function pdf(MasterPrintBatch $batch)
    {
        return $this->service->downloadPdf($batch);
    }

    public function print(MasterPrintBatch $batch)
    {
        return $this->service->renderPrintable($batch);
    }
}
