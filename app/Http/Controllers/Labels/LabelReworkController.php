<?php

namespace App\Http\Controllers\Labels;

use App\Http\Controllers\Controller;
use App\Models\LabelRequest;
use App\Models\SerialUnit;
use App\Services\Labels\LabelPrintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LabelReworkController extends Controller
{
    public function __construct(
        private readonly LabelPrintService $printService,
    ) {
    }

    public function search(Request $request): View
    {
        $job = trim((string) $request->query('job', ''));

        $labelRequests = $this->buildSearchQuery($job)->paginate(15)->withQueryString();

        return view('label_reworks.search', [
            'job' => $job,
            'labelRequests' => $labelRequests,
        ]);
    }

    public function show(LabelRequest $label_request): View
    {
        $labelRequest = LabelRequest::query()
            ->with([
                'line:id,code,name',
                'shift:id,code,name',
                'serialRanges' => fn ($query) => $query->orderBy('range_start'),
                'printBatches' => fn ($query) => $query->latest('printed_at')->latest('id')->limit(20),
            ])
            ->findOrFail($label_request->id);

        $availableUnits = $this->loadAvailableUnits($labelRequest);

        return view('label_reworks.show', [
            'labelRequest' => $labelRequest,
            'availableUnits' => $availableUnits,
        ]);
    }

    public function store(Request $request, LabelRequest $label_request): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'printer_name' => ['required', 'string', 'max:255'],
            'selected_serial_unit_ids' => ['nullable', 'array'],
            'selected_serial_unit_ids.*' => ['integer'],
            'selected_rating_unit_ids' => ['nullable', 'array'],
            'selected_rating_unit_ids.*' => ['integer'],
        ]);

        $serialIds = collect($data['selected_serial_unit_ids'] ?? [])->filter()->unique()->values();
        $ratingIds = collect($data['selected_rating_unit_ids'] ?? [])->filter()->unique()->values();

        if ($serialIds->isEmpty() && $ratingIds->isEmpty()) {
            return back()->withErrors([
                'selection' => 'Selecciona al menos un serial o un rating para retrabajo/reimpresión.',
            ])->withInput();
        }

        $batch = $this->printService->createBatch(
            labelRequest: $label_request,
            data: [
                'batch_type' => 'rework',
                'copies' => 1,
                'print_serial' => $serialIds->isNotEmpty(),
                'print_rating' => $ratingIds->isNotEmpty(),
                'reason' => $data['reason'] . ' | Impresora: ' . $data['printer_name'],
                'selected_serial_unit_ids' => $serialIds->all(),
                'selected_rating_unit_ids' => $ratingIds->all(),
            ],
            printedByUserId: auth()->id(),
            printedByName: (string) auth()->user()?->name,
        );

        return redirect()
            ->route('label_requests.print_batches.print', ['label_request' => $label_request, 'batch' => $batch])
            ->with('success', 'Batch de retrabajo generado correctamente.');
    }

    private function buildSearchQuery(string $job)
    {
        return LabelRequest::query()
            ->select('label_requests.*')
            ->with(['line:id,code,name', 'shift:id,code,name'])
            ->withCount('printBatches')
            ->withMin('serialRanges', 'range_start')
            ->withMax('serialRanges', 'range_end')
            ->selectSub(function ($query) {
                $query->from('serial_ranges as sr')
                    ->join('serial_units as su', function ($join) {
                        $join->on('su.serial_week_id', '=', 'sr.serial_week_id')
                            ->on('su.serial_number', '=', 'sr.range_start');
                    })
                    ->whereColumn('sr.label_request_id', 'label_requests.id')
                    ->orderBy('sr.range_start')
                    ->limit(1)
                    ->select('su.serial_full');
            }, 'serial_start_full')
            ->selectSub(function ($query) {
                $query->from('serial_ranges as sr')
                    ->join('serial_units as su', function ($join) {
                        $join->on('su.serial_week_id', '=', 'sr.serial_week_id')
                            ->on('su.serial_number', '=', 'sr.range_end');
                    })
                    ->whereColumn('sr.label_request_id', 'label_requests.id')
                    ->orderByDesc('sr.range_end')
                    ->limit(1)
                    ->select('su.serial_full');
            }, 'serial_end_full')
            ->where('status', 'completed')
            ->when($job !== '', fn ($query) => $query->where('job_number', 'like', "%{$job}%"))
            ->orderByDesc('request_date')
            ->orderByDesc('id');
    }

    /**
     * @return array{serial: Collection<int, SerialUnit>, rating: Collection<int, SerialUnit>}
     */
    private function loadAvailableUnits(LabelRequest $labelRequest): array
    {
        $ranges = $labelRequest->serialRanges;

        if ($ranges->isEmpty()) {
            return [
                'serial' => collect(),
                'rating' => collect(),
            ];
        }

        $weekId = (int) $ranges->first()->serial_week_id;

        $units = SerialUnit::query()
            ->where('serial_week_id', $weekId)
            ->where(function ($query) use ($ranges) {
                foreach ($ranges as $range) {
                    $query->orWhereBetween('serial_number', [$range->range_start, $range->range_end]);
                }
            })
            ->orderBy('serial_number')
            ->get(['id', 'serial_number', 'serial_full', 'status']);

        return [
            'serial' => $labelRequest->include_serial ? $units : collect(),
            'rating' => $labelRequest->include_rating ? $units : collect(),
        ];
    }
}
