@extends('layouts.app', ['title' => 'Oracle Jobs'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Oracle Jobs</h1>
            <p class="text-slate-600 mt-1">Fuente central importada desde Excel.</p>
        </div>

        <a href="{{ route('oracle_jobs.import_view') }}"
           class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">
            Importar Excel
        </a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4" method="GET" action="{{ route('oracle_jobs.index') }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Filtros de búsqueda</h2>
                <p class="mt-1 text-xs text-slate-600">Filtra cada columna por separado. Puedes combinar varios campos.</p>
            </div>
            <a href="{{ route('oracle_jobs.index') }}" class="text-xs font-medium text-slate-600 underline underline-offset-2 hover:text-slate-900">Limpiar filtros</a>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="block">
                <span class="text-xs font-medium text-slate-700">Job</span>
                <input name="job_number" value="{{ $filters['job_number'] ?? '' }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                       placeholder="Número de job" />
            </label>

            <label class="block">
                <span class="text-xs font-medium text-slate-700">Line</span>
                <input name="line" value="{{ $filters['line'] ?? '' }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                       placeholder="Línea de producción" />
            </label>

            <label class="block">
                <span class="text-xs font-medium text-slate-700">Status</span>
                <input name="job_status" value="{{ $filters['job_status'] ?? '' }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                       placeholder="Estado del job" />
            </label>

            <label class="block">
                <span class="text-xs font-medium text-slate-700">Assembly</span>
                <input name="assembly" value="{{ $filters['assembly'] ?? '' }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                       placeholder="Assembly" />
            </label>

            <label class="block">
                <span class="text-xs font-medium text-slate-700">Qty</span>
                <input type="number" step="1" name="job_qty" value="{{ $filters['job_qty'] ?? '' }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                       placeholder="Cantidad exacta" />
            </label>

            <label class="block">
                <span class="text-xs font-medium text-slate-700">SHIP_TO</span>
                <input name="ship_to" value="{{ $filters['ship_to'] ?? '' }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                       placeholder="Destino" />
            </label>

            <label class="block">
                <span class="text-xs font-medium text-slate-700">SHIP_CODE</span>
                <input name="ship_code" value="{{ $filters['ship_code'] ?? '' }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                       placeholder="Código de destino" />
            </label>

            <label class="block">
                <span class="text-xs font-medium text-slate-700">PO</span>
                <input name="ttl_cust_po" value="{{ $filters['ttl_cust_po'] ?? '' }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                       placeholder="Orden de compra" />
            </label>
        </div>

        <div class="mt-4 flex justify-end">
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition">
                Aplicar filtros
            </button>
        </div>
    </form>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full min-w-[960px] text-sm">
            <thead>
            <tr class="text-left text-slate-500 border-b">
                <th class="py-3 pr-3">Job</th>
                <th class="py-3 pr-3">Line</th>
                <th class="py-3 pr-3">Status</th>
                <th class="py-3 pr-3">Assembly</th>
                <th class="py-3 pr-3">Qty</th>
                <th class="py-3 pr-3">SHIP_TO</th>
                <th class="py-3 pr-3">SHIP_CODE</th>
                <th class="py-3 pr-3">PO</th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @forelse($jobs as $j)
                <tr>
                    <td class="py-3 pr-3 font-semibold text-slate-900">{{ $j->job_number }}</td>
                    <td class="py-3 pr-3">{{ $j->line }}</td>
                    <td class="py-3 pr-3">{{ $j->job_status }}</td>
                    <td class="py-3 pr-3">{{ $j->assembly }}</td>
                    <td class="py-3 pr-3">{{ $j->job_qty }}</td>
                    <td class="py-3 pr-3"><div class="min-w-40 max-w-64 break-words">{{ $j->ship_to }}</div></td>
                    <td class="py-3 pr-3"><div class="min-w-28 max-w-40 break-all">{{ $j->ship_code }}</div></td>
                    <td class="py-3 pr-3">{{ $j->ttl_cust_po }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-8 text-center text-slate-500">
                        No hay datos. Importa un Excel para comenzar.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $jobs->links() }}
    </div>
</div>
@endsection
