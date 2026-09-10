@extends('layouts.app', ['title' => 'Revisión administrativa', 'mainClass' => 'max-w-[1600px]'])
@section('content')
@php
    $values = $input ?? old();
@endphp
<div class="space-y-5">
    @include('label_requests.partials.admin-navigation')
    <header class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="text-sm font-semibold text-red-700">LABELROOM · REVISIÓN ADMINISTRATIVA</div>
        <h1 class="mt-1 text-2xl font-bold text-slate-950">Revisar requisición #{{ $labelRequest->id }}</h1>
        <p class="mt-2 text-slate-600">{{ $labelRequest->line?->code }} · {{ $labelRequest->request_date->format('d/m/Y') }} · {{ \App\Models\LabelRequest::FOLIO_MODES[$labelRequest->folio_mode] }}</p>
        <a href="{{ route('label_requests.show', $labelRequest) }}" class="mt-3 inline-block text-sm font-semibold text-blue-700 underline">Ver solicitud y datos de Oracle</a>
    </header>
    @include('label_requests.partials.messages')
    @if($labelRequest->released_at || !in_array($labelRequest->status, ['requested', 'in_progress']))
        <div class="rounded-xl bg-white p-6">Esta requisición ya fue liberada o cerrada. Consulta sus tareas y folios en el detalle.</div>
    @else
    <form method="POST" action="{{ isset($proposal) ? route('label_requests.release', $labelRequest) : route('label_requests.preview', $labelRequest) }}" class="space-y-5">
        @csrf
        @if(isset($proposalSignature))<input type="hidden" name="proposal_signature" value="{{ $proposalSignature }}" />@endif
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-bold">1. Validar la operación</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <label class="text-sm font-medium">Año de control
                    <input type="number" name="control_year" min="2000" max="2100" value="{{ $values['control_year'] ?? $defaultYear }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                </label>
                <label class="text-sm font-medium">Semana autorizada
                    <input type="number" name="control_week" min="1" max="53" value="{{ $values['control_week'] ?? $labelRequest->week }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                </label>
                <label class="text-sm font-medium">Clasificación del trabajo
                    <select name="job_status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach(\App\Models\LabelRequest::JOB_STATUSES as $code => $label)
                            <option value="{{ $code }}" @selected(($values['job_status'] ?? $labelRequest->job_status) === $code)>{{ $code }} · {{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            @if($labelRequest->isOriginalReprint())
                <p class="mt-3 text-sm text-amber-800">Cada rango reimpreso conservará su semana y año originales. No se reserva evidencia adicional.</p>
                <label class="mt-3 flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="originals_received" value="1" required @checked($values['originals_received'] ?? false) /> Recibí físicamente las etiquetas originales.
                </label>
            @endif
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
                <h3 class="font-bold">Controles registrados · {{ $controlsYear }} · Semana {{ $controlsWeek }}</h3>
                <p class="mt-1 text-slate-600">El control debe coincidir en NP Rating, familia, año y semana. La familia es el SKU obtenido del ensamble de la Job en Master Model Mapping.</p>
                @forelse($availableControls as $control)
                    <p class="mt-2">NP Rating <strong>{{ $control->label_part_number }}</strong> · Familia <strong>{{ $control->folio_family }}</strong> · Último reservado <strong>{{ number_format($control->last_serial_number) }}</strong></p>
                @empty
                    <p class="mt-2 text-slate-600">No hay controles con familia registrada para estas etiquetas en este año y semana.</p>
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
                        <p class="mt-1 text-sm text-slate-600">{{ collect($line['jobs'])->map(fn($job) => $job['job_number'].' · '.($job['model'] ?? 'Sin modelo'))->implode(' / ') }}</p></div>
                        <div class="text-right text-sm">Producción <strong class="text-lg">{{ number_format($line['quantity']) }}</strong><br>
                            Evidencia <strong>{{ $labelRequest->isOriginalReprint() ? 0 : 1 }}</strong> · Total <strong>{{ number_format($line['quantity'] + ($labelRequest->isOriginalReprint() ? 0 : 1)) }}</strong>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        <label class="text-sm font-medium">Asignar a
                            <select name="tasks[{{ $key }}][assigned_to_user_id]" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">Asignar después de liberar</option>
                                @foreach($operators as $operator)<option value="{{ $operator->id }}" @selected(($taskInput['assigned_to_user_id'] ?? '') == $operator->id)>{{ $operator->name }}</option>@endforeach
                            </select>
                        </label>
                        @if($line['requires_folios'])
                            <label class="text-sm font-medium">NP Rating de control
                                <input name="tasks[{{ $key }}][rating_part_number]" value="{{ $taskInput['rating_part_number'] ?? $line['rating_part_number'] }}" maxlength="80" required @readonly($line['label_type'] === 'rating') class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase" />
                            </label>
                            <label class="text-sm font-medium">Familia de folios (SKU)
                                <input readonly value="{{ $line['folio_family'] }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase" />
                                @if(blank($line['folio_family']))
                                    <span class="mt-1 block text-xs text-amber-800">No se encontró el SKU del ensamble {{ $line['assembly_number'] ?: 'sin identificar' }} en Master Model Mapping.</span>
                                @else
                                    <span class="mt-1 block text-xs text-slate-600">Ensamble {{ $line['assembly_number'] }} → SKU {{ $line['folio_family'] }}.</span>
                                @endif
                            </label>
                            @if($labelRequest->isOriginalReprint())
                                <label class="text-sm font-medium">Rango original registrado (opcional)
                                    <input type="number" name="tasks[{{ $key }}][source_range_id]" list="originalRanges" value="{{ $taskInput['source_range_id'] ?? '' }}" min="1" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                                </label>
                                <label class="text-sm font-medium">Referencia de originales sin registro digital
                                    <input name="tasks[{{ $key }}][original_reference]" value="{{ $taskInput['original_reference'] ?? '' }}" maxlength="255" placeholder="Requisición física / hoja de Excel" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                                </label>
                                <label class="text-sm font-medium">Año de los folios originales
                                    <input type="number" name="tasks[{{ $key }}][original_year]" value="{{ $taskInput['original_year'] ?? $values['control_year'] ?? $defaultYear }}" min="2000" max="2100" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                                </label>
                                <label class="text-sm font-medium">Semana de los folios originales
                                    <input type="number" name="tasks[{{ $key }}][original_week]" value="{{ $taskInput['original_week'] ?? $values['control_week'] ?? $labelRequest->week }}" min="1" max="53" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                                </label>
                                <label class="text-sm font-medium">Reimprimir del folio
                                    <input type="number" name="tasks[{{ $key }}][folio_start]" value="{{ $taskInput['folio_start'] ?? '' }}" min="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                                </label>
                                <label class="text-sm font-medium">Hasta el folio
                                    <input type="number" name="tasks[{{ $key }}][folio_end]" value="{{ $taskInput['folio_end'] ?? '' }}" min="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                                </label>
                            @else
                                <label class="text-sm font-medium">Folio que se conserva como evidencia
                                    <select name="tasks[{{ $key }}][evidence_position]" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                                        <option value="last" @selected(($taskInput['evidence_position'] ?? 'last') === 'last')>Último del rango</option>
                                        <option value="first" @selected(($taskInput['evidence_position'] ?? 'last') === 'first')>Primero del rango</option>
                                    </select>
                                </label>
                            @endif
                        @endif
                    </div>
                    @if($planned && $line['requires_folios'])
                        <div class="mt-4 grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-4">
                            <label class="text-sm">Folios del<input readonly value="{{ $planned['folio_start'] }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-bold" /></label>
                            <label class="text-sm">Hasta<input readonly value="{{ $planned['folio_end'] }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-bold" /></label>
                            <div class="text-sm">Evidencia<div class="mt-2 font-bold">{{ $planned['evidence_folio'] ?? 'Sin pieza adicional' }}</div></div>
                            <div class="text-sm">Control<div class="mt-2 font-bold">{{ $planned['control_year'] }} · Semana {{ $planned['control_week'] }}</div></div>
                        </div>
                        <input type="hidden" name="tasks[{{ $key }}][expected_start]" value="{{ $planned['folio_start'] }}" />
                    @endif
                </article>
            @endforeach
        </section>
        <datalist id="originalRanges">
            @foreach($sources as $source)<option value="{{ $source->id }}">Req #{{ $source->label_request_id }} · {{ $source->week->label_part_number }} / {{ $source->week->folio_family ?: 'Familia histórica sin identificar' }} · {{ $source->range_start }}–{{ $source->range_end }} · {{ $source->week->year }}/{{ $source->week->week }}</option>@endforeach
        </datalist>
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
