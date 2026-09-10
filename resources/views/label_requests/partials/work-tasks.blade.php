@php
    $operators = app(\App\Services\Labels\LabelRoomAdministrationService::class)->operators();
    $workShifts = \App\Models\Shift::where('active', true)->orderBy('code')->get();
@endphp
<section class="mt-6 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h2 class="text-xl font-bold">Trabajo liberado</h2><p class="mt-1 text-sm text-slate-600">Liberó {{ $labelRequest->releasedBy?->name ?? 'LabelRoom' }} · {{ $labelRequest->released_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</p></div>
        <span class="rounded-full bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800">{{ \App\Models\LabelRequest::FOLIO_MODES[$labelRequest->folio_mode] }}</span>
    </div>
    @foreach($labelRequest->workTasks as $task)
        <article class="rounded-2xl border {{ $task->status === 'completed' ? 'border-emerald-300' : 'border-slate-200' }} bg-white p-5">
            <div class="flex flex-wrap justify-between gap-3">
                <div><h3 class="text-lg font-bold">{{ ucfirst($task->label_type) }} · {{ $task->part_number }}</h3>
                    <p class="text-sm text-slate-600">{{ collect($task->jobs)->map(fn($job) => $job['job_number'].' · '.($job['model'] ?? ''))->implode(' / ') }}</p>
                </div>
                <span class="text-sm font-semibold {{ $task->status === 'completed' ? 'text-emerald-700' : 'text-slate-600' }}">{{ ['pending'=>'Pendiente', 'completed'=>'Impresión confirmada', 'cancelled'=>'Cancelada'][$task->status] ?? $task->status }}</span>
            </div>
            <dl class="mt-4 grid gap-4 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3 lg:grid-cols-6">
                <div><dt class="text-slate-500">Para producción</dt><dd class="mt-1 text-lg font-bold">{{ number_format($task->quantity) }}</dd></div>
                <div><dt class="text-slate-500">Evidencia</dt><dd class="mt-1 text-lg font-bold">{{ $task->evidence_quantity }}</dd></div>
                <div><dt class="text-slate-500">Total por imprimir</dt><dd class="mt-1 text-lg font-bold">{{ number_format($task->quantity + $task->evidence_quantity) }}</dd></div>
                <div><dt class="text-slate-500">Folios del / hasta</dt><dd class="mt-1 font-bold">{{ $task->folio_start !== null ? $task->folio_start.' – '.$task->folio_end : 'No aplica' }}</dd></div>
                <div><dt class="text-slate-500">Folio de evidencia</dt><dd class="mt-1 font-bold">{{ $task->evidence_folio ?? 'No aplica' }}</dd></div>
                <div><dt class="text-slate-500">Familia · Semana</dt><dd class="mt-1 font-bold">{{ $task->folio_start !== null ? $task->folio_family.' · '.$task->control_year.'/'.$task->control_week : '—' }}</dd></div>
            </dl>
            @if($task->status === 'completed')
                <p class="mt-4 text-sm text-emerald-800">Imprimió <strong>{{ $task->printed_by_name }}</strong> · Turno {{ $task->printedShift?->code }} · Fecha de trabajo {{ $task->work_date?->format('d/m/Y') }}.</p>
            @elseif($task->status === 'pending' && $labelRequest->status === 'in_progress')
                <form method="POST" action="{{ route('label_requests.tasks.assign', [$labelRequest, $task]) }}" class="mt-4 flex flex-wrap items-end gap-2">
                    @csrf
                    <label class="min-w-64 text-sm">Operadora asignada
                        <select name="assigned_to_user_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">Sin asignar</option>
                            @foreach($operators as $operator)<option value="{{ $operator->id }}" @selected($task->assigned_to_user_id == $operator->id)>{{ $operator->name }}</option>@endforeach
                        </select>
                    </label>
                    <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Guardar asignación</button>
                </form>
                <form method="POST" action="{{ route('label_requests.tasks.complete', [$labelRequest, $task]) }}" class="mt-4 rounded-xl border border-slate-200 p-4">
                    @csrf
                    <div class="grid gap-3 md:grid-cols-3">
                        <label class="text-sm">Imprimió
                            <select name="printed_by_user_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Selecciona una operadora</option>
                                @foreach($operators as $operator)<option value="{{ $operator->id }}" @selected(($task->assigned_to_user_id ?? auth()->id()) == $operator->id)>{{ $operator->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm">Turno de impresión
                            <select name="printed_shift_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Selecciona un turno</option>
                                @foreach($workShifts as $shift)<option value="{{ $shift->id }}" @selected(auth()->user()->shift_id == $shift->id)>{{ $shift->code }} · {{ $shift->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm">Fecha operativa del turno
                            <input type="date" name="work_date" value="{{ now()->toDateString() }}" min="{{ $labelRequest->request_date->toDateString() }}" max="{{ now()->toDateString() }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                        </label>
                    </div>
                    @if($labelRequest->isOriginalReprint() && !$labelRequest->physical_signed_at)
                        <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="physical_signed" value="1" /> La requisición física quedó firmada (obligatorio para cerrar la última tarea).</label>
                    @endif
                    <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="work_confirmed" value="1" required /> Confirmo que se imprimieron las {{ number_format($task->quantity + $task->evidence_quantity) }} etiquetas de esta tarea.</label>
                    <button class="mt-4 rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white hover:bg-emerald-800">Confirmar impresión {{ ucfirst($task->label_type) }}</button>
                </form>
            @endif
        </article>
    @endforeach
    @if($labelRequest->physical_signed_at)<p class="text-sm text-slate-600">Requisición física firmada: confirmado el {{ $labelRequest->physical_signed_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}.</p>@endif
</section>

