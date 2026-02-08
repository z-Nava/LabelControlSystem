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

    <form class="mt-5 grid grid-cols-1 md:grid-cols-4 gap-3" method="GET" action="{{ route('oracle_jobs.index') }}">
        <input name="q" value="{{ $filters['q'] ?? '' }}"
               class="rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="Buscar job/assembly/po/desc..." />

        <input name="line" value="{{ $filters['line'] ?? '' }}"
               class="rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="LINE (ej. MEXC010)" />

        <input name="job_status" value="{{ $filters['job_status'] ?? '' }}"
               class="rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="Status (Released...)" />

        <button class="rounded-xl bg-slate-900 text-white px-4 py-2 hover:bg-slate-800 transition">
            Filtrar
        </button>
    </form>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
            <tr class="text-left text-slate-500 border-b">
                <th class="py-3 pr-3">Job</th>
                <th class="py-3 pr-3">Line</th>
                <th class="py-3 pr-3">Status</th>
                <th class="py-3 pr-3">Assembly</th>
                <th class="py-3 pr-3">Qty</th>
                <th class="py-3 pr-3">Remainder</th>
                <th class="py-3 pr-3">Updated</th>
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
                    <td class="py-3 pr-3">{{ $j->quantity_remainder }}</td>
                    <td class="py-3 pr-3">{{ optional($j->last_update_date)->format('Y-m-d H:i') }}</td>
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
