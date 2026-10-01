@extends('layouts.app', ['title' => 'Revisión administrativa', 'mainClass' => 'max-w-[1600px]'])
@section('content')
@php
    $values = $input ?? old();
    $canAssignTasks = app(\App\Services\Labels\LabelRoomAdministrationService::class)->canAssignTasks(auth()->user());
@endphp
<div class="space-y-5">
    @include('label_requests.partials.admin-navigation')
    <header class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="text-sm font-semibold text-red-700">LABELROOM · REVISIÓN ADMINISTRATIVA</div>
        <h1 class="mt-1 text-2xl font-bold text-slate-950">Revisar requisición #{{ $labelRequest->id }}</h1>
        <p class="mt-2 text-slate-600">{{ $labelRequest->line?->code }} · {{ $labelRequest->request_date->format('d/m/Y') }} · {{ $labelRequest->folioModeLabel() }}</p>
        <a href="{{ route('label_requests.show', $labelRequest) }}" class="mt-3 inline-block text-sm font-semibold text-blue-700 underline">Ver solicitud y datos de Oracle</a>
        @if($labelRequest->isLostLabelRework())
            <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">Reposición por faltantes de la requisición #{{ $labelRequest->source_label_request_id }}. Motivo: {{ $labelRequest->rework_reason }}. Verifica el periodo actual antes de liberar; el sistema emitirá folios nuevos y conservará los rangos anteriores.</p>
        @endif
    </header>
    @include('label_requests.partials.messages')
    @if(!array_key_exists($labelRequest->folio_mode, \App\Models\LabelRequest::FOLIO_MODES))
        <div class="rounded-xl bg-white p-6">Este tipo de trabajo ya no se procesa en la aplicación. Consulta sus datos históricos en el detalle.</div>
    @elseif($labelRequest->released_at || !in_array($labelRequest->status, ['requested', 'in_progress']))
        <div class="rounded-xl bg-white p-6">Esta requisición ya fue liberada o cerrada. Consulta sus tareas y folios en el detalle.</div>
    @else
    <form method="POST" action="{{ isset($proposal) ? route('label_requests.release', $labelRequest) : route('label_requests.preview', $labelRequest) }}" class="space-y-5">
        @csrf
        @if(isset($proposalSignature))<input type="hidden" name="proposal_signature" value="{{ $proposalSignature }}" />@endif
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold">1. Elegir el periodo de folios y validar la requisición</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="text-sm font-medium">Mercado de serialización
                    <select id="release-market" name="serial_standard" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Selecciona el mercado</option>
                        @foreach($markets as $market)
                            <option value="{{ $market }}" @selected(($values['serial_standard'] ?? $selectedMarket) === $market)>{{ $market }} · {{ $market === 'UL' ? 'Control semanal' : 'Control mensual' }}</option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-xs text-slate-500">Si el catálogo no lo resolvió, confírmalo aquí antes de liberar.</span>
                </label>
                <label class="text-sm font-medium">Año del periodo de folios
                    <input type="number" name="control_year" min="2000" max="2100" value="{{ $values['control_year'] ?? $defaultYear }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                </label>
                <label class="text-sm font-medium"><span id="release-week-label">Semana UL para estos folios</span>
                    <input type="number" name="control_week" min="1" max="53" value="{{ $values['control_week'] ?? '' }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    <span id="release-week-help" class="mt-1 block text-xs font-normal text-slate-500">El consecutivo continúa dentro de la semana que elijas.</span>
                </label>
                <label id="release-month-field" class="text-sm font-medium">Mes de folios EMEA / ANZ / APJ
                    <select id="release-month" name="serial_month" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @for($month = 1; $month <= 12; $month++)
                            <option value="{{ $month }}" @selected((int) ($values['serial_month'] ?? $defaultMonth) === $month)>{{ \App\Support\SerialPeriods::describe('month', $month) }}</option>
                        @endfor
                    </select>
                </label>
                <label class="text-sm font-medium">Clasificación del trabajo
                    <select name="job_status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach(\App\Models\LabelRequest::JOB_STATUSES as $code => $label)
                            <option value="{{ $code }}" @selected(($values['job_status'] ?? $labelRequest->job_status) === $code)>{{ $code }} · {{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <p class="mt-3 text-sm text-slate-600">El año y periodo que elijas determinan el rango de esta requisición. El sistema busca el último folio del NP Rating en ese mismo periodo y suma las etiquetas solicitadas. Al abrir un periodo nuevo de un NP ya registrado, comienza en 1. La fecha de la requisición no cambia el periodo elegido.</p>
            <p class="mt-1 text-sm font-medium text-slate-700">Esta sección solo define el periodo: «Revisar propuesta» calcula el rango y «Liberar requisición» reserva los folios.</p>
            <label class="mt-4 block text-sm font-medium">Observaciones de la revisión
                <textarea name="review_notes" maxlength="2000" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ $values['review_notes'] ?? '' }}</textarea>
            </label>
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input type="checkbox" name="plan_checked" value="1" required @checked($values['plan_checked'] ?? false) />
                Revisé Job, modelo, NP, cantidades, PO y destino contra la información de producción disponible.
            </label>
        </section>
        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-bold">2. Preparar folios y responsables</h2>
                <p class="mt-1 text-sm text-slate-600">Cada renglón mantiene su cantidad de producción. Serial y Rating del mismo producto comparten folios. La evidencia se conserva por cada tarea física.</p>
                <a href="{{ route('label_requests.weeks') }}" target="_blank" rel="noopener" class="text-sm font-semibold text-blue-700 underline">Inicializar o consultar el último folio del Excel</a>
            </div>
            <div class="rounded-xl border border-blue-200 bg-white p-4 text-sm">
                <h3 class="font-bold">Controles registrados · {{ $selectedMarket ?: 'Mercado pendiente' }} · {{ $periodNumber ? \App\Support\SerialPeriods::describe($periodType, $periodNumber) : 'Semana pendiente' }} {{ $periodYear }}</h3>
                <p class="mt-1 text-slate-600">El consecutivo se controla por NP Rating, mercado y periodo. El ensamble y el SKU se conservan como referencia operativa.</p>
                @forelse($availableControls as $control)
                    <p class="mt-2">NP Rating <strong>{{ $control->label_part_number }}</strong> · Mercado <strong>{{ $control->serial_standard }}</strong> · Último reservado <strong>{{ number_format($control->last_serial_number) }}</strong></p>
                @empty
                    <p class="mt-2 text-slate-600">No hay un control registrado para estas etiquetas y este periodo.</p>
                @endforelse
            </div>
            @foreach($lines as $key => $line)
                @php
                    $taskInput = $values['tasks'][$key] ?? [];
                    $planned = $proposal['tasks'][$key] ?? null;
                @endphp
                <article class="rounded-2xl border {{ $line['label_type'] === 'serial' ? 'border-blue-200' : ($line['label_type'] === 'rating' ? 'border-violet-200' : 'border-amber-200') }} bg-white p-5">
                    <div class="flex flex-wrap justify-between gap-3">
                        <div><h3 class="text-lg font-bold">{{ ucfirst($line['label_type']) }} · {{ $line['part_number'] }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ collect($line['jobs'])->map(fn($job) => $job['job_number'].' · '.($job['model'] ?? 'Sin modelo'))->implode(' / ') }}</p>
                        @if($line['label_type'] === 'shipping' && filled($line['po_number']))
                            <p class="mt-1 text-sm text-slate-700"><span class="font-semibold">PO:</span> {{ $line['po_number'] }}</p>
                        @endif
                        </div>
                        <div class="text-right text-sm">Producción <strong class="text-lg">{{ number_format($line['quantity']) }}</strong><br>
                            Evidencia <strong>1</strong> · Total <strong>{{ number_format($line['quantity'] + 1) }}</strong>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        @if($canAssignTasks)
                            <label class="text-sm font-medium">Asignar a
                                <select name="tasks[{{ $key }}][assigned_to_user_id]" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="">Asignar después de liberar</option>
                                    @foreach($operators as $operator)<option value="{{ $operator->id }}" @selected(($taskInput['assigned_to_user_id'] ?? '') == $operator->id)>{{ $operator->name }}</option>@endforeach
                                </select>
                            </label>
                        @else
                            <input type="hidden" name="tasks[{{ $key }}][assigned_to_user_id]" value="" />
                            <div class="text-sm text-slate-600">La líder asignará la tarea antes de confirmar la impresión.</div>
                        @endif
                        @if($line['requires_folios'])
                            <label class="text-sm font-medium">Ensamble / SKU (referencia)
                                <input readonly value="{{ $line['assembly_number'] }}{{ $line['folio_family'] ? ' / '.$line['folio_family'] : '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase" />
                                @if(blank($line['folio_family']))
                                    <span class="mt-1 block text-xs text-slate-600">El SKU no está mapeado; esto no bloquea el control de etiquetas.</span>
                                @else
                                    <span class="mt-1 block text-xs text-slate-600">Ensamble {{ $line['assembly_number'] }} → SKU {{ $line['folio_family'] }}.</span>
                                @endif
                            </label>
                            <div class="text-sm font-medium">Folio que se conserva como evidencia
                                <div class="mt-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2">Primero del rango</div>
                            </div>
                        @endif
                    </div>
                    @if($planned && $line['requires_folios'])
                        <div class="mt-4 grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-4">
                            <label class="text-sm">Folios del<input readonly value="{{ $planned['folio_start'] }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-bold" /></label>
                            <label class="text-sm">Hasta<input readonly value="{{ $planned['folio_end'] }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-bold" /></label>
                            <div class="text-sm">Evidencia<div class="mt-2 font-bold">{{ $planned['evidence_folio'] ?? 'Sin pieza adicional' }}</div></div>
                            <div class="text-sm">Periodo serial<div class="mt-2 font-bold">{{ \App\Support\SerialPeriods::describe($planned['serial_period_type'], $planned['serial_period_number']) }} {{ $planned['serial_period_year'] }}</div><div class="text-xs text-slate-500">Control operativo: {{ $planned['control_year'] }} / semana {{ $planned['control_week'] }}</div></div>
                        </div>
                        <input type="hidden" name="tasks[{{ $key }}][expected_start]" value="{{ $planned['folio_start'] }}" />
                    @endif
                </article>
            @endforeach
        </section>
        <footer class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-5">
            @if(isset($proposal))
                <button class="rounded-xl bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800">Liberar requisición y reservar folios</button>
                <button formaction="{{ route('label_requests.preview', $labelRequest) }}" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold">Recalcular propuesta</button>
                <p class="text-sm text-slate-600">La propuesta todavía no consume folios. Si cambiaste algún dato, recalcula antes de liberar.</p>
            @else
                <button class="rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white">Revisar propuesta de folios</button>
                <p class="text-sm text-slate-600">Podrás revisar los rangos antes de liberarlos.</p>
            @endif
        </footer>
    </form>
    @endif
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const market = document.getElementById('release-market');
    const monthField = document.getElementById('release-month-field');
    const month = document.getElementById('release-month');
    const weekLabel = document.getElementById('release-week-label');
    const weekHelp = document.getElementById('release-week-help');

    function updatePeriodFields() {
        const monthly = market.value !== '' && market.value !== 'UL';
        monthField.hidden = !monthly;
        month.disabled = !monthly;
        month.required = monthly;
        weekLabel.textContent = monthly ? 'Semana operativa (solo referencia)' : 'Semana UL para estos folios';
        weekHelp.textContent = monthly
            ? 'Para este mercado, el consecutivo se controla por el mes elegido.'
            : 'El consecutivo continúa dentro de la semana que elijas.';
    }

    market.addEventListener('change', updatePeriodFields);
    updatePeriodFields();
});
</script>
@endpush
