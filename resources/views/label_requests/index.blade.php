@extends('layouts.app', ['title' => 'Requisiciones de Etiquetas'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Listado de requisiciones de etiquetas</h1>
            <p class="text-slate-600 mt-1">Filtra por fecha, línea, turno, status o SKU/NP.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Dashboard</a>
            <a href="{{ route('label_requests.create') }}" class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500 transition">Nueva requisición</a>
        </div>
    </div>

    <form method="GET" action="{{ route('label_requests.index') }}" class="mt-6 grid grid-cols-1 md:grid-cols-6 gap-3">
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
            <label class="text-sm text-slate-600">Status</label>
            <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                <option value="">Todos</option>
                @foreach(['requested','in_progress','completed','cancelled'] as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm text-slate-600">SKU / NP</label>
            <input type="text" name="sku_np" value="{{ $filters['sku_np'] }}" placeholder="SKU o Label PN" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div class="md:col-span-6 flex gap-2">
            <button class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm hover:bg-slate-800">Aplicar filtros</button>
            <a href="{{ route('label_requests.index') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Limpiar</a>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-slate-500 border-b border-slate-200">
                    <th class="py-3 px-4">Fecha</th>
                    <th class="py-3 px-4">Línea / Turno</th>
                    <th class="py-3 px-4">SKU/NP</th>
                    <th class="py-3 px-4">Qty</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($labelRequests as $request)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">{{ $request->request_date?->format('Y-m-d') }}</td>
                        <td class="py-3 px-4">{{ $request->line?->code }} · {{ $request->shift?->code }}</td>
                        <td class="py-3 px-4">
                            <div class="font-semibold">{{ $request->label_part_number }}</div>
                            <div class="text-xs text-slate-500">{{ $request->job_number ?: '-' }}</div>
                        </td>
                        <td class="py-3 px-4">{{ number_format($request->quantity_requested) }}</td>
                        <td class="py-3 px-4">
                            <span class="rounded-full px-2 py-1 text-xs {{ $request->status === 'completed' ? 'bg-green-100 text-green-800' : ($request->status === 'cancelled' ? 'bg-slate-200 text-slate-700' : ($request->status === 'in_progress' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800')) }}">{{ $request->status }}</span>
                        </td>
                        <td class="py-3 px-4 space-x-2">
                            <a href="{{ route('label_requests.show', $request) }}" class="rounded-lg border px-3 py-1.5 hover:bg-slate-50">Ver detalle</a>
                            @if(in_array($request->status, ['requested', 'in_progress'], true))
                                <form method="POST" action="{{ route('label_requests.cancel', $request) }}" class="inline">
                                    @csrf
                                    <button class="rounded-lg border border-red-200 text-red-700 px-3 py-1.5 hover:bg-red-50" onclick="return confirm('¿Cancelar requisición?')">Cancelar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay requisiciones con los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $labelRequests->links() }}</div>
</div>
@endsection
