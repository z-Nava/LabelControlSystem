@extends('layouts.app', ['title' => 'Reimpresión / Retrabajo Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Reimpresión / Retrabajo Master</h1>
            <p class="text-slate-600 mt-1">
                Vista general para localizar folios por job y continuar con reimpresiones.
            </p>
        </div>

        <a href="{{ route('dashboard') }}"
           class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">
            Volver al dashboard
        </a>
    </div>

    <form method="GET" action="{{ route('master_reprints.search') }}" class="mt-6 grid grid-cols-1 md:grid-cols-5 gap-3">
        <div class="md:col-span-4">
            <label class="text-sm text-slate-600">Buscar por Job (Assembly o Packaging)</label>
            <input
                type="text"
                name="job"
                value="{{ $job }}"
                placeholder="Ej. 245901"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
            >
        </div>

        <div class="flex items-end gap-2">
            <button class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500 transition w-full">
                Buscar
            </button>

            <a href="{{ route('master_reprints.search') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">
                Limpiar
            </a>
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
                    <th class="py-3 pr-3">Job Assembly</th>
                    <th class="py-3 pr-3">Job Packaging</th>
                    <th class="py-3 pr-3">Impresiones</th>
                    <th class="py-3 pr-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($masterRequests as $mr)
                    <tr>
                        <td class="py-3 pr-3 font-semibold">#{{ $mr->id }}</td>
                        <td class="py-3 pr-3">{{ optional($mr->request_date)->format('Y-m-d') }}</td>
                        <td class="py-3 pr-3">{{ $mr->line?->code ?? '-' }}</td>
                        <td class="py-3 pr-3">{{ $mr->shift?->code ?? '-' }}</td>
                        <td class="py-3 pr-3">{{ $mr->job_assembly ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $mr->job_packaging ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $mr->print_batches_count }}</td>
                        <td class="py-3 pl-3 text-right whitespace-nowrap">
                            <a href="{{ route('master_requests.reprints.index', $mr->id) }}" class="rounded-lg border px-3 py-1.5 hover:bg-slate-50">
                                Ver historial
                            </a>
                            <a href="{{ route('master_requests.print.create', $mr->id) }}" class="rounded-lg bg-red-600 text-white px-3 py-1.5 hover:bg-red-500 ml-1">
                                Reimprimir
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-500">
                            No se encontraron requisiciones master con ese job.
                        </td>
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
