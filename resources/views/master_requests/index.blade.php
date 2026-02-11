@extends('layouts.app', ['title' => 'Requisiciones Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Requisiciones Master</h1>
            <p class="text-slate-600 mt-1">Consulta requisiciones pendientes para continuar impresión más tarde.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Dashboard</a>
            <a href="{{ route('master_requests.create') }}" class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500 transition">Nueva requisición</a>
        </div>
    </div>

    <form method="GET" action="{{ route('master_requests.index') }}" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="text-sm text-slate-600">Estado</label>
            <select name="status" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                <option value="pending" @selected(($filters['status'] ?? 'pending') === 'pending')>Pendientes (requested/in_progress)</option>
                <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Completadas</option>
                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Canceladas</option>
                <option value="all" @selected(($filters['status'] ?? '') === 'all')>Todas</option>
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="text-sm text-slate-600">Buscar</label>
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="# requisición, líder, job, PO"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
        </div>

        <div class="flex items-end gap-2">
            <button class="w-full rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold hover:bg-slate-800 transition">Filtrar</button>
            <a href="{{ route('master_requests.index') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Limpiar</a>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">Req</th>
                    <th class="py-3 pr-3">Fecha</th>
                    <th class="py-3 pr-3">Línea / Turno</th>
                    <th class="py-3 pr-3">Líder</th>
                    <th class="py-3 pr-3">Job</th>
                    <th class="py-3 pr-3">Avance</th>
                    <th class="py-3 pr-3">Estado</th>
                    <th class="py-3 pr-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($masterRequests as $mr)
                    @php
                        $total = (int) ($mr->total_folios ?? 0);
                        $printed = (int) ($mr->printed_folios ?? 0);
                    @endphp
                    <tr>
                        <td class="py-3 pr-3 font-semibold">#{{ $mr->id }}</td>
                        <td class="py-3 pr-3">{{ $mr->request_date?->format('Y-m-d') }}</td>
                        <td class="py-3 pr-3">{{ $mr->line?->code }} · {{ $mr->shift?->code }}</td>
                        <td class="py-3 pr-3">{{ $mr->leader_name }}</td>
                        <td class="py-3 pr-3">{{ $mr->job_assembly ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $printed }}/{{ $total }}</td>
                        <td class="py-3 pr-3">
                            <span class="rounded-full px-2 py-1 text-xs
                                {{ $mr->status === 'completed' ? 'bg-green-100 text-green-800' : ($mr->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ $mr->status }}
                            </span>
                        </td>
                        <td class="py-3 pr-3 whitespace-nowrap">
                            <a href="{{ route('master_requests.show', $mr->id) }}" class="rounded-lg border px-3 py-1.5 hover:bg-slate-50">Ver</a>
                            <a href="{{ route('master_requests.print.create', $mr->id) }}" class="rounded-lg bg-red-600 text-white px-3 py-1.5 hover:bg-red-500">Imprimir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-500">No hay requisiciones con ese filtro.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $masterRequests->links() }}
    </div>
</div>
@endsection
