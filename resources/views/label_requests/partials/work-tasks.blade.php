<section class="mt-6 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h2 class="text-xl font-bold">Trabajo liberado</h2><p class="mt-1 text-sm text-slate-600">Liberó {{ $labelRequest->releasedBy?->name ?? 'LabelRoom' }} · {{ $labelRequest->released_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</p></div>
        <span class="rounded-full bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800">{{ $labelRequest->folioModeLabel() }}</span>
    </div>
    @foreach($labelRequest->workTasks as $task)
        <article class="rounded-2xl border {{ $task->status === 'completed' ? 'border-emerald-300' : 'border-slate-200' }} bg-white p-5">
            <div class="flex flex-wrap justify-between gap-3">
                <div><h3 class="text-lg font-bold">{{ ucfirst($task->label_type) }} · {{ $task->part_number }}</h3>
                    @if($task->label_type !== 'shipping')
                        <p class="text-sm text-slate-600">{{ collect($task->jobs)->map(fn($job) => $job['job_number'].' · '.($job['model'] ?? ''))->implode(' / ') }}</p>
                    @endif
                    @if($task->label_type === 'shipping')
                        @foreach($task->jobs as $job)
                            <p class="mt-1 text-sm text-slate-700">{{ $job['job_number'] }} · {{ $job['model'] ?? 'Sin modelo' }}: <span class="font-semibold">PO</span> {{ $job['po_number'] ?? $task->po_number ?? '—' }} · <span class="font-semibold">Destino</span> {{ $job['destination'] ?? $task->destination ?? '—' }}</p>
                        @endforeach
                    @elseif($task->label_type === 'inner' && filled($task->po_number ?: $labelRequest->po_number))
                        <p class="mt-1 text-sm text-slate-700"><span class="font-semibold">PO:</span> {{ $task->po_number ?: $labelRequest->po_number }}</p>
                    @endif
                </div>
                <span class="text-sm font-semibold {{ $task->status === 'completed' ? 'text-emerald-700' : 'text-slate-600' }}">{{ ['pending'=>'Pendiente', 'completed'=>'Impresión confirmada', 'cancelled'=>'Cancelada'][$task->status] ?? $task->status }}</span>
            </div>
            <dl class="mt-4 grid gap-4 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3 lg:grid-cols-6">
                <div><dt class="text-slate-500">Para producción</dt><dd class="mt-1 text-lg font-bold">{{ number_format($task->quantity) }}</dd></div>
                <div><dt class="text-slate-500">Evidencia</dt><dd class="mt-1 text-lg font-bold">{{ $task->evidence_quantity }}</dd></div>
                <div><dt class="text-slate-500">Total por imprimir</dt><dd class="mt-1 text-lg font-bold">{{ number_format($task->quantity + $task->evidence_quantity) }}</dd></div>
                <div><dt class="text-slate-500">Folios del / hasta</dt><dd class="mt-1 font-bold">{{ $task->folio_start !== null ? $task->folio_start.' – '.$task->folio_end : 'No aplica' }}</dd></div>
                <div><dt class="text-slate-500">Folio de evidencia</dt><dd class="mt-1 flex flex-wrap items-center gap-2 font-bold">{{ $task->evidence_folio ?? 'No aplica' }}@if($workTaskMonthLetters[$task->id] ?? null)<span class="rounded-md bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">Letra del mes: {{ $workTaskMonthLetters[$task->id] }}</span>@endif</dd></div>
                <div><dt class="text-slate-500">Mercado · Periodo</dt><dd class="mt-1 font-bold">{{ $workTaskPeriodLabels[$task->id] }}@if($task->folio_start !== null)<br><span class="text-xs font-normal text-slate-500">Control: {{ $task->control_year }}/Sem. {{ $task->control_week }}</span>@endif</dd></div>
            </dl>
            <p class="mt-4 text-sm text-slate-700">Operadora asignada: <strong>{{ $task->assignee?->name ?? 'Pendiente de asignar' }}</strong></p>
            @if($task->status === 'completed')
                <p class="mt-4 text-sm text-emerald-800">Imprimió <strong>{{ $task->printed_by_name }}</strong> · Turno {{ $task->printedShift?->code }} · Fecha de trabajo {{ $task->work_date?->format('d/m/Y') }}.</p>
            @elseif($task->status === 'pending' && $labelRequest->status === 'in_progress' && $canProcessWorkTasks)
                @if($canAssignWorkTasks)
                    <form method="POST" action="{{ route('label_requests.tasks.assign', [$labelRequest, $task]) }}" class="mt-4 flex flex-wrap items-end gap-2">
                        @csrf
                        <label class="min-w-64 text-sm">Asignar o reasignar
                            <select name="assigned_to_user_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Sin asignar</option>
                                @foreach($workOperators as $operator)<option value="{{ $operator->id }}" @selected($task->assigned_to_user_id == $operator->id)>{{ $operator->name }}</option>@endforeach
                            </select>
                        </label>
                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Guardar asignación</button>
                    </form>
                @endif
                @if($canCompleteWorkTasks[$task->id] ?? false)
                    <form method="POST" action="{{ route('label_requests.tasks.complete', [$labelRequest, $task]) }}" class="mt-4 rounded-xl border border-slate-200 p-4">
                        @csrf
                        <p class="mb-3 text-sm text-slate-700">Se registrará a <strong>{{ $task->assignee?->name }}</strong> como quien imprimió. @if($isLabelRoomLeader) Si imprimió otra persona, reasigna la tarea antes de confirmar. @endif</p>
                        <div class="grid gap-3 md:grid-cols-2">
                            <label class="text-sm">Turno de impresión
                                <select name="printed_shift_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="">Selecciona un turno</option>
                                    @foreach($workShifts as $shift)<option value="{{ $shift->id }}" @selected($selectedWorkShiftId == $shift->id)>{{ $shift->code }} · {{ $shift->name }}</option>@endforeach
                                </select>
                            </label>
                            <label class="text-sm">Fecha operativa del turno
                                <input type="date" name="work_date" value="{{ now()->toDateString() }}" min="{{ $labelRequest->request_date->toDateString() }}" max="{{ now()->toDateString() }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                            </label>
                        </div>
                        <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="work_confirmed" value="1" required /> Confirmo que se imprimieron las {{ number_format($task->quantity + $task->evidence_quantity) }} etiquetas de esta tarea.</label>
                        <button class="mt-4 rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white hover:bg-emerald-800">Confirmar impresión {{ ucfirst($task->label_type) }}</button>
                    </form>
                @else
                    <p class="mt-4 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">{{ $task->assigned_to_user_id ? 'Solo la operadora asignada o la líder pueden confirmar esta impresión.' : 'La líder debe asignar esta tarea antes de confirmar la impresión.' }}</p>
                @endif
            @endif
        </article>
    @endforeach
</section>
