<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreMasterReworkRequest;
use App\Models\MasterRequest;
use App\Services\Masters\MasterReworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MasterReworkController extends Controller
{
    public function __construct(private readonly MasterReworkService $service) {}

    public function create(MasterRequest $master_request): View
    {
        return view('master_reworks.create', $this->service->buildCreateFormData($master_request));
    }

    public function store(
        StoreMasterReworkRequest $request,
        MasterRequest $master_request,
    ): RedirectResponse {
        $revision = $this->service->createRevision(
            baseRequest: $master_request,
            data: $request->validated(),
            userId: $request->user()?->id,
            userName: (string) $request->user()?->name,
        );

        return redirect()
            ->route('master_reworks.show', $revision)
            ->with('success', "Revisión R{$revision->revision_number} guardada. Verifica el resumen antes de imprimir.");
    }

    public function show(MasterRequest $master_request): View
    {
        $revision = $this->service->findRevisionForSummary($master_request);

        return view('master_reworks.show', compact('revision'));
    }

    public function print(MasterRequest $master_request): RedirectResponse
    {
        $batch = $this->service->createInitialPrintBatch(
            revision: $master_request,
            userId: auth()->id(),
            userName: (string) auth()->user()?->name,
        );

        return redirect()->route('master_print_batches.print', $batch);
    }
}
