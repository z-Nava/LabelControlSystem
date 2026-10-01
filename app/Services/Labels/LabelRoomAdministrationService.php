<?php

namespace App\Services\Labels;

use App\Models\LabelJobEntry;
use App\Models\LabelRequest;
use App\Models\LabelWorkTask;
use App\Models\SerialRange;
use App\Models\Shift;
use App\Models\User;
use App\Services\Catalogs\RatingAssemblyMappingService;
use App\Support\SerialPeriods;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelRoomAdministrationService
{
    public function __construct(
        private readonly LabelWorkDefinitionService $definitions,
        private readonly LabelFolioService $folios,
        private readonly RatingAssemblyMappingService $ratingMappings,
        private readonly LostLabelReworkService $lostLabelReworks,
    ) {}

    public function operators(): Collection
    {
        return User::with('roles')->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'label_room'))
            ->orderBy('name')->get()->filter(fn (User $user) => $user->hasModuleAccess('labels'));
    }

    public function canAssignTasks(User $actor): bool
    {
        return $actor->isLabelRoomLeader() && $actor->hasModuleAccess('labels');
    }

    public function canCompleteTask(User $actor, LabelWorkTask $task): bool
    {
        return $task->assigned_to_user_id !== null
            && $actor->is_active
            && $actor->hasRole('label_room')
            && $actor->hasModuleAccess('labels')
            && ($this->canAssignTasks($actor) || (int) $task->assigned_to_user_id === $actor->id);
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
    public function proposal(LabelRequest $request, array $data, User $actor, bool $lockSources = false): array
    {
        if (! $this->canAssignTasks($actor) && collect($data['tasks'] ?? [])->contains(fn ($task) => filled($task['assigned_to_user_id'] ?? null))) {
            throw new AuthorizationException('Solo la líder puede asignar tareas de impresión.');
        }

        if ($request->released_at || ! in_array($request->status, [LabelRequest::STATUS_REQUESTED, LabelRequest::STATUS_IN_PROGRESS], true)) {
            throw ValidationException::withMessages(['status' => 'Esta requisición ya fue liberada o está cerrada.']);
        }
        if (! in_array($request->folio_mode, array_keys(LabelRequest::FOLIO_MODES), true)) {
            throw ValidationException::withMessages(['folio_mode' => 'Este tipo de trabajo ya no se puede liberar desde la aplicación.']);
        }
        if ($request->isLostLabelRework()) {
            $source = LabelRequest::query()->whereKey($request->source_label_request_id)
                ->when($lockSources, fn ($query) => $query->lockForUpdate())->first();
            if (! $source) {
                throw ValidationException::withMessages(['source_label_request_id' => 'La requisición original no está disponible.']);
            }
            $this->lostLabelReworks->assertValid($request, $source);
        }

        $lines = $this->definitions->forRequest($request);
        if ($lines->isEmpty() || $lines->keys()->sort()->values()->all() !== collect(array_keys($data['tasks'] ?? []))->sort()->values()->all()) {
            throw ValidationException::withMessages(['tasks' => 'El detalle cambió. Abre nuevamente la revisión de la requisición.']);
        }

        $market = strtoupper(trim((string) $data['serial_standard']));
        $periodType = SerialPeriods::forMarket($market);
        $releasePeriodYear = (int) $data['control_year'];
        $releasePeriodNumber = $periodType === SerialPeriods::WEEK
            ? (int) $data['control_week']
            : (int) $data['serial_month'];
        $bundles = [];
        $tasks = [];
        $nextByPeriod = [];

        foreach ($lines as $key => $line) {
            $input = $data['tasks'][$key];
            $assigned = filled($input['assigned_to_user_id'] ?? null) ? (int) $input['assigned_to_user_id'] : null;
            if ($assigned) {
                $this->assertOperator($assigned);
            }

            $task = array_merge($line, [
                'assigned_to_user_id' => $assigned,
                'evidence_quantity' => 1,
                'bundle_key' => null,
                'folio_start' => null,
                'folio_end' => null,
                'evidence_folio' => null,
            ]);

            if ($line['requires_folios']) {
                $family = trim((string) $line['folio_family']) ?: null;
                $rating = $line['label_type'] === 'rating'
                    ? strtoupper(trim((string) $line['part_number']))
                    : strtoupper(trim((string) ($line['rating_part_number'] ?? '')));
                if ($rating === '') {
                    throw ValidationException::withMessages(['tasks' => 'No se pudo determinar el NP Rating para Serial. Verifica el Rating de la requisición o la relación Rating/Ensamble 018 del catálogo.']);
                }

                if ($line['catalog_rating'] && $rating !== $line['catalog_rating']) {
                    throw ValidationException::withMessages(['tasks' => 'El Rating de control debe coincidir con la relación seleccionada en Kiosk.']);
                }
                $mappedMarket = $line['catalog_market'] ?? $this->ratingMappings->resolveMarket($line['assembly_number'], [$rating]);
                if ($mappedMarket && $mappedMarket !== $market) {
                    throw ValidationException::withMessages([
                        'serial_standard' => "El catálogo relaciona el NP Rating {$rating} y ensamble {$line['assembly_number']} con el mercado {$mappedMarket}.",
                    ]);
                }

                if ($line['label_type'] === 'serial') {
                    $matchingRatings = $lines->where('label_type', 'rating')
                        ->where('job_number', $line['job_number'])
                        ->where('model', $line['model']);
                    if ($matchingRatings->isNotEmpty() && ! $matchingRatings->contains('part_number', $rating)) {
                        throw ValidationException::withMessages(['tasks' => 'Serial debe usar el NP Rating de su contraparte en esta requisición.']);
                    }
                }

                $bundleKey = json_encode([$line['job_number'], $line['model'], $rating, $market]);
                $task['bundle_key'] = $bundleKey;
                $task['folio_family'] = $family;
                $task['rating_part_number'] = $rating;

                if (! isset($bundles[$bundleKey])) {
                    $periodKey = json_encode([$rating, $market, $periodType, $releasePeriodYear, $releasePeriodNumber]);
                    $start = $nextByPeriod[$periodKey] ?? $this->folios->nextNumber(
                        $rating,
                        $market,
                        $periodType,
                        $releasePeriodYear,
                        $releasePeriodNumber,
                    );
                    $end = $start + $line['quantity'];
                    if ($end > LabelFolioService::MAX_FOLIO) {
                        throw ValidationException::withMessages(['tasks' => 'El rango excede el límite del consecutivo.']);
                    }
                    $evidence = $start;
                    $bundles[$bundleKey] = [
                        'range_start' => $start,
                        'range_end' => $end,
                        'production_quantity' => $line['quantity'],
                        'evidence_quantity' => 1,
                        'evidence_folio' => $evidence,
                        'period_type' => $periodType,
                        'period_year' => $releasePeriodYear,
                        'period_number' => $releasePeriodNumber,
                        'operational_year' => (int) $data['control_year'],
                        'operational_week' => (int) $data['control_week'],
                        'family' => $family,
                        'market' => $market,
                        'rating' => $rating,
                        'job_number' => $line['job_number'],
                        'model' => $line['model'],
                    ];
                    $nextByPeriod[$periodKey] = $end + 1;
                } else {
                    $bundle = $bundles[$bundleKey];
                    if ($bundle['production_quantity'] !== $line['quantity']) {
                        throw ValidationException::withMessages(['tasks' => 'Serial y Rating del mismo producto deben tener la misma cantidad.']);
                    }
                }

                $bundle = $bundles[$bundleKey];
                $task['folio_start'] = $bundle['range_start'];
                $task['folio_end'] = $bundle['range_end'];
                $task['evidence_folio'] = $bundle['evidence_folio'];
                $task['control_year'] = $bundle['operational_year'];
                $task['control_week'] = $bundle['operational_week'];
                $task['serial_period_type'] = $bundle['period_type'];
                $task['serial_period_year'] = $bundle['period_year'];
                $task['serial_period_number'] = $bundle['period_number'];
            }
            $tasks[$key] = $task;
        }

        return ['tasks' => $tasks, 'bundles' => $bundles];
    }

    public function proposalSignature(LabelRequest $request, array $proposal, array $data): string
    {
        return hash_hmac('sha256', json_encode([
            $request->id,
            $proposal,
            $data['serial_standard'],
            (int) $data['control_year'],
            (int) $data['control_week'],
            $data['serial_month'] ?? null,
            $data['job_status'],
            $data['review_notes'] ?? null,
        ]), (string) config('app.key'));
    }

    public function release(LabelRequest $request, array $data, User $user): LabelRequest
    {
        return DB::transaction(function () use ($request, $data, $user): LabelRequest {
            $locked = LabelRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($locked->released_at && $locked->status !== LabelRequest::STATUS_CANCELLED) {
                return $locked;
            }
            if (empty($data['plan_checked'])) {
                throw ValidationException::withMessages(['plan_checked' => 'Confirma la revisión contra la información disponible de producción.']);
            }

            $proposal = $this->proposal($locked, $data, $user, true);
            if (! hash_equals($this->proposalSignature($locked, $proposal, $data), (string) ($data['proposal_signature'] ?? ''))) {
                throw ValidationException::withMessages(['tasks' => 'Recalcula la propuesta: los datos o los folios cambiaron desde la revisión.']);
            }
            if (SerialRange::query()->where('label_request_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['tasks' => 'Esta requisición tiene rangos históricos. Revisa su historial antes de crear otra reserva.']);
            }

            $rangeIds = [];
            $bundles = collect($proposal['bundles'])->sortBy(fn ($bundle) => json_encode([
                $bundle['rating'], $bundle['market'], $bundle['period_type'], $bundle['period_year'],
                $bundle['period_number'], str_pad((string) $bundle['range_start'], 10, '0', STR_PAD_LEFT),
            ]));
            foreach ($bundles as $key => $bundle) {
                $period = $this->folios->lockPeriod(
                    $bundle['rating'],
                    $bundle['market'],
                    $bundle['period_type'],
                    $bundle['period_year'],
                    $bundle['period_number'],
                    $bundle['operational_week'],
                );
                $next = $this->folios->highWater($period) + 1;
                foreach (collect($proposal['tasks'])->where('bundle_key', $key) as $taskKey => $task) {
                    if ((int) ($data['tasks'][$taskKey]['expected_start'] ?? 0) !== $next) {
                        throw ValidationException::withMessages(['tasks' => 'El consecutivo cambió o falta la propuesta. Recalcula los folios antes de liberar.']);
                    }
                }
                if ($next !== $bundle['range_start']) {
                    throw ValidationException::withMessages(['tasks' => 'Otra requisición reservó folios. Recalcula la propuesta.']);
                }

                $range = SerialRange::query()->create([
                    'serial_week_id' => $period->id,
                    'label_request_id' => $locked->id,
                    'range_start' => $next,
                    'range_end' => $bundle['range_end'],
                    'quantity' => $bundle['production_quantity'] + 1,
                    'production_quantity' => $bundle['production_quantity'],
                    'evidence_quantity' => 1,
                    'evidence_folio' => $bundle['evidence_folio'],
                    'job_number' => $bundle['job_number'],
                    'model' => $bundle['model'],
                    'created_by_user_id' => $user->id,
                    'status' => 'reserved',
                ]);
                $rangeIds[$key] = $range->id;
                $period->update(['last_serial_number' => $range->range_end]);
            }

            foreach ($proposal['tasks'] as $task) {
                $locked->workTasks()->create([
                    ...collect($task)->only([
                        'source_key', 'label_type', 'part_number', 'model', 'job_number', 'jobs',
                        'po_number', 'destination', 'quantity', 'evidence_quantity', 'assigned_to_user_id',
                        'folio_start', 'folio_end', 'evidence_folio', 'rating_part_number', 'folio_family',
                        'control_year', 'control_week', 'serial_period_type', 'serial_period_year',
                        'serial_period_number',
                    ])->all(),
                    'serial_range_id' => $task['bundle_key'] ? $rangeIds[$task['bundle_key']] : null,
                ]);
            }

            $onlyBundle = count($proposal['bundles']) === 1 ? reset($proposal['bundles']) : null;
            $locked->update([
                'serial_standard' => $data['serial_standard'],
                'job_status' => $data['job_status'],
                'review_notes' => $data['review_notes'] ?? null,
                'control_year' => $data['control_year'],
                'control_week' => $data['control_week'],
                'serial_period_type' => $onlyBundle['period_type'] ?? null,
                'serial_period_year' => $onlyBundle['period_year'] ?? null,
                'serial_period_number' => $onlyBundle['period_number'] ?? null,
                'folio_start' => $onlyBundle['range_start'] ?? null,
                'folio_end' => $onlyBundle['range_end'] ?? null,
                'released_at' => now(),
                'released_by_user_id' => $user->id,
                'status' => LabelRequest::STATUS_IN_PROGRESS,
            ]);
            $this->event($locked, $user, 'released', [
                'folio_mode' => $locked->folio_mode,
                'market' => $locked->serial_standard,
                'job_status' => $locked->job_status,
                'notes' => $locked->review_notes,
                'ranges' => $rangeIds,
            ]);

            return $locked;
        }, 3);
    }

    public function assign(LabelRequest $request, LabelWorkTask $task, ?int $operatorId, User $actor): void
    {
        DB::transaction(function () use ($request, $task, $operatorId, $actor) {
            $locked = LabelRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $task = $locked->workTasks()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->folio_mode, array_keys(LabelRequest::FOLIO_MODES), true)) {
                throw ValidationException::withMessages(['folio_mode' => 'Este tipo de trabajo ya no se puede procesar desde la aplicación.']);
            }
            if (! $this->canAssignTasks($actor)) {
                throw new AuthorizationException('Solo la líder puede asignar tareas de impresión.');
            }
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
            if (! in_array($locked->folio_mode, array_keys(LabelRequest::FOLIO_MODES), true)) {
                throw ValidationException::withMessages(['folio_mode' => 'Este tipo de trabajo ya no se puede procesar desde la aplicación.']);
            }
            if (! $this->canCompleteTask($actor, $task)) {
                throw new AuthorizationException('Solo la operadora asignada o la líder puede confirmar esta impresión.');
            }
            if ($task->status === 'completed' && $locked->status !== LabelRequest::STATUS_CANCELLED) {
                return $locked;
            }
            if (! $locked->released_at || $locked->status !== LabelRequest::STATUS_IN_PROGRESS) {
                throw ValidationException::withMessages(['status' => 'La requisición debe estar liberada y en preparación.']);
            }
            $operator = $this->assertOperator((int) $task->assigned_to_user_id);
            if (! Shift::whereKey($data['printed_shift_id'])->where('active', true)->exists()) {
                throw ValidationException::withMessages(['printed_shift_id' => 'Selecciona un turno activo.']);
            }
            $lastWorkDate = $locked->workTasks()->where('status', 'completed')->max('work_date');
            if ($data['work_date'] < $locked->request_date->toDateString() || ($lastWorkDate && $data['work_date'] < substr($lastWorkDate, 0, 10))) {
                throw ValidationException::withMessages(['work_date' => 'La fecha de trabajo no puede ser anterior a la requisición ni al trabajo ya confirmado.']);
            }
            $lastTask = $locked->workTasks()->where('status', 'pending')->count() === 1;
            $task->update([
                'status' => 'completed', 'printed_by_user_id' => $operator->id, 'printed_by_name' => $operator->name,
                'printed_shift_id' => $data['printed_shift_id'], 'work_date' => $data['work_date'], 'completed_at' => now(),
            ]);
            $this->event($locked, $actor, 'task_completed', ['task_id' => $task->id, 'operator' => $operator->id,
                'confirmed_by_user_id' => $actor->id,
                'label_type' => $task->label_type, 'part_number' => $task->part_number,
                'printed_by_name' => $operator->name,
                'shift_id' => $data['printed_shift_id'], 'work_date' => $data['work_date']]);

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
            if (! in_array($locked->folio_mode, array_keys(LabelRequest::FOLIO_MODES), true)) {
                throw ValidationException::withMessages(['folio_mode' => 'Este tipo de trabajo ya no se puede procesar desde la aplicación.']);
            }
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
