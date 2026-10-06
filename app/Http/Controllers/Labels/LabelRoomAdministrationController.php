<?php

namespace App\Http\Controllers\Labels;

use App\Http\Controllers\Controller;
use App\Models\LabelJobEntry;
use App\Models\LabelRequest;
use App\Models\LabelWorkTask;
use App\Models\ProductionLine;
use App\Models\RatingAssemblyMapping;
use App\Models\SerialPeriod;
use App\Models\SerialRange;
use App\Models\Shift;
use App\Services\Catalogs\RatingAssemblyMappingService;
use App\Services\Labels\LabelFolioService;
use App\Services\Labels\LabelRoomAdministrationService;
use App\Services\Labels\LabelWorkDefinitionService;
use App\Support\SerialPeriods;
use App\Support\SerialStandards;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LabelRoomAdministrationController extends Controller
{
    private const JOB_LABEL_TYPE_CODES = [
        'shipping' => 'SH',
        'serial' => 'SE',
        'rating' => 'RA',
        'inner' => 'INN',
    ];

    public function __construct(
        private readonly LabelRoomAdministrationService $service,
        private readonly LabelWorkDefinitionService $definitions,
        private readonly RatingAssemblyMappingService $ratingMappings,
    ) {}

    public function review(Request $request, LabelRequest $label_request)
    {
        $preview = $request->session()->get('label_review_proposals.'.$label_request->id, []);

        return view('label_requests.review', [
            ...$this->reviewData($label_request, $preview['input'] ?? []),
            ...$preview,
        ]);
    }

    public function previewRedirect(LabelRequest $label_request)
    {
        return redirect()->route('label_requests.review', $label_request);
    }

    private function reviewData(LabelRequest $labelRequest, array $input = []): array
    {
        $lines = $this->definitions->forRequest($labelRequest);
        $values = $input ?: session()->getOldInput();
        $today = now(config('app.display_timezone'));
        $market = strtoupper(trim((string) ($values['serial_standard'] ?? $labelRequest->serial_standard ?? '')));
        if (! in_array($market, SerialStandards::all(), true)) {
            $inferredMarkets = $lines
                ->filter(fn ($line) => $line['requires_folios'])
                ->map(fn ($line) => $line['catalog_market'] ?? $this->ratingMappings->resolveMarket(
                    $line['assembly_number'],
                    [$line['rating_part_number']],
                ))
                ->filter()
                ->unique();
            $market = $inferredMarkets->count() === 1 ? $inferredMarkets->first() : '';
        }
        $periodType = $market ? SerialPeriods::forMarket($market) : SerialPeriods::WEEK;
        $defaultYear = $periodType === SerialPeriods::WEEK ? $today->isoWeekYear() : $today->year;
        $controlYear = (int) ($values['control_year'] ?? $defaultYear);
        $controlWeek = filled($values['control_week'] ?? null) ? (int) $values['control_week'] : null;
        $ratingPeriods = $lines->filter(fn ($line) => $line['requires_folios'])
            ->pluck('rating_part_number')->filter()->map(fn ($rating) => strtoupper(trim($rating)))
            ->unique()->map(function ($rating) use ($lines, $values, $market, $periodType, $controlYear, $controlWeek, $today) {
                $key = LabelRoomAdministrationService::ratingPeriodKey($rating);
                $year = (int) ($values['rating_periods'][$key]['year'] ?? $controlYear);
                $number = (int) ($values['rating_periods'][$key]['number'] ?? ($periodType === SerialPeriods::WEEK ? $controlWeek : $today->month));

                return [
                    'rating' => $rating, 'key' => $key, 'year' => $year, 'number' => $number,
                    'serial_parts' => $lines->filter(fn ($line) => $line['label_type'] === 'serial'
                        && strtoupper(trim((string) $line['rating_part_number'])) === $rating)
                        ->pluck('part_number')->unique()->values()->all(),
                    'control' => $market && $number >= 1 && $number <= SerialPeriods::maximum($periodType)
                        ? SerialPeriod::query()->where('label_part_number', $rating)
                            ->where('serial_standard', $market)->where('period_type', $periodType)
                            ->where('year', $year)->where('period_number', $number)->first()
                        : null,
                ];
            })->values();

        return [
            'labelRequest' => $labelRequest->load(['line', 'shift', 'releasedBy']),
            'lines' => $lines, 'operators' => $this->service->operators(),
            'defaultYear' => $defaultYear, 'defaultWeek' => $today->isoWeek(), 'defaultMonth' => $today->month,
            'markets' => SerialStandards::all(), 'selectedMarket' => $market,
            'periodType' => $periodType, 'ratingPeriods' => $ratingPeriods,
        ];
    }

    private function reviewInput(Request $request): array
    {
        return $request->validate([
            'serial_standard' => ['required', Rule::in(SerialStandards::all())],
            'control_year' => ['required', 'integer', 'between:2000,2100'],
            'control_week' => ['required', 'integer', 'between:1,53'],
            'rating_periods' => ['nullable', 'array'],
            'rating_periods.*.year' => ['required', 'integer', 'between:2000,2100'],
            'rating_periods.*.number' => ['required', 'integer', 'between:1,53'],
            'job_status' => ['required', Rule::in(array_keys(LabelRequest::JOB_STATUSES))],
            'review_notes' => ['nullable', 'string', 'max:2000'],
            'plan_checked' => ['accepted'],
            'proposal_signature' => ['nullable', 'string', 'size:64'],
            'tasks' => ['required', 'array', 'max:5000'],
            'tasks.*.assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'tasks.*.expected_start' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
        ]);
    }

    public function preview(Request $request, LabelRequest $label_request)
    {
        $previewKey = 'label_review_proposals.'.$label_request->id;
        $request->session()->forget($previewKey);

        try {
            $data = $this->reviewInput($request);
            $proposal = $this->service->proposal($label_request, $data, $request->user());
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('label_requests.review', $label_request));
        }

        return redirect()->route('label_requests.review', $label_request, 303)->with($previewKey, [
            'input' => $data,
            'proposal' => $proposal,
            'proposalSignature' => $this->service->proposalSignature($label_request, $proposal, $data),
        ]);
    }

    public function release(Request $request, LabelRequest $label_request)
    {
        $request->session()->forget('label_review_proposals.'.$label_request->id);

        try {
            $this->service->release($label_request, $this->reviewInput($request), $request->user());
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('label_requests.review', $label_request));
        }

        return redirect()->route('label_requests.show', $label_request)->with('success', 'Requisición liberada. Los folios quedaron reservados y las tareas están disponibles.');
    }

    public function assign(Request $request, LabelRequest $label_request, LabelWorkTask $task)
    {
        $data = $request->validate(['assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id']]);
        $this->service->assign($label_request, $task, filled($data['assigned_to_user_id'] ?? null) ? (int) $data['assigned_to_user_id'] : null, $request->user());

        return back()->with('success', 'Asignación actualizada.');
    }

    public function complete(Request $request, LabelRequest $label_request, LabelWorkTask $task)
    {
        $data = $request->validate([
            'printed_shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'work_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'work_confirmed' => ['accepted'],
        ]);
        $result = $this->service->completeTask($label_request, $task, $data, $request->user());

        return back()->with('success', $result->status === LabelRequest::STATUS_READY_FOR_DELIVERY
            ? 'Todas las etiquetas están terminadas. Se registró el concentrado JOB y la requisición está lista para entregar.'
            : 'Impresión registrada. Quedan tareas pendientes.');
    }

    public function classify(Request $request, LabelRequest $label_request)
    {
        $data = $request->validate([
            'job_status' => ['required', Rule::in(array_keys(LabelRequest::JOB_STATUSES))],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->service->classify($label_request, $data['job_status'], $data['reason'], $request->user());

        return back()->with('success', 'Clasificación administrativa actualizada.');
    }

    public function weeks(Request $request)
    {
        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'serial_standard' => ['nullable', Rule::in(SerialStandards::all())],
            'period_number' => ['nullable', 'integer', 'between:1,53'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $today = now(config('app.display_timezone'));
        $year = $filters['year'] ?? $today->year;
        $market = $filters['serial_standard'] ?? null;
        $query = SerialPeriod::query()->where('year', $year)
            ->when($market, fn ($q, $value) => $q->where('serial_standard', $value))
            ->when($filters['period_number'] ?? null, fn ($q, $number) => $q->where('period_number', $number))
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->where('label_part_number', 'like', "%{$term}%"));
        $ranges = SerialRange::with(['period', 'labelRequest.line', 'labelRequest.shift', 'tasks.assignee', 'tasks.printedShift'])
            ->whereIn('serial_week_id', (clone $query)->select('id'))->orderByDesc('id')->paginate(30, ['*'], 'ranges_page')->withQueryString();

        return view('label_requests.weeks', [
            'periods' => $query->orderByDesc('period_number')->orderBy('label_part_number')->paginate(20, ['*'], 'periods_page')->withQueryString(),
            'ranges' => $ranges, 'filters' => $filters, 'year' => $year,
            'markets' => SerialStandards::all(),
            'ratingOptions' => RatingAssemblyMapping::query()->active()
                ->whereNotNull('rating_part_number')->where('rating_part_number', '!=', '')
                ->select('rating_part_number', 'market')->distinct()
                ->orderBy('rating_part_number')->orderBy('market')->get()
                ->groupBy('rating_part_number')
                ->map(fn ($mappings) => [
                    'part' => $mappings->first()->rating_part_number,
                    'markets' => $mappings->pluck('market')->unique()->values()->all(),
                ])->values(),
        ]);
    }

    public function initializeWeek(Request $request, LabelFolioService $service)
    {
        $data = $request->validate([
            'label_part_number' => ['required', 'string', 'max:80'],
            'serial_standard' => ['required', Rule::in(SerialStandards::all())],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'period_number' => ['required', 'integer', 'between:1,53'],
            'control_week' => ['nullable', 'integer', 'between:1,53'],
            'last_serial_number' => ['required', 'integer', 'min:0', 'max:4294967294'],
            'opening_notes' => ['required', 'string', 'max:2000'],
            'history_checked' => ['accepted'],
        ]);
        $maximum = SerialPeriods::maximum(SerialPeriods::forMarket($data['serial_standard']));
        if ((int) $data['period_number'] > $maximum) {
            throw ValidationException::withMessages([
                'period_number' => "El periodo máximo para {$data['serial_standard']} es {$maximum}.",
            ]);
        }
        $service->initialize($data, $request->user());

        return back()->with('success', 'Control de periodo actualizado con el último folio verificado.');
    }

    public function jobs(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'], 'line_id' => ['nullable', 'integer', 'exists:production_lines,id'],
            'job_status' => ['nullable', Rule::in(array_keys(LabelRequest::JOB_STATUSES))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $entries = LabelJobEntry::with(['labelRequest.workTasks', 'line', 'shift'])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '<=', $date))
            ->when($filters['line_id'] ?? null, fn ($q, $id) => $q->where('line_id', $id))
            ->when($filters['shift_id'] ?? null, fn ($q, $id) => $q->where('shift_id', $id))
            ->when($filters['job_status'] ?? null, fn ($q, $status) => $q->whereHas('labelRequest', fn ($q) => $q->where('job_status', $status)))
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('job_number', 'like', "%{$term}%")->orWhere('po_number', 'like', "%{$term}%")->orWhere('destination', 'like', "%{$term}%")->orWhere('model', 'like', "%{$term}%")))
            ->orderByDesc('work_date')->orderByDesc('id')->paginate(40)->withQueryString();

        $entrySummaries = $entries->getCollection()
            ->mapWithKeys(fn (LabelJobEntry $entry) => [$entry->id => $this->summarizeJobEntry($entry)])
            ->all();

        return view('label_requests.jobs', [
            'entries' => $entries, 'filters' => $filters, 'shifts' => Shift::orderBy('code')->get(),
            'lines' => ProductionLine::orderBy('code')->get(),
            'entrySummaries' => $entrySummaries,
            'jobStatusOptions' => LabelRequest::JOB_STATUSES,
        ]);
    }

    private function summarizeJobEntry(LabelJobEntry $entry): array
    {
        $tasks = $entry->labelRequest->workTasks->filter(static function (LabelWorkTask $task) use ($entry): bool {
            if ($task->status !== 'completed') {
                return false;
            }

            foreach ($task->jobs ?? [] as $job) {
                if ((string) ($job['job_number'] ?? '') === $entry->job_number
                    && (string) ($job['model'] ?? '') === $entry->model) {
                    return true;
                }
            }

            return false;
        });

        $ranges = $tasks
            ->filter(fn (LabelWorkTask $task) => $task->folio_start !== null && $task->folio_end !== null)
            ->unique(fn (LabelWorkTask $task) => $task->serial_range_id
                ?? implode('|', [$task->rating_part_number, $task->folio_start, $task->folio_end]))
            ->map(fn (LabelWorkTask $task) => [
                'start' => $task->folio_start,
                'end' => $task->folio_end,
                'rating_part_number' => $task->rating_part_number,
            ])
            ->values()
            ->all();

        $types = collect(self::JOB_LABEL_TYPE_CODES)
            ->filter(fn (string $code, string $type) => $tasks->contains('label_type', $type))
            ->values()
            ->all();

        return ['ranges' => $ranges, 'types' => $types];
    }
}
