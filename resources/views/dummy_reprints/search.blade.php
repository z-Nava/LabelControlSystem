@extends('layouts.app', ['title' => 'Reimpresión de Dummy QR'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Reimpresión de Dummy QR</h1>
            <p class="text-slate-600 mt-1">Consulta requisiciones con impresión inicial, filtra por job/tipo y abre selección de dummys por consecutivo.</p>
        </div>

        <a href="{{ route('dashboard') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al dashboard</a>
    </div>

    <form method="GET" action="{{ route('dummy_reprints.search') }}" class="mt-6 grid grid-cols-1 md:grid-cols-6 gap-3">
        <div class="md:col-span-3">
            <label class="text-sm text-slate-600">Buscar por Job</label>
            <input type="text" name="job" value="{{ $job }}" placeholder="Ej. 245901"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
        </div>

        <div class="md:col-span-2">
            <label class="text-sm text-slate-600">Tipo de dummy</label>
            <select name="request_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                <option value="">Todos</option>
                <option value="first_time" @selected($requestType === 'first_time')>RMT Dummy QR</option>
                <option value="rework" @selected($requestType === 'rework')>RW Dummy QR</option>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500 transition w-full">Buscar</button>
            <a href="{{ route('dummy_reprints.search') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Limpiar</a>
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
                    <th class="py-3 pr-3">Tipo dummy</th>
                    <th class="py-3 pr-3">Consecutivo desde</th>
                    <th class="py-3 pr-3">Consecutivo hasta</th>
                    <th class="py-3 pr-3">Historial</th>
                    <th class="py-3 pr-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($dummyRequests as $request)
                    @php
                        $typeLabel = $request->request_type === 'rework' ? 'RW Dummy QR' : 'RMT Dummy QR';
                    @endphp
                    <tr>
                        <td class="py-3 pr-3 font-semibold">#{{ $request->id }}</td>
                        <td class="py-3 pr-3">{{ optional($request->request_date)->format('Y-m-d') }}</td>
                        <td class="py-3 pr-3">{{ $request->line?->code ?? '-' }}</td>
                        <td class="py-3 pr-3">{{ $request->shift?->code ?? '-' }}</td>
                        <td class="py-3 pr-3">{{ $request->job_number ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $typeLabel }}</td>
                        <td class="py-3 pr-3 font-mono">{{ str_pad((string) $request->range_from, 10, '0', STR_PAD_LEFT) }}</td>
                        <td class="py-3 pr-3 font-mono">{{ str_pad((string) $request->range_to, 10, '0', STR_PAD_LEFT) }}</td>
                        <td class="py-3 pr-3">{{ $request->print_batches_count }}</td>
                        <td class="py-3 pl-3 text-right whitespace-nowrap">
                            <a href="{{ route('dummy_requests.show', $request) }}#historial-impresiones" class="rounded-lg border px-3 py-1.5 hover:bg-slate-50">Ver historial</a>
                            <a href="{{ route('dummy_reprints.show', $request) }}" class="rounded-lg bg-red-600 text-white px-3 py-1.5 hover:bg-red-500 ml-1">Seleccionar dummys</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-6 text-center text-slate-500">No se encontraron requisiciones con impresión inicial para ese filtro.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $dummyRequests->links() }}</div>
</div>
@endsection
