<?php

namespace App\Services\Labels;

use App\Models\LabelJobEntry;
use App\Models\LabelRequest;
use App\Models\LabelWorkTask;
use App\Models\SerialRange;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelRoomAdministrationService
{
    public function __construct(
        private readonly LabelWorkDefinitionService $definitions,
        private readonly LabelFolioService $folios,
    ) {}

    public function operators(): Collection
    {
        return User::with('roles')->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'label_room'))
            ->orderBy('name')->get()->filter(fn (User $user) => $user->hasModuleAccess('labels'));
    }

    private function assertOperator(int $id): User
    {
        $user = User::with('roles')->find($id);
        if (! $user || ! $user->is_active || ! $user->hasRole('label_room') || ! $user->hasModuleAccess('labels')) {
            throw ValidationException::withMessages(['operator' => 'Selecciona una operadora activa con acceso a etiquetas.']);
        }

        return $user;
    }

    /** Build a proposal without consuming any folios. All task identities and quantities come from the request. */
    public function proposal(LabelRequest $request, array $data, bool $lockSources = false): array
    {
        if ($request->released_at || ! in_array($request->status, [LabelRequest::STATUS_REQUESTED, LabelRequest::STATUS_IN_PROGRESS], true)) {
            throw ValidationException::withMessages(['status' => 'Esta requisición ya fue liberada o está cerrada.']);
        }
        if ($request->isOriginalReprint() && empty($data['originals_received'])) {
            throw ValidationException::withMessages(['originals_received' => 'Confirma la recepción de las etiquetas originales físicas.']);
        }

        $lines = $this->definitions->forRequest($request);
        if ($lines->isEmpty() || $lines->keys()->sort()->values()->all() !== collect(array_keys($data['tasks'] ?? []))->sort()->values()->all()) {
            throw ValidationException::withMessages(['tasks' => 'El detalle cambió. Abre nuevamente la revisión de la requisición.']);
        }
        $bundles = [];
        $tasks = [];
        $nextByWeek = [];

        foreach ($lines as $key => $line) {
            $input = $data['tasks'][$key];
            $assigned = filled($input['assigned_to_user_id'] ?? null) ? (int) $input['assigned_to_user_id'] : null;
            if ($assigned) {
                $this->assertOperator($assigned);
            }
            $task = array_merge($line, [
                'assigned_to_user_id' => $assigned, 'evidence_quantity' => $request->isOriginalReprint() ? 0 : 1,
                'bundle_key' => null, 'folio_start' => null, 'folio_end' => null, 'evidence_folio' => null,
            ]);

            if ($line['requires_folios']) {
                $family = trim((string) $line['folio_family']);
                if ($family === '') {
                    throw ValidationException::withMessages(['tasks' => "No se encontró un SKU activo en Master Model Mapping para el ensamble {$line['assembly_number']} de la Job {$line['job_number']}. Revisa Job → ensamble → SKU antes de liberar."]);
                }
                $rating = $line['label_type'] === 'rating' ? $line['part_number'] : strtoupper(trim((string) ($input['rating_part_number'] ?? '')));
                if ($rating === '') {
                    throw ValidationException::withMessages(['tasks' => 'Cada tarea Serial/Rating necesita NP Rating de control.']);
                }
                if ($line['label_type'] === 'serial') {
                    $matchingRatings = $lines->where('label_type', 'rating')->where('job_number', $line['job_number'])->where('model', $line['model']);
                    if ($matchingRatings->isNotEmpty()) {
                        $matchingRating = $matchingRatings->firstWhere('part_number', $rating);
                        if (! $matchingRating || trim((string) $matchingRating['folio_family']) !== $family) {
                            throw ValidationException::withMessages(['tasks' => 'Serial debe usar el NP Rating y la familia de su contraparte en esta requisición.']);
                        }
                    }
                }
                $bundleKey = json_encode([$line['job_number'], $line['model'], $rating, $family]);
                $task['bundle_key'] = $bundleKey;
                $task['folio_family'] = $family;
                $task['rating_part_number'] = $rating;

                if ($request->isOriginalReprint()) {
                    $range = SerialRange::with(['week', 'labelRequest'])->find($input['source_range_id'] ?? null);
                    $start = (int) ($input['folio_start'] ?? 0);
                    $end = (int) ($input['folio_end'] ?? 0);
                    if (! $range && filled($input['source_range_id'] ?? null)) {
                        throw ValidationException::withMessages(['tasks' => 'El rango original ya no está disponible.']);
                    }
                    if ($range && $lockSources) {
                        $originalRequest = LabelRequest::whereKey($range->label_request_id)->lockForUpdate()->firstOrFail();
                        $range->refresh()->setRelation('labelRequest', $originalRequest);
                        $range->load('week');
                    }
                    if ($range && ($range->label_request_id === $request->id || $range->status === 'cancelled'
                        || $range->labelRequest->status === LabelRequest::STATUS_CANCELLED
                        || $range->week->label_part_number !== $rating || $range->week->folio_family !== $family
                        || $start < $range->range_start || $end > $range->range_end || $end - $start + 1 !== $line['quantity']
                        || ($range->evidence_folio !== null && $range->evidence_folio >= $start && $range->evidence_folio <= $end))) {
                        throw ValidationException::withMessages(['tasks' => 'El rango original debe pertenecer a la familia y NP Rating, contener la cantidad solicitada y excluir la evidencia.']);
                    }
                    $matchingOriginal = ! $range || $this->definitions->forRequest($range->labelRequest)->contains(fn ($original) => $original['label_type'] === $line['label_type'] && $original['part_number'] === $line['part_number']
                        && $original['job_number'] === $line['job_number'] && $original['model'] === $line['model']);
                    if (! $matchingOriginal || ($range?->job_number && $range->job_number !== $line['job_number'])
                        || ($range?->model && $range->model !== $line['model'])) {
                        throw ValidationException::withMessages(['tasks' => 'La requisición original no contiene ese tipo de etiqueta, NP, Job y modelo.']);
                    }
                    $reference = trim((string) ($input['original_reference'] ?? ''));
                    if (! $range && ($reference === '' || $start < 1 || $end - $start + 1 !== $line['quantity'])) {
                        throw ValidationException::withMessages(['tasks' => 'Para originales sin registro digital, indica la referencia física y un rango que corresponda exactamente a la cantidad solicitada.']);
                    }
                    $task['original_reference'] = $range ? 'Requisición #'.$range->label_request_id : $reference;
                    $bundle = ['source_range_id' => $range?->id, 'range_start' => $start, 'range_end' => $end,
                        'production_quantity' => $line['quantity'], 'evidence_quantity' => 0, 'evidence_folio' => null,
                        'year' => $range ? $range->week->year : (int) ($input['original_year'] ?? $data['control_year']),
                        'week' => $range ? $range->week->week : (int) ($input['original_week'] ?? $data['control_week']),
                        'family' => $family, 'rating' => $rating,
                        'job_number' => $line['job_number'], 'model' => $line['model']];
                    if (isset($bundles[$bundleKey]) && $bundles[$bundleKey] !== $bundle) {
                        throw ValidationException::withMessages(['tasks' => 'Serial y Rating del mismo producto deben reutilizar exactamente el mismo rango.']);
                    }
                    $bundles[$bundleKey] = $bundle;
                } elseif (! isset($bundles[$bundleKey])) {
                    $year = (int) $data['control_year'];
                    $week = (int) $data['control_week'];
                    $weekKey = json_encode([$rating, $family, $year, $week]);
                    $start = $nextByWeek[$weekKey] ?? $this->folios->nextNumber($rating, $family, $year, $week);
                    $end = $start + $line['quantity'];
                    if ($end > LabelFolioService::MAX_FOLIO) {
                        throw ValidationException::withMessages(['tasks' => 'El rango excede el límite del consecutivo.']);
                    }
                    $evidence = ($input['evidence_position'] ?? 'last') === 'first' ? $start : $end;
                    $bundles[$bundleKey] = ['source_range_id' => null, 'range_start' => $start, 'range_end' => $end,
                        'production_quantity' => $line['quantity'], 'evidence_quantity' => 1, 'evidence_folio' => $evidence,
                        'year' => $year, 'week' => $week, 'family' => $family, 'rating' => $rating,
                        'job_number' => $line['job_number'], 'model' => $line['model']];
                    $nextByWeek[$weekKey] = $end + 1;
                } else {
                    $bundle = $bundles[$bundleKey];
                    $evidence = ($input['evidence_position'] ?? 'last') === 'first' ? $bundle['range_start'] : $bundle['range_end'];
                    if ($bundle['production_quantity'] !== $line['quantity'] || $bundle['evidence_folio'] !== $evidence) {
                        throw ValidationException::withMessages(['tasks' => 'Serial y Rating del mismo producto deben tener la misma cantidad y posición de evidencia.']);
                    }
                }
                $bundle = $bundles[$bundleKey];
                $task['folio_start'] = $bundle['range_start'];
                $task['folio_end'] = $bundle['range_end'];
                $task['evidence_folio'] = $bundle['evidence_folio'];
                $task['control_year'] = $bundle['year'];
                $task['control_week'] = $bundle['week'];
            }
            $tasks[$key] = $task;
        }

        return ['tasks' => $tasks, 'bundles' => $bundles];
    }

    public function proposalSignature(LabelRequest $request, array $proposal, array $data): string
    {
        return hash_hmac('sha256', json_encode([$request->id, $proposal,
            (int) $data['control_year'], (int) $data['control_week'], $data['job_status'],
            $data['review_notes'] ?? null]), (string) config('app.key'));
    }

    public function release(LabelRequest $request, array $data, User $user): LabelRequest
    {
        return DB::transaction(function () use ($request, $data, $user) {
            $locked = LabelRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($locked->released_at && $locked->status !== LabelRequest::STATUS_CANCELLED) {
                return $locked; // Retrying the same release cannot allocate a second range.
            }
            if (! empty($data['plan_checked'])) {
                $proposal = $this->proposal($locked, $data, true);
            } else {
                throw ValidationException::withMessages(['plan_checked' => 'Confirma la revisión contra la información disponible de producción.']);
            }
            if (! hash_equals($this->proposalSignature($locked, $proposal, $data), (string) ($data['proposal_signature'] ?? ''))) {
                throw ValidationException::withMessages(['tasks' => 'Recalcula la propuesta: los datos o los folios cambiaron desde la revisión.']);
            }
            if (SerialRange::where('label_request_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['tasks' => 'Esta requisición tiene rangos históricos. Revisa su historial antes de crear otra reserva.']);
            }

            $rangeIds = [];
            $bundles = collect($proposal['bundles'])->sortBy(fn ($b) => json_encode([$b['rating'], $b['family'], $b['year'], $b['week'], str_pad((string) $b['range_start'], 10, '0', STR_PAD_LEFT)]));
            foreach ($bundles as $key => $bundle) {
                if ($locked->isOriginalReprint()) {
                    $rangeIds[$key] = $bundle['source_range_id'];

                    continue;
                }
                $week = $this->folios->lockWeek($bundle['rating'], $bundle['family'], $bundle['year'], $bundle['week']);
                $next = $this->folios->highWater($week) + 1;
                $relatedTasks = collect($proposal['tasks'])->where('bundle_key', $key);
                foreach ($relatedTasks as $taskKey => $task) {
                    if ((int) ($data['tasks'][$taskKey]['expected_start'] ?? 0) !== $next) {
                        throw ValidationException::withMessages(['tasks' => 'El consecutivo cambió o falta la propuesta. Recalcula los folios antes de liberar.']);
                    }
                }
                if ($next !== $bundle['range_start']) {
                    throw ValidationException::withMessages(['tasks' => 'Otra requisición reservó folios. Recalcula la propuesta.']);
                }
                $range = SerialRange::create([
                    'serial_week_id' => $week->id, 'label_request_id' => $locked->id,
                    'range_start' => $next, 'range_end' => $bundle['range_end'],
                    'quantity' => $bundle['production_quantity'] + 1, 'production_quantity' => $bundle['production_quantity'],
                    'evidence_quantity' => 1, 'evidence_folio' => $bundle['evidence_folio'],
                    'job_number' => $bundle['job_number'], 'model' => $bundle['model'],
                    'created_by_user_id' => $user->id, 'status' => 'reserved',
                ]);
                $rangeIds[$key] = $range->id;
                $week->update(['last_serial_number' => $range->range_end]);
            }

            foreach ($proposal['tasks'] as $task) {
                $locked->workTasks()->create([
                    ...collect($task)->only(['source_key', 'label_type', 'part_number', 'model', 'job_number', 'jobs',
                        'po_number', 'destination', 'quantity', 'evidence_quantity', 'assigned_to_user_id',
                        'folio_start', 'folio_end', 'evidence_folio', 'rating_part_number', 'folio_family',
                        'control_year', 'control_week', 'original_reference'])->all(),
                    'serial_range_id' => $task['bundle_key'] ? $rangeIds[$task['bundle_key']] : null,
                ]);
            }
            $onlyBundle = count($proposal['bundles']) === 1 ? reset($proposal['bundles']) : null;
            $locked->update([
                'job_status' => $data['job_status'], 'review_notes' => $data['review_notes'] ?? null,
                'control_year' => $locked->isOriginalReprint() ? ($onlyBundle['year'] ?? null) : $data['control_year'],
                'control_week' => $locked->isOriginalReprint() ? ($onlyBundle['week'] ?? null) : $data['control_week'],
                'folio_start' => $onlyBundle['range_start'] ?? null, 'folio_end' => $onlyBundle['range_end'] ?? null,
                'released_at' => now(), 'released_by_user_id' => $user->id,
                'originals_received_at' => $locked->isOriginalReprint() ? now() : null,
                'status' => LabelRequest::STATUS_IN_PROGRESS,
            ]);
            $this->event($locked, $user, 'released', ['folio_mode' => $locked->folio_mode, 'job_status' => $locked->job_status,
                'notes' => $locked->review_notes, 'ranges' => $rangeIds]);

            return $locked;
        }, 3);
    }

    public function assign(LabelRequest $request, LabelWorkTask $task, ?int $operatorId, User $actor): void
    {
        DB::transaction(function () use ($request, $task, $operatorId, $actor) {
            $locked = LabelRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $task = $locked->workTasks()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== LabelRequest::STATUS_IN_PROGRESS || $task->status !== 'pending') {
                throw ValidationException::withMessages(['task' => 'Solo puedes asignar tareas pendientes de una requisición en preparación.']);
            }
            if ($operatorId) {
                $this->assertOperator($operatorId);
            }
            $before = $task->assigned_to_user_id;
            $task->update(['assigned_to_user_id' => $operatorId]);
            $this->event($locked, $actor, 'assigned', ['task_id' => $task->id, 'before' => $before, 'after' => $operatorId]);
        });
    }

    public function completeTask(LabelRequest $request, LabelWorkTask $task, array $data, User $actor): LabelRequest
    {
        return DB::transaction(function () use ($request, $task, $data, $actor) {
            $locked = LabelRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $task = $locked->workTasks()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            if ($task->status === 'completed' && $locked->status !== LabelRequest::STATUS_CANCELLED) {
                return $locked;
            }
            if (! $locked->released_at || $locked->status !== LabelRequest::STATUS_IN_PROGRESS) {
                throw ValidationException::withMessages(['status' => 'La requisición debe estar liberada y en preparación.']);
            }
            $operator = $this->assertOperator((int) $data['printed_by_user_id']);
            if (! Shift::whereKey($data['printed_shift_id'])->where('active', true)->exists()) {
                throw ValidationException::withMessages(['printed_shift_id' => 'Selecciona un turno activo.']);
            }
            $lastWorkDate = $locked->workTasks()->where('status', 'completed')->max('work_date');
            if ($data['work_date'] < $locked->request_date->toDateString() || ($lastWorkDate && $data['work_date'] < substr($lastWorkDate, 0, 10))) {
                throw ValidationException::withMessages(['work_date' => 'La fecha de trabajo no puede ser anterior a la requisición ni al trabajo ya confirmado.']);
            }
            $lastTask = $locked->workTasks()->where('status', 'pending')->count() === 1;
            if ($lastTask && $locked->isOriginalReprint() && ! $locked->physical_signed_at && empty($data['physical_signed'])) {
                throw ValidationException::withMessages(['physical_signed' => 'Confirma la firma de la requisición física antes de completar la reimpresión.']);
            }
            if ($locked->isOriginalReprint() && ! empty($data['physical_signed']) && ! $locked->physical_signed_at) {
                $locked->update(['physical_signed_at' => now(), 'physical_signed_by_user_id' => $actor->id]);
            }
            $task->update([
                'status' => 'completed', 'printed_by_user_id' => $operator->id, 'printed_by_name' => $operator->name,
                'printed_shift_id' => $data['printed_shift_id'], 'work_date' => $data['work_date'], 'completed_at' => now(),
            ]);
            $this->event($locked, $actor, 'task_completed', ['task_id' => $task->id, 'operator' => $operator->id,
                'shift_id' => $data['printed_shift_id'], 'work_date' => $data['work_date'],
                'physical_signed' => (bool) $locked->physical_signed_at]);

            if ($lastTask) {
                $this->closeWork($locked, $data, $actor);
            }

            return $locked->refresh();
        }, 3);
    }

    private function closeWork(LabelRequest $request, array $data, User $actor): void
    {
        $rows = [];
        foreach ($request->workTasks()->get() as $task) {
            foreach ($task->jobs as $job) {
                $key = json_encode([$job['job_number'], $job['model'] ?? '']);
                $row = $rows[$key] ?? [
                    'job_number' => $job['job_number'], 'model' => $job['model'] ?? '',
                    'po_number' => $task->po_number ?: $request->po_number,
                    'quantity' => null, 'shipping_quantity' => null, 'shared_shipping' => [],
                ];
                if ($task->label_type !== 'shipping') {
                    $row['quantity'] = max($row['quantity'] ?? 0, $task->quantity);
                } elseif (count($task->jobs) === 1) {
                    $row['shipping_quantity'] = ($row['shipping_quantity'] ?? 0) + $task->quantity;
                    $row['po_number'] = $task->po_number ?: $row['po_number'];
                } else {
                    $row['shared_shipping'][] = ['task_id' => $task->id, 'part_number' => $task->part_number,
                        'quantity' => $task->quantity, 'po_number' => $task->po_number, 'jobs' => array_column($task->jobs, 'job_number')];
                }
                $rows[$key] = $row;
            }
        }
        foreach ($rows as $row) {
            LabelJobEntry::firstOrCreate([
                'label_request_id' => $request->id, 'job_number' => $row['job_number'], 'model' => $row['model'],
            ], [...$row, 'work_date' => $data['work_date'], 'shift_id' => $data['printed_shift_id'],
                'line_id' => $request->line_id, 'closed_by_user_id' => $actor->id]);
        }
        $request->update(['status' => LabelRequest::STATUS_READY_FOR_DELIVERY, 'attended_at' => now(), 'attended_by_user_id' => $actor->id]);
        SerialRange::where('label_request_id', $request->id)->update(['status' => 'completed']);
        $this->event($request, $actor, 'work_closed', ['shift_id' => $data['printed_shift_id'], 'work_date' => $data['work_date']]);
    }

    public function classify(LabelRequest $request, string $status, string $reason, User $actor): void
    {
        DB::transaction(function () use ($request, $status, $reason, $actor) {
            $locked = LabelRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $before = $locked->job_status;
            $locked->update(['job_status' => $status]);
            $this->event($locked, $actor, 'classified', ['before' => $before, 'after' => $status, 'reason' => $reason]);
        });
    }

    public function event(LabelRequest $request, User $user, string $action, array $details): void
    {
        DB::table('label_administration_events')->insert([
            'label_request_id' => $request->id, 'user_id' => $user->id, 'action' => $action,
            'details' => json_encode($details), 'created_at' => now(),
        ]);
    }
}
