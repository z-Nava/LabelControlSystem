@extends('layouts.app', ['title' => 'Periodos y folios', 'mainClass' => 'max-w-[1600px]'])

@section('content')
<div class="space-y-5">
    @include('label_requests.partials.admin-navigation')
    <header>
        <h1 class="text-2xl font-bold">Control de periodos y folios</h1>
        <p class="mt-1 text-slate-600">Consecutivos independientes por NP Rating y mercado. UL se controla por semana; EMEA, ANZ y APJ por mes.</p>
    </header>
    @include('label_requests.partials.messages')

    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4">
        <label class="text-sm">Año
            <input type="number" name="year" value="{{ $year }}" min="2000" max="2100" class="mt-1 block w-28 rounded-lg border border-slate-300 px-3 py-2" />
        </label>
        <label class="text-sm">Mercado
            <select name="serial_standard" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Todos</option>
                @foreach($markets as $market)<option value="{{ $market }}" @selected(($filters['serial_standard'] ?? '') === $market)>{{ $market }}</option>@endforeach
            </select>
        </label>
        <label class="text-sm">Número de periodo
            <input type="number" name="period_number" value="{{ $filters['period_number'] ?? '' }}" min="1" max="53" placeholder="Todos" class="mt-1 block w-36 rounded-lg border border-slate-300 px-3 py-2" />
        </label>
        <label class="text-sm">NP Rating
            <input name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2" />
        </label>
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-white">Filtrar</button>
    </form>

    <section class="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm" aria-labelledby="period-registration-title">
        <div class="border-b border-blue-100 bg-blue-50 px-5 py-5 sm:px-7">
            <div class="text-xs font-bold uppercase tracking-wide text-blue-700">Alta inicial o avance verificado</div>
            <h2 id="period-registration-title" class="mt-1 text-xl font-bold text-slate-900">Registrar el último folio de un periodo</h2>
            <p class="mt-2 max-w-4xl text-sm text-slate-700">Primero ubica en Excel a qué semana o mes pertenece el último folio. Ese es el periodo que debes registrar. La semana operativa es solo una referencia del alta inicial: no mueve folios a otra semana. Si el periodo ya existe, puedes registrar un avance verificado, pero nunca reducir el consecutivo.</p>
        </div>

        <div class="grid gap-3 px-5 py-5 sm:px-7 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">1 · Buscar en Excel</div>
                <p class="mt-2 text-sm text-slate-700">Confirma el <strong>NP Rating, mercado, periodo</strong> y último folio usado en ese mismo periodo.</p>
            </div>
            <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4">
                <div class="text-xs font-bold uppercase tracking-wide text-blue-700">2 · Registrar el periodo del folio</div>
                <p class="mt-2 text-sm text-slate-700">Ejemplo: si Excel dice <strong>semana 37 · folio 361</strong>, registra <strong>37 y 361</strong>, aunque hoy estés en la semana 38.</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
                <div class="text-xs font-bold uppercase tracking-wide text-emerald-700">3 · Liberar la requisición</div>
                <p class="mt-2 text-sm text-slate-700">Al liberar, selecciona el mismo periodo para continuar desde el siguiente folio. Si Cuarto de Etiquetas cambia a una semana o mes nuevo, ese periodo inicia desde <strong>1</strong>.</p>
            </div>
        </div>

        <form id="period-control-form" method="POST" action="{{ route('label_requests.weeks.initialize') }}" class="space-y-6 border-t border-slate-200 px-5 py-6 sm:px-7">
            @csrf
            <div>
                <div class="mb-3 flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">1</span><h3 class="font-semibold text-slate-900">Identifica la etiqueta</h3></div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-800">NP Rating
                        <input id="period-part" name="label_part_number" list="rating-part-options" value="{{ old('label_part_number') }}" maxlength="80" required autocomplete="off" placeholder="Busca o escribe el NP Rating" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 uppercase focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100" />
                        <span class="mt-1 block text-xs font-normal text-slate-500">Lista buscable de NP Rating activos del catálogo Rating y Ensamble 018. También puedes escribir uno histórico que aún no esté en el catálogo.</span>
                        @if($ratingOptions->isEmpty())<span class="mt-1 block text-xs text-amber-700">Aún no hay NP Rating activos en el catálogo; verifica el NP manualmente.</span>@endif
                    </label>
                    <datalist id="rating-part-options">
                        @foreach($ratingOptions as $option)
                            <option value="{{ $option['part'] }}" label="{{ implode(', ', $option['markets']) }}" data-markets="{{ implode(',', $option['markets']) }}"></option>
                        @endforeach
                    </datalist>
                    <label class="block text-sm font-medium text-slate-800">Mercado
                        <select id="period-market" name="serial_standard" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Selecciona el mercado del NP Rating...</option>
                            @foreach($markets as $market)<option value="{{ $market }}" @selected(old('serial_standard') === $market)>{{ $market }} · {{ $market === 'UL' ? 'semanal' : 'mensual' }}</option>@endforeach
                        </select>
                        <span id="period-market-hint" class="mt-1 block text-xs font-normal text-slate-500" aria-live="polite">Confirma que coincida con el mercado del Excel o del catálogo.</span>
                    </label>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-5">
                <div class="mb-3 flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">2</span><h3 class="font-semibold text-slate-900">Ubica el periodo al que pertenece el último folio</h3></div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label class="block text-sm font-medium text-slate-800">Año del periodo en Excel
                        <input id="period-year" type="number" name="year" value="{{ old('year', now(config('app.display_timezone'))->year) }}" min="2000" max="2100" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100" />
                        <span class="mt-1 block text-xs font-normal text-slate-500">El año de la semana o mes que estás registrando.</span>
                    </label>
                    <label class="block text-sm font-medium text-slate-800"><span id="period-number-label">Semana o mes del último folio</span>
                        <input id="period-number" type="number" name="period_number" value="{{ old('period_number') }}" min="1" max="53" required aria-describedby="period-number-help" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100" />
                        <span id="period-number-help" class="mt-1 block text-xs font-normal text-slate-500">UL: semana 1–53. EMEA, ANZ y APJ: mes 1–12.</span>
                    </label>
                    <label class="block text-sm font-medium text-slate-800">Último folio usado en ese periodo
                        <input id="period-last-folio" type="number" name="last_serial_number" value="{{ old('last_serial_number') }}" min="0" max="4294967294" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100" />
                        <span class="mt-1 block text-xs font-normal text-slate-500">Captura 0 solo si en ese periodo no hubo impresiones.</span>
                    </label>
                    <label class="block text-sm font-medium text-slate-800">Semana operativa inicial <span class="font-normal text-slate-500">(referencia)</span>
                        <input id="period-operational-week" type="number" name="control_week" value="{{ old('control_week', now(config('app.display_timezone'))->isoWeek()) }}" min="1" max="53" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100" />
                        <span class="mt-1 block text-xs font-normal text-slate-500">Se guarda al crear el periodo y se conserva en avances posteriores. No decide el periodo serial.</span>
                    </label>
                </div>
                <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4" aria-live="polite">
                    <div id="period-preview-heading" class="font-semibold text-blue-950">Vista previa del control</div>
                    <p id="period-preview-copy" class="mt-1 text-sm text-blue-900">Selecciona el mercado y captura el periodo y último folio para revisar lo que guardarás.</p>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-5">
                <div class="mb-3 flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">3</span><h3 class="font-semibold text-slate-900">Deja evidencia de la verificación</h3></div>
                <label class="block text-sm font-medium text-slate-800">Referencia y motivo del registro
                    <textarea name="opening_notes" maxlength="2000" required rows="2" placeholder="Ejemplo: Excel de control, hoja UL semana 37; último folio verificado 361. Alta inicial." class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('opening_notes') }}</textarea>
                </label>
                <label class="mt-4 flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm text-slate-800"><input type="checkbox" name="history_checked" value="1" required class="mt-0.5" /> <span>Verifiqué el último folio y el mercado contra el registro físico o Excel.</span></label>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button class="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">Guardar control de periodo</button>
                    <span class="text-xs text-slate-500">Guardar este control no reserva folios para una requisición.</span>
                </div>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <h2 class="border-b bg-slate-50 p-4 font-bold">Consecutivos por mercado y periodo</h2>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr>@foreach(['NP Rating', 'Mercado', 'Año', 'Periodo', 'Semana operativa inicial', 'Último reservado', 'Siguiente', 'Referencia inicial / ajuste'] as $heading)<th class="px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y">
                @forelse($periods as $period)<tr>
                    <td class="px-4 py-3 font-mono">{{ $period->label_part_number }}</td>
                    <td class="px-4 py-3">{{ $period->serial_standard ?? 'Histórico sin mercado' }}</td>
                    <td class="px-4 py-3">{{ $period->year }}</td>
                    <td class="px-4 py-3">{{ $period->period_label }}</td>
                    <td class="px-4 py-3">{{ $period->week }}</td>
                    <td class="px-4 py-3 font-bold">{{ number_format($period->last_serial_number) }}</td>
                    <td class="px-4 py-3">{{ number_format($period->last_serial_number + 1) }}</td>
                    <td class="max-w-sm px-4 py-3">{{ $period->opening_notes ?? '—' }}</td>
                </tr>@empty<tr><td colspan="8" class="p-8 text-center text-slate-500">No hay controles para estos filtros.</td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="p-4">{{ $periods->links() }}</div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <h2 class="border-b bg-slate-50 p-4 font-bold">Historial de rangos reservados</h2>
        <div class="overflow-x-auto"><table class="w-full min-w-[1150px] text-left text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr>@foreach(['Rango / Req.', 'NP Rating / Mercado', 'Modelo / Job', 'Fecha solicitud', 'Periodo serial', 'Control operativo', 'Del / Hasta', 'Evidencia', 'Serial', 'Rating', 'Estado'] as $heading)<th class="px-3 py-3">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y">
                @forelse($ranges as $range)<tr>
                    <td class="px-3 py-3">#{{ $range->id }}<br><a class="text-blue-700 underline" href="{{ route('label_requests.show', $range->label_request_id) }}">Req. #{{ $range->label_request_id }}</a></td>
                    <td class="px-3 py-3">{{ $range->period->label_part_number }}<br>{{ $range->period->serial_standard ?? 'Histórico' }}</td>
                    <td class="px-3 py-3">{{ $range->model ?? $range->labelRequest->model }}<br><span class="text-xs">{{ $range->job_number ?? $range->labelRequest->job_number }}</span></td>
                    <td class="px-3 py-3">{{ $range->labelRequest->request_date->format('d/m/Y') }}</td>
                    <td class="px-3 py-3">{{ $range->period->period_label }} {{ $range->period->year }}</td>
                    <td class="px-3 py-3">{{ $range->labelRequest->control_year }} / Sem. {{ $range->labelRequest->control_week }}</td>
                    <td class="whitespace-nowrap px-3 py-3 font-bold">{{ $range->range_start }} – {{ $range->range_end }}</td>
                    <td class="px-3 py-3">{{ $range->evidence_folio ?? '—' }}</td>
                    @foreach(['serial', 'rating'] as $type)
                        <td class="px-3 py-3">
                            @forelse($range->tasks->where('label_request_id', $range->label_request_id)->where('label_type', $type) as $task)
                                <div>{{ $task->printed_by_name ?? ($task->assignee?->name ? 'Asignada: '.$task->assignee->name : 'Sin asignar') }}</div>
                                <div class="text-xs text-slate-500">{{ $task->printedShift?->code }} {{ $task->work_date?->format('d/m/Y') }}</div>
                            @empty — @endforelse
                        </td>
                    @endforeach
                    <td class="px-3 py-3">{{ ['reserved'=>'Reservado', 'completed'=>'Terminado', 'cancelled'=>'Cancelado'][$range->status] ?? $range->status }}</td>
                </tr>@empty<tr><td colspan="11" class="p-8 text-center text-slate-500">No hay rangos reservados para estos filtros.</td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="p-4">{{ $ranges->links() }}</div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="font-bold">Reimpresiones con originales físicos</h2>
        <p class="mt-1 text-sm text-slate-600">Reutilizan los folios originales, imprimen una copia adicional de evidencia y no avanzan el consecutivo.</p>
        <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm">
            <thead><tr>@foreach(['Requisición', 'Tipo / NP', 'Origen', 'Periodo original', 'Folios reimpresos', 'Evidencia', 'Imprimió / Turno', 'Estado'] as $heading)<th class="p-2">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y">@forelse($reprints as $task)<tr>
                <td class="p-2"><a class="text-blue-700 underline" href="{{ route('label_requests.show', $task->label_request_id) }}">#{{ $task->label_request_id }}</a></td>
                <td class="p-2">{{ ucfirst($task->label_type) }} · {{ $task->part_number }}</td>
                <td class="p-2">{{ $task->serial_range_id ? '#'.$task->serial_range_id : $task->original_reference }}</td>
                <td class="p-2">{{ $task->serial_period_type ? \App\Support\SerialPeriods::describe($task->serial_period_type, $task->serial_period_number) : 'Periodo histórico' }} {{ $task->serial_period_year }}</td>
                <td class="p-2">{{ $task->folio_start }} – {{ $task->folio_end }}</td>
                <td class="p-2">1 copia · Folio {{ $task->evidence_folio }}</td>
                <td class="p-2">{{ $task->printed_by_name ?? 'Pendiente' }} · {{ $task->printedShift?->code }}</td>
                <td class="p-2">{{ ['pending'=>'Pendiente', 'completed'=>'Terminado', 'cancelled'=>'Cancelado'][$task->status] ?? $task->status }}</td>
            </tr>@empty<tr><td colspan="8" class="p-4 text-slate-500">Sin reimpresiones.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $reprints->links() }}
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const part = document.getElementById('period-part');
    const market = document.getElementById('period-market');
    const year = document.getElementById('period-year');
    const period = document.getElementById('period-number');
    const lastFolio = document.getElementById('period-last-folio');
    const operationalWeek = document.getElementById('period-operational-week');
    const marketHint = document.getElementById('period-market-hint');
    const periodLabel = document.getElementById('period-number-label');
    const periodHelp = document.getElementById('period-number-help');
    const previewHeading = document.getElementById('period-preview-heading');
    const previewCopy = document.getElementById('period-preview-copy');
    const ratingOptions = Array.from(document.querySelectorAll('#rating-part-options option'));
    const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const numberFormat = new Intl.NumberFormat('es-MX');

    function updateGuide() {
        const selectedMarket = market.value;
        const isWeek = selectedMarket === 'UL';
        period.max = selectedMarket && !isWeek ? '12' : '53';
        periodLabel.textContent = isWeek ? 'Semana del último folio en Excel' : (selectedMarket ? 'Mes del último folio en Excel' : 'Semana o mes del último folio');
        periodHelp.textContent = isWeek
            ? 'Escribe la semana ISO del folio (1–53), no la semana de la nueva requisición.'
            : (selectedMarket ? 'Escribe el mes del folio (1–12), no la semana operativa.' : 'UL: semana 1–53. EMEA, ANZ y APJ: mes 1–12.');

        const selectedPart = part.value.trim().toUpperCase();
        const catalogOption = ratingOptions.find(option => option.value.toUpperCase() === selectedPart);
        if (catalogOption) {
            const markets = catalogOption.dataset.markets.split(',');
            marketHint.textContent = markets.includes(selectedMarket)
                ? 'El NP Rating y el mercado coinciden con el catálogo activo.'
                : 'En el catálogo activo, este NP Rating aparece para: ' + markets.join(', ') + '. Verifica el mercado antes de guardar.';
            marketHint.className = 'mt-1 block text-xs font-medium ' + (markets.includes(selectedMarket) ? 'text-emerald-700' : 'text-amber-700');
        } else {
            marketHint.textContent = selectedPart.length >= 5
                ? 'Este NP no aparece en el catálogo activo. Si es histórico, verifica el mercado contra Excel.'
                : 'Confirma que coincida con el mercado del Excel o del catálogo.';
            marketHint.className = 'mt-1 block text-xs font-normal text-slate-500';
        }

        const periodNumber = Number(period.value);
        const lastNumber = Number(lastFolio.value);
        if (!selectedMarket || !selectedPart || !year.value || !period.value || !lastFolio.value
            || !Number.isInteger(periodNumber) || periodNumber < 1 || periodNumber > Number(period.max)
            || !Number.isInteger(lastNumber) || lastNumber < 0) {
            previewHeading.textContent = 'Vista previa del control';
            previewCopy.textContent = 'Selecciona el mercado y captura el periodo y último folio para revisar lo que guardarás.';
            return;
        }

        const serialPeriod = isWeek ? 'semana ' + periodNumber : months[periodNumber - 1];
        const nextNumber = numberFormat.format(lastNumber + 1);
        const operationalReference = operationalWeek.value ? ' La semana operativa inicial ' + operationalWeek.value + ' es solo una referencia.' : '';
        previewHeading.textContent = selectedPart + ' · ' + selectedMarket + ' · ' + serialPeriod + ' de ' + year.value;
        previewCopy.textContent = 'Último folio verificado de este periodo: ' + numberFormat.format(lastNumber)
            + '. El siguiente en este mismo periodo sería ' + nextNumber
            + '. Ese número no se traslada al periodo siguiente.' + operationalReference;
    }

    [part, year, period, lastFolio, operationalWeek].forEach(input => input.addEventListener('input', updateGuide));
    market.addEventListener('change', updateGuide);
    updateGuide();
});
</script>
@endpush
