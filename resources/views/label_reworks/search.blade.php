@extends('layouts.app', ['title' => 'Retrabajo de etiquetas'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Retrabajo / Reimpresión de Etiquetas</h1>
            <p class="text-slate-600 mt-1">Busca por job y gestiona retrabajos con historial de impresión.</p>
        </div>

        <a href="{{ route('dashboard') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al dashboard</a>
    </div>

    <form method="GET" action="{{ route('label_reworks.search') }}" class="mt-6 grid grid-cols-1 md:grid-cols-5 gap-3">
        <div class="md:col-span-4">
            <label class="text-sm text-slate-600">Buscar por Job (Assembly o Packaging)</label>
            <input type="text" name="job" value="{{ $job }}" placeholder="Ej. 245901"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
        </div>

        <div class="flex items-end gap-2">
            <button class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500 transition w-full">Buscar</button>
            <a href="{{ route('label_reworks.search') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Limpiar</a>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">Req.</th>
                    <th class="py-3 pr-3">Fecha</th>
                    <th class="py-3 pr-3">Línea</th>
                    <th class="py-3 pr-3">Turno</th>
                    <th class="py-3 pr-3">Job</th>
                    <th class="py-3 pr-3">Seriales de</th>
                    <th class="py-3 pr-3">Seriales hasta</th>
                    <th class="py-3 pr-3">Impresiones</th>
                    <th class="py-3 pr-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($labelRequests as $request)
                    <tr>
                        <td class="py-3 pr-3 font-semibold">#{{ $request->id }}</td>
                        <td class="py-3 pr-3">{{ optional($request->request_date)->format('Y-m-d') }}</td>
                        <td class="py-3 pr-3">{{ $request->line?->code ?? '-' }}</td>
                        <td class="py-3 pr-3">{{ $request->shift?->code ?? '-' }}</td>
                        <td class="py-3 pr-3">{{ $request->job_number ?: '-' }}</td>
                        <td class="py-3 pr-3 font-mono">{{ $request->serial_ranges_min_range_start ?? '-' }}</td>
                        <td class="py-3 pr-3 font-mono">{{ $request->serial_ranges_max_range_end ?? '-' }}</td>
                        <td class="py-3 pr-3">{{ $request->print_batches_count }}</td>
                        <td class="py-3 pl-3 text-right whitespace-nowrap">
                            <a href="{{ route('label_requests.show', $request->id) }}#historial-impresiones" class="rounded-lg border px-3 py-1.5 hover:bg-slate-50">Ver historial</a>
                            <a href="{{ route('label_reworks.show', $request->id) }}" class="rounded-lg bg-red-600 text-white px-3 py-1.5 hover:bg-red-500 ml-1">Reimprimir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-6 text-center text-slate-500">No se encontraron requisiciones de etiquetas para ese job.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $labelRequests->links() }}</div>
</div>
@endsection
