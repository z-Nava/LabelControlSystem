@extends('layouts.app', ['title' => 'Semanas y folios', 'mainClass' => 'max-w-[1600px]'])
@section('content')
<div class="space-y-5">
    @include('label_requests.partials.admin-navigation')
    <header><h1 class="text-2xl font-bold">Control de semanas y folios</h1><p class="mt-1 text-slate-600">Consecutivos compartidos por NP Rating y familia. Los rangos reservados se conservan aunque se cancele una requisición.</p></header>
    @include('label_requests.partials.messages')
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4">
        <label class="text-sm">Año<input type="number" name="year" value="{{ $year }}" min="2000" max="2100" class="mt-1 block w-28 rounded-lg border border-slate-300 px-3 py-2" /></label>
        <label class="text-sm">Semana<input type="number" name="week" value="{{ $filters['week'] ?? '' }}" min="1" max="53" placeholder="Todas" class="mt-1 block w-28 rounded-lg border border-slate-300 px-3 py-2" /></label>
        <label class="text-sm">NP Rating / Familia<input name="search" value="{{ $filters['search'] ?? '' }}" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2" /></label>
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-white">Filtrar</button>
    </form>
    <details class="rounded-xl border border-blue-200 bg-white p-5" @if($errors->any()) open @endif>
        <summary class="cursor-pointer font-semibold text-blue-800">Inicializar semana o registrar un avance verificado en Excel</summary>
        <p class="mt-3 text-sm text-slate-600">La familia es el SKU completo del ensamble de la Job en Master Model Mapping (por ejemplo, 2563-20). Debe coincidir exactamente con el SKU mostrado en la revisión de la requisición. La primera vez indica el último folio utilizado; captura 0 en ese campo solo si no hubo impresiones. Las semanas posteriores de una familia conocida comienzan automáticamente en 1. Un ajuste nunca reduce el consecutivo.</p>
        <form method="POST" action="{{ route('label_requests.weeks.initialize') }}" class="mt-4 space-y-3">
            @csrf
            <div class="grid gap-3 md:grid-cols-5">
                <label class="text-sm">NP Rating<input name="label_part_number" value="{{ old('label_part_number') }}" maxlength="80" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase" /></label>
                <label class="text-sm">Familia (SKU)<input name="folio_family" value="{{ old('folio_family') }}" maxlength="80" placeholder="2563-20" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 uppercase" /></label>
                <label class="text-sm">Año<input type="number" name="year" value="{{ old('year', $year) }}" min="2000" max="2100" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
                <label class="text-sm">Semana<input type="number" name="week" value="{{ old('week', $filters['week'] ?? now()->isoWeek()) }}" min="1" max="53" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
                <label class="text-sm">Último folio utilizado<input type="number" name="last_serial_number" value="{{ old('last_serial_number') }}" min="0" max="4294967294" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
            </div>
            <label class="block text-sm">Referencia y motivo del registro<textarea name="opening_notes" maxlength="2000" required rows="2" placeholder="Archivo / hoja consultada y motivo del ajuste" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('opening_notes') }}</textarea></label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="history_checked" value="1" required /> Verifiqué el último folio y la familia contra el registro físico o Excel.</label>
            <button class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white">Guardar control semanal</button>
        </form>
    </details>
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <h2 class="border-b bg-slate-50 p-4 font-bold">Consecutivo por familia</h2>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr>@foreach(['NP Rating', 'Familia', 'Año', 'Semana', 'Último reservado', 'Siguiente', 'Referencia inicial / ajuste'] as $heading)<th class="px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y">
                @forelse($weeks as $week)<tr>
                    <td class="px-4 py-3 font-mono">{{ $week->label_part_number }}</td><td class="px-4 py-3">{{ $week->folio_family ?? 'Histórico sin familia' }}</td><td class="px-4 py-3">{{ $week->year }}</td><td class="px-4 py-3">{{ $week->week }}</td>
                    <td class="px-4 py-3 font-bold">{{ number_format($week->last_serial_number) }}</td><td class="px-4 py-3">{{ number_format($week->last_serial_number + 1) }}</td><td class="max-w-sm px-4 py-3">{{ $week->opening_notes ?? '—' }}</td>
                </tr>@empty<tr><td colspan="7" class="p-8 text-center text-slate-500">No hay controles para estos filtros.</td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="p-4">{{ $weeks->links() }}</div>
    </section>
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <h2 class="border-b bg-slate-50 p-4 font-bold">Historial de rangos reservados</h2>
        <div class="overflow-x-auto"><table class="w-full min-w-[1100px] text-left text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr>@foreach(['Rango / Req.', 'NP Rating / Familia', 'Modelo / Job', 'Fecha solicitud', 'Año / Semana', 'Del / Hasta', 'Evidencia', 'Serial', 'Rating', 'Estado'] as $heading)<th class="px-3 py-3">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y">
                @forelse($ranges as $range)<tr>
                    <td class="px-3 py-3">#{{ $range->id }}<br><a class="text-blue-700 underline" href="{{ route('label_requests.show', $range->label_request_id) }}">Req. #{{ $range->label_request_id }}</a></td>
                    <td class="px-3 py-3">{{ $range->week->label_part_number }}<br>{{ $range->week->folio_family ?? 'Histórico' }}</td>
                    <td class="px-3 py-3">{{ $range->model ?? $range->labelRequest->model }}<br><span class="text-xs">{{ $range->job_number ?? $range->labelRequest->job_number }}</span></td>
                    <td class="px-3 py-3">{{ $range->labelRequest->request_date->format('d/m/Y') }}</td><td class="px-3 py-3">{{ $range->week->year }} / {{ $range->week->week }}</td>
                    <td class="whitespace-nowrap px-3 py-3 font-bold">{{ $range->range_start }} – {{ $range->range_end }}</td><td class="px-3 py-3">{{ $range->evidence_folio ?? '—' }}</td>
                    @foreach(['serial', 'rating'] as $type)
                        <td class="px-3 py-3">
                            @forelse($range->tasks->where('label_request_id', $range->label_request_id)->where('label_type', $type) as $task)
                                <div>{{ $task->printed_by_name ?? ($task->assignee?->name ? 'Asignada: '.$task->assignee->name : 'Sin asignar') }}</div>
                                <div class="text-xs text-slate-500">{{ $task->printedShift?->code }} {{ $task->work_date?->format('d/m/Y') }}</div>
                            @empty — @endforelse
                        </td>
                    @endforeach
                    <td class="px-3 py-3">{{ ['reserved'=>'Reservado', 'completed'=>'Terminado', 'cancelled'=>'Cancelado'][$range->status] ?? $range->status }}</td>
                </tr>@empty<tr><td colspan="10" class="p-8 text-center text-slate-500">No hay rangos reservados para estos filtros.</td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="p-4">{{ $ranges->links() }}</div>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="font-bold">Reimpresiones con originales físicos</h2>
        <p class="mt-1 text-sm text-slate-600">Estas operaciones reutilizan folios y no avanzan el consecutivo.</p>
        <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm">
            <thead><tr>@foreach(['Requisición', 'Tipo / NP', 'Rango original', 'Folios reimpresos', 'Imprimió / Turno', 'Estado'] as $heading)<th class="p-2">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y">@forelse($reprints as $task)<tr>
                <td class="p-2"><a class="text-blue-700 underline" href="{{ route('label_requests.show', $task->label_request_id) }}">#{{ $task->label_request_id }}</a></td>
                <td class="p-2">{{ ucfirst($task->label_type) }} · {{ $task->part_number }}</td><td class="p-2">{{ $task->serial_range_id ? '#'.$task->serial_range_id : $task->original_reference }}<br>{{ $task->control_year }}/{{ $task->control_week }}</td>
                <td class="p-2">{{ $task->folio_start }} – {{ $task->folio_end }}</td><td class="p-2">{{ $task->printed_by_name ?? 'Pendiente' }} · {{ $task->printedShift?->code }}</td>
                <td class="p-2">{{ ['pending'=>'Pendiente', 'completed'=>'Terminado', 'cancelled'=>'Cancelado'][$task->status] ?? $task->status }}</td>
            </tr>@empty<tr><td colspan="6" class="p-4 text-slate-500">Sin reimpresiones.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $reprints->links() }}
    </section>
</div>
@endsection
