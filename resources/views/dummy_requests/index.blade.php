@extends('layouts.app', ['title' => 'Requisiciones Dummy QR'])

@section('content')
@php
    $statusLabels = [
        'requested' => 'Solicitado',
        'in_progress' => 'En Progreso',
        'completed' => 'Completado',
        'cancelled' => 'Cancelada',
    ];
@endphp
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Listado de requisiciones Dummy QR</h1>
            <p class="text-slate-600 mt-1">Consulta por Job, tipo, fecha, estatus y rango de consecutivos.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Dashboard</a>
            <a href="{{ route('dummy_requests.create') }}" class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500 transition">Nueva requisición Dummy QR</a>
        </div>
    </div>

    <form method="GET" action="{{ route('dummy_requests.index') }}" class="mt-6 grid grid-cols-1 md:grid-cols-7 gap-3">
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
            <label class="text-sm text-slate-600">Tipo</label>
            <select name="request_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                <option value="">Todos</option>
                <option value="first_time" @selected($filters['request_type'] === 'first_time')>Primera vez (RMT)</option>
                <option value="rework" @selected($filters['request_type'] === 'rework')>Retrabajo (RW)</option>
            </select>
        </div>
        <div>
            <label class="text-sm text-slate-600">Estatus</label>
            <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                <option value="">Todos</option>
                @foreach($statusLabels as $status => $statusLabel)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabel }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm text-slate-600">Job</label>
            <input type="text" name="job_number" value="{{ $filters['job_number'] }}" placeholder="Ej: 9933" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div class="md:col-span-7 flex gap-2">
            <button class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm hover:bg-slate-800">Aplicar filtros</button>
            <a href="{{ route('dummy_requests.index') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Limpiar</a>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-slate-500 border-b border-slate-200">
                    <th class="py-3 px-4">Fecha</th>
                    <th class="py-3 px-4">Línea / Turno</th>
                    <th class="py-3 px-4">Job / FG</th>
                    <th class="py-3 px-4">Tipo</th>
                    <th class="py-3 px-4">Estatus</th>
                    <th class="py-3 px-4">Rango consecutivo</th>
                    <th class="py-3 px-4">Qty</th>
                    <th class="py-3 px-4">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($dummyRequests as $request)
                    @php
                        $printedQty = (int) ($request->printed_qty ?? 0);
                        $typeLabel = $request->request_type === 'rework' ? 'RW Dummy QR' : 'RMT Dummy QR';
                        $statusLabel = $statusLabels[$request->status] ?? $request->status;
                        $statusClasses = match ($request->status) {
                            'completed' => 'bg-green-100 text-green-800',
                            'in_progress' => 'bg-amber-100 text-amber-800',
                            'requested' => 'bg-blue-100 text-blue-800',
                            'cancelled' => 'bg-slate-200 text-slate-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">{{ $request->request_date?->format('Y-m-d') }}</td>
                        <td class="py-3 px-4">{{ $request->line?->code }} · {{ $request->shift?->code }}</td>
                        <td class="py-3 px-4">
                            <div class="font-semibold">{{ $request->job_number }}</div>
                            <div class="text-xs text-slate-500">FG: {{ $request->fg_code }}</div>
                        </td>
                        <td class="py-3 px-4">{{ $typeLabel }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-mono text-xs">
                            {{ str_pad((string) $request->range_from, 10, '0', STR_PAD_LEFT) }}
                            -
                            {{ str_pad((string) $request->range_to, 10, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="py-3 px-4">
                            <div>{{ number_format($request->quantity_requested) }}</div>
                            <div class="text-xs text-slate-500">Impreso: {{ number_format($printedQty) }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ route('dummy_requests.show', $request) }}" class="rounded-lg border px-3 py-1.5 hover:bg-slate-50">Ver detalle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500">No hay requisiciones Dummy QR con los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $dummyRequests->links() }}</div>
</div>
@endsection
