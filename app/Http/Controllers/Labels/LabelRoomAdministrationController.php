<?php

namespace App\Http\Controllers\Labels;

use App\Http\Controllers\Controller;
use App\Models\LabelJobEntry;
use App\Models\LabelRequest;
use App\Models\LabelWorkTask;
use App\Models\ProductionLine;
use App\Models\SerialRange;
use App\Models\SerialWeek;
use App\Models\Shift;
use App\Services\Labels\LabelFolioService;
use App\Services\Labels\LabelRoomAdministrationService;
use App\Services\Labels\LabelWorkDefinitionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LabelRoomAdministrationController extends Controller
{
    public function __construct(
        private readonly LabelRoomAdministrationService $service,
        private readonly LabelWorkDefinitionService $definitions,
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
        $jobNumbers = $lines->flatMap(fn ($line) => array_column($line['jobs'], 'job_number'))->unique();
        $values = $input ?: session()->getOldInput();
        $controlYear = (int) ($values['control_year'] ?? $labelRequest->request_date->isoWeekYear());
        $controlWeek = (int) ($values['control_week'] ?? $labelRequest->week);
        $ratingParts = $lines->pluck('rating_part_number')
            ->merge(collect($values['tasks'] ?? [])->pluck('rating_part_number'))->filter()->unique();

        return [
            'labelRequest' => $labelRequest->load(['line', 'shift', 'releasedBy']),
            'lines' => $lines, 'operators' => $this->service->operators(),
            'defaultYear' => $labelRequest->request_date->isoWeekYear(),
            'availableControls' => SerialWeek::whereIn('label_part_number', $ratingParts)
                ->where('year', $controlYear)->where('week', $controlWeek)->whereNotNull('folio_family')
                ->orderBy('label_part_number')->orderBy('folio_family')->get(),
            'controlsYear' => $controlYear, 'controlsWeek' => $controlWeek,
            'sources' => SerialRange::with(['week', 'labelRequest'])
                ->where('status', '!=', 'cancelled')->where('label_request_id', '!=', $labelRequest->id)
                ->where(fn ($q) => $q->whereIn('job_number', $jobNumbers)
                    ->orWhereHas('labelRequest', fn ($q) => $q->whereIn('job_number', $jobNumbers)))
                ->orderByDesc('id')->limit(200)->get(),
        ];
    }

    private function reviewInput(Request $request): array
    {
        return $request->validate([
            'control_year' => ['required', 'integer', 'between:2000,2100'],
            'control_week' => ['required', 'integer', 'between:1,53'],
            'job_status' => ['required', Rule::in(array_keys(LabelRequest::JOB_STATUSES))],
            'review_notes' => ['nullable', 'string', 'max:2000'],
            'plan_checked' => ['accepted'],
            'originals_received' => ['nullable', 'boolean'],
            'proposal_signature' => ['nullable', 'string', 'size:64'],
            'tasks' => ['required', 'array', 'max:5000'],
            'tasks.*.assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'tasks.*.rating_part_number' => ['nullable', 'string', 'max:80'],
            'tasks.*.evidence_position' => ['nullable', Rule::in(['first', 'last'])],
            'tasks.*.original_reference' => ['nullable', 'string', 'max:255'],
            'tasks.*.original_year' => ['nullable', 'integer', 'between:2000,2100'],
            'tasks.*.original_week' => ['nullable', 'integer', 'between:1,53'],
            'tasks.*.source_range_id' => ['nullable', 'integer', 'exists:serial_ranges,id'],
            'tasks.*.folio_start' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
            'tasks.*.folio_end' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
            'tasks.*.expected_start' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
        ]);
    }

    public function preview(Request $request, LabelRequest $label_request)
    {
        $previewKey = 'label_review_proposals.'.$label_request->id;
        $request->session()->forget($previewKey);

        try {
            $data = $this->reviewInput($request);
            $proposal = $this->service->proposal($label_request, $data);
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
            'printed_by_user_id' => ['required', 'integer', 'exists:users,id'],
            'printed_shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'work_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'physical_signed' => ['nullable', 'boolean'],
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
            'week' => ['nullable', 'integer', 'between:1,53'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $year = $filters['year'] ?? now()->isoWeekYear();
        $query = SerialWeek::query()->where('year', $year)
            ->when($filters['week'] ?? null, fn ($q, $week) => $q->where('week', $week))
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('label_part_number', 'like', "%{$term}%")->orWhere('folio_family', 'like', "%{$term}%")));
        $ranges = SerialRange::with(['week', 'labelRequest.line', 'labelRequest.shift', 'tasks.assignee', 'tasks.printedShift'])
            ->whereIn('serial_week_id', (clone $query)->select('id'))->orderByDesc('id')->paginate(30, ['*'], 'ranges_page')->withQueryString();
        $reprints = LabelWorkTask::with(['range.week', 'labelRequest', 'printedShift'])
            ->whereHas('labelRequest', fn ($q) => $q->where('folio_mode', 'reprint_originals'))
            ->whereNotNull('folio_start')->where('control_year', $year)
            ->when($filters['week'] ?? null, fn ($q, $week) => $q->where('control_week', $week))
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('rating_part_number', 'like', "%{$term}%")->orWhere('folio_family', 'like', "%{$term}%")))
            ->orderByDesc('id')->paginate(20, ['*'], 'reprints_page')->withQueryString();

        return view('label_requests.weeks', [
            'weeks' => $query->orderByDesc('week')->orderBy('label_part_number')->paginate(20, ['*'], 'weeks_page')->withQueryString(),
            'ranges' => $ranges, 'reprints' => $reprints, 'filters' => $filters, 'year' => $year,
        ]);
    }

    public function initializeWeek(Request $request, LabelFolioService $service)
    {
        $data = $request->validate([
            'label_part_number' => ['required', 'string', 'max:80'],
            'folio_family' => ['required', 'string', 'max:80'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'week' => ['required', 'integer', 'between:1,53'],
            'last_serial_number' => ['required', 'integer', 'min:0', 'max:4294967294'],
            'opening_notes' => ['required', 'string', 'max:2000'],
            'history_checked' => ['accepted'],
        ]);
        $service->initialize($data, $request->user());

        return back()->with('success', 'Control semanal actualizado con el último folio verificado.');
    }

    public function jobs(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'], 'line_id' => ['nullable', 'integer', 'exists:production_lines,id'],
            'job_status' => ['nullable', Rule::in(array_keys(LabelRequest::JOB_STATUSES))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $entries = LabelJobEntry::with(['labelRequest', 'line', 'shift'])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('work_date', '<=', $date))
            ->when($filters['line_id'] ?? null, fn ($q, $id) => $q->where('line_id', $id))
            ->when($filters['shift_id'] ?? null, fn ($q, $id) => $q->where('shift_id', $id))
            ->when($filters['job_status'] ?? null, fn ($q, $status) => $q->whereHas('labelRequest', fn ($q) => $q->where('job_status', $status)))
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('job_number', 'like', "%{$term}%")->orWhere('po_number', 'like', "%{$term}%")->orWhere('model', 'like', "%{$term}%")))
            ->orderByDesc('work_date')->orderByDesc('id')->paginate(40)->withQueryString();

        return view('label_requests.jobs', [
            'entries' => $entries, 'filters' => $filters, 'shifts' => Shift::orderBy('code')->get(),
            'lines' => ProductionLine::orderBy('code')->get(),
        ]);
    }
}
