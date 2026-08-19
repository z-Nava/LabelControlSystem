@extends('layouts.app', ['title' => 'Requisiciones de Etiquetas'])

@section('content')
@php
    $statusOptions = [
        'active' => 'Pendientes (todas)',
        'requested' => 'Pendiente',
        'in_progress' => 'Requisición impresa',
        'attended' => 'Atendida',
        'completed' => 'Entregada',
        'cancelled' => 'Cancelada',
        'all' => 'Todas',
    ];
@endphp

<div class="rounded-2xl bg-white p-6 shadow">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Requisiciones de etiquetas pendientes</h1>
            <p class="mt-1 text-slate-600">La vista inicia con Pendientes, Impresas y Atendidas. Usa los filtros para consultar el historial.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Dashboard</a>
            <a href="{{ route('label_requests.create') }}" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">Nueva requisición</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('label_requests.index') }}" class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-6">
        <div>
            <label class="text-sm text-slate-600">Fecha desde</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>
        <div>
            <label class="text-sm text-slate-600">Fecha hasta</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>
        <div>
            <label class="text-sm text-slate-600">Línea</label>
            <select name="line_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                <option value="">Todas</option>
                @foreach($lines as $line)
                    <option value="{{ $line->id }}" @selected((string) $filters['line_id'] === (string) $line->id)>{{ $line->code }} · {{ $line->line_type }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm text-slate-600">Turno</label>
            <select name="shift_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                <option value="">Todos</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}" @selected((string) $filters['shift_id'] === (string) $shift->id)>{{ $shift->code }} · {{ $shift->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm text-slate-600">Estatus</label>
            <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                @foreach($statusOptions as $status => $label)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm text-slate-600">Job / NP Serial o Rating</label>
            <input type="text" name="sku_np" value="{{ $filters['sku_np'] }}" placeholder="Job o número de parte" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div class="flex gap-2 md:col-span-6">
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Aplicar filtros</button>
            <a href="{{ route('label_requests.index') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Limpiar</a>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full min-w-[1100px] text-sm">
            <thead class="bg-slate-50">
                <tr class="border-b border-slate-200 text-left text-slate-500">
                    <th class="px-4 py-3">Requisición</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Línea / Turno</th>
                    <th class="px-4 py-3">Job / Modelo</th>
                    <th class="px-4 py-3">Tipos</th>
                    <th class="px-4 py-3">Cantidad / Folios</th>
                    <th class="px-4 py-3">Estatus</th>
                    <th class="px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($labelRequests as $request)
                    <tr class="align-top hover:bg-slate-50">
                        <td class="px-4 py-3 font-semibold">#{{ $request->id }}</td>
                        <td class="px-4 py-3">{{ $request->request_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">{{ $request->line?->code }} · {{ $request->shift?->code }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $request->job_number ?: 'Sin Job' }}</div>
                            <div class="text-xs text-slate-500">{{ $request->model ?: 'Sin modelo' }}</div>
                            @if($request->include_serial)
                                @foreach($request->requestedSerialPartNumbers() as $serialPartNumber)
                                    <div class="mt-1 text-xs text-slate-500">NP Serial: {{ $serialPartNumber }}</div>
                                @endforeach
                            @endif
                            @if($request->include_rating)
                                @foreach($request->requestedRatingPartNumbers() as $ratingPartNumber)
                                    <div class="mt-1 text-xs text-slate-500">NP Rating: {{ $ratingPartNumber }}</div>
                                @endforeach
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ implode(' + ', $request->requestedLabelTypes()) ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">General: {{ number_format($request->quantity_requested) }}</div>
                            @if($request->include_shipping)
                                <div class="text-xs text-slate-500">Shipping: {{ number_format($request->shipping_quantity ?? $request->quantity_requested) }}</div>
                            @endif
                            <div class="text-xs text-slate-500">
                                {{ $request->folio_start !== null ? $request->folio_start.' – '.$request->folio_end : 'Sin folios' }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full border px-2 py-1 text-xs font-semibold {{ $request->status_badge_classes }}">{{ $request->status_label }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="mb-2">
                                <a href="{{ route('label_requests.show', $request) }}" class="rounded-lg border px-3 py-1.5 text-xs font-medium hover:bg-white">Ver detalle</a>
                            </div>
                            @include('label_requests.partials.workflow-actions', ['labelRequest' => $request, 'compact' => true])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">No hay requisiciones con los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $labelRequests->links() }}</div>
</div>
@endsection
