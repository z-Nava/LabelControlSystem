@extends('layouts.app', ['title' => 'Concentrado JOB', 'mainClass' => 'max-w-[1600px]'])
@section('content')
<div class="space-y-5">
    @include('label_requests.partials.admin-navigation')
    <header><h1 class="text-2xl font-bold">Concentrado de JOB y PO</h1><p class="mt-1 text-slate-600">Se registra al terminar todas las etiquetas solicitadas, con la fecha y turno que completa el trabajo. Las cantidades de producción no incluyen evidencia.</p></header>
    <form method="GET" class="grid items-end gap-3 rounded-xl bg-white p-4 md:grid-cols-4 xl:grid-cols-7">
        <label class="text-sm">Desde<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
        <label class="text-sm">Hasta<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
        <label class="text-sm">Turno<select name="shift_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><option value="">Todos</option>@foreach($shifts as $shift)<option value="{{ $shift->id }}" @selected(($filters['shift_id'] ?? '') == $shift->id)>{{ $shift->code }}</option>@endforeach</select></label>
        <label class="text-sm">Línea<select name="line_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><option value="">Todas</option>@foreach($lines as $line)<option value="{{ $line->id }}" @selected(($filters['line_id'] ?? '') == $line->id)>{{ $line->code }}</option>@endforeach</select></label>
        <label class="text-sm">Clasificación<select name="job_status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><option value="">Todas</option>@foreach(\App\Models\LabelRequest::JOB_STATUSES as $code => $label)<option value="{{ $code }}" @selected(($filters['job_status'] ?? '') === $code)>{{ $code }} · {{ $label }}</option>@endforeach</select></label>
        <label class="text-sm">Job / PO / Modelo<input name="search" value="{{ $filters['search'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-white">Filtrar</button>
    </form>
    @include('label_requests.partials.messages')
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto"><table class="w-full min-w-[1100px] text-left text-sm">
            <thead class="bg-slate-900 text-white"><tr>@foreach(['Req.', 'JOB', 'PO', 'Cantidad', 'Etiquetas Shipping', 'Modelo', 'Fecha', 'Status', 'Línea', 'Turno'] as $heading)<th class="px-3 py-4">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y">@forelse($entries as $entry)<tr>
                <td class="px-3 py-3"><a href="{{ route('label_requests.show', $entry->label_request_id) }}" class="font-semibold text-blue-700 underline">#{{ $entry->label_request_id }}</a>@if($entry->labelRequest->isOriginalReprint())<div class="text-xs text-amber-800">Reimpresión</div>@endif</td>
                <td class="px-3 py-3 font-mono">{{ $entry->job_number }}</td><td class="px-3 py-3">{{ $entry->po_number ?? '—' }}</td>
                <td class="px-3 py-3">{{ $entry->quantity !== null ? number_format($entry->quantity) : 'No aplica' }}</td>
                <td class="px-3 py-3">{{ $entry->shipping_quantity !== null ? number_format($entry->shipping_quantity) : '—' }}
                    @foreach($entry->shared_shipping ?? [] as $shared)<div class="mt-1 text-xs text-amber-800">{{ number_format($shared['quantity']) }} compartidas · Tarea #{{ $shared['task_id'] }}<br>{{ $shared['part_number'] }} · PO {{ $shared['po_number'] ?? '—' }}</div>@endforeach
                </td>
                <td class="px-3 py-3">{{ $entry->model }}</td><td class="px-3 py-3">{{ $entry->work_date->format('d/m/Y') }}</td>
                <td class="px-3 py-3"><span class="rounded-md bg-sky-100 px-2 py-1 font-bold text-sky-900" title="{{ \App\Models\LabelRequest::JOB_STATUSES[$entry->labelRequest->job_status] }}">{{ $entry->labelRequest->job_status }}</span></td>
                <td class="px-3 py-3">{{ $entry->line?->code }}</td><td class="px-3 py-3 font-semibold">{{ $entry->shift?->code }}</td>
            </tr>@empty<tr><td colspan="10" class="p-10 text-center text-slate-500">Aún no hay trabajos terminados para estos filtros.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="p-4">{{ $entries->links() }}</div>
    </div>
    <p class="text-sm text-slate-500">Shipping compartido identifica una sola tarea para varias Jobs; su cantidad no debe sumarse una vez por cada Job.</p>
</div>
@endsection

