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

    <details class="rounded-xl border border-blue-200 bg-white p-5" @if($errors->any()) open @endif>
        <summary class="cursor-pointer font-semibold text-blue-800">Inicializar un periodo o registrar un avance verificado</summary>
        <p class="mt-3 text-sm text-slate-600">La primera vez de un NP Rating y mercado registra el último folio utilizado. Captura 0 solo si ese periodo no tuvo impresiones. Los periodos posteriores comienzan automáticamente en 1 y ningún ajuste puede reducir el consecutivo.</p>
        <form method="POST" action="{{ route('label_requests.weeks.initialize') }}" class="mt-4 space-y-3">
            @csrf
            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                <label class="text-sm">NP Rating
                    <input name="label_part_number" value="{{ old('label_part_number') }}" maxlength="80" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase" />
                </label>
                <label class="text-sm">Mercado
                    <select name="serial_standard" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">Selecciona...</option>
                        @foreach($markets as $market)<option value="{{ $market }}" @selected(old('serial_standard') === $market)>{{ $market }}</option>@endforeach
                    </select>
                </label>
                <label class="text-sm">Año del periodo
                    <input type="number" name="year" value="{{ old('year', $year) }}" min="2000" max="2100" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                </label>
                <label class="text-sm">Semana o mes
                    <input type="number" name="period_number" value="{{ old('period_number', $filters['period_number'] ?? '') }}" min="1" max="53" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                    <span class="mt-1 block text-xs text-slate-500">UL: 1–53. Otros mercados: 1–12.</span>
                </label>
                <label class="text-sm">Semana operativa
                    <input type="number" name="control_week" value="{{ old('control_week', now(config('app.display_timezone'))->isoWeek()) }}" min="1" max="53" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                </label>
                <label class="text-sm">Último folio utilizado
                    <input type="number" name="last_serial_number" value="{{ old('last_serial_number') }}" min="0" max="4294967294" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
                </label>
            </div>
            <label class="block text-sm">Referencia y motivo del registro
                <textarea name="opening_notes" maxlength="2000" required rows="2" placeholder="Archivo / hoja consultada y motivo del ajuste" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('opening_notes') }}</textarea>
            </label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="history_checked" value="1" required /> Verifiqué el último folio y el mercado contra el registro físico o Excel.</label>
            <button class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Guardar control de periodo</button>
        </form>
    </details>

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
