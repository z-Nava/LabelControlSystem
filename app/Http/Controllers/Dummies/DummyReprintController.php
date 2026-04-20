<?php

namespace App\Http\Controllers\Dummies;

use App\Http\Controllers\Controller;
use App\Models\DummyRequest;
use App\Services\Dummies\DummyPrintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DummyReprintController extends Controller
{
    public function __construct(
        private readonly DummyPrintService $printService,
    ) {
    }

    public function search(Request $request): View
    {
        $job = trim((string) $request->query('job', ''));
        $requestType = trim((string) $request->query('request_type', ''));

        $dummyRequests = DummyRequest::query()
            ->with(['line:id,code,name', 'shift:id,code,name'])
            ->withCount('items')
            ->withCount('printBatches')
            ->whereIn('status', DummyRequest::REPRINT_SELECTION_ELIGIBLE_STATUSES)
            ->whereHas('printBatches', fn ($query) => $query
                ->where('batch_type', 'print')
                ->whereNotNull('printed_at'))
            ->when($job !== '', fn ($query) => $query->where('job_number', 'like', "%{$job}%"))
            ->when(in_array($requestType, ['first_time', 'rework'], true), fn ($query) => $query->where('request_type', $requestType))
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('dummy_reprints.search', [
            'job' => $job,
            'requestType' => $requestType,
            'dummyRequests' => $dummyRequests,
        ]);
    }

    public function show(DummyRequest $dummy_request): View
    {
        $dummyRequest = DummyRequest::query()
            ->whereIn('status', DummyRequest::REPRINT_SELECTION_ELIGIBLE_STATUSES)
            ->whereHas('printBatches', fn ($query) => $query
                ->where('batch_type', 'print')
                ->whereNotNull('printed_at'))
            ->with([
                'line:id,code,name',
                'shift:id,code,name',
                'items' => fn ($query) => $query->orderBy('consecutive'),
                'printBatches' => fn ($query) => $query
                    ->with('printedByUser:id,name')
                    ->latest('printed_at')
                    ->latest('id')
                    ->limit(30),
            ])
            ->findOrFail($dummy_request->id);

        return view('dummy_reprints.show', [
            'dummyRequest' => $dummyRequest,
        ]);
    }

    public function store(Request $request, DummyRequest $dummy_request): RedirectResponse
    {
        abort_unless($dummy_request->canAccessSelectionReprint(), 404);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'printer_name' => ['required', 'string', 'max:255'],
            'copies' => ['required', 'integer', 'min:1', 'max:10'],
            'selected_dummy_request_item_ids' => ['nullable', 'array'],
            'selected_dummy_request_item_ids.*' => ['integer'],
        ]);

        $selectedIds = collect($data['selected_dummy_request_item_ids'] ?? [])
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return back()->withErrors([
                'selection' => 'Selecciona al menos un dummy para reimpresión.',
            ])->withInput();
        }

        $batch = $this->printService->createBatch(
            dummyRequest: $dummy_request,
            data: [
                'batch_type' => 'reprint',
                'copies' => (int) $data['copies'],
                'reason' => $data['reason'] . ' | Impresora: ' . $data['printer_name'],
                'selected_dummy_request_item_ids' => $selectedIds->all(),
            ],
            printedByUserId: auth()->id(),
            printedByName: (string) auth()->user()?->name,
        );

        return redirect()
            ->route('dummy_requests.print_batches.print', ['dummy_request' => $dummy_request, 'batch' => $batch])
            ->with('success', 'Batch de reimpresión dummy generado correctamente.');
    }
}
