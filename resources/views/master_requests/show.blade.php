@extends('layouts.app', ['title' => 'Detalle Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Requisición Master #{{ $mr->id }}</h1>
            <p class="text-slate-600 mt-1">
                {{ $mr->line?->code }} · Turno {{ $mr->shift?->code }} · {{ $mr->request_date?->format('Y-m-d') }}
            </p>
        </div>

        <a href="{{ route('master_requests.index')}}"
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            ← Volver al listado
        </a>
    </div>

    @php
        $totalFolios = $mr->folios->count();
        $printedFolios = $mr->folios->where('status', 'printed')->count();
        $pendingFolios = $totalFolios - $printedFolios;
    @endphp

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-red-700">Acción principal</div>
            <h2 class="mt-1 text-base font-semibold text-slate-900">Impresión inicial</h2>
            <p class="mt-1 text-sm text-slate-600">Genera un lote y continúa en la pantalla de impresión.</p>

            <a href="{{ route('master_requests.print.create', $mr->id) }}"
                class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">
                Imprimir requisición
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Seguimiento</div>
            <h2 class="mt-1 text-base font-semibold text-slate-900">Historial de impresiones</h2>
            <p class="mt-1 text-sm text-slate-600">Consulta todas las impresiones y reimpresiones realizadas.</p>

            <a href="{{ route('master_requests.reprints.index', $mr->id) }}"
                class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                Ver historial
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Documentos</div>
            <h2 class="mt-1 text-base font-semibold text-slate-900">Último lote generado</h2>
            <p class="mt-1 text-sm text-slate-600">Consulta rápido el lote más reciente desde esta requisición.</p>

            @if(session('batch_id'))
                <div class="mt-4 grid grid-cols-1 gap-2">
                    <a href="{{ route('master_print_batches.print', session('batch_id')) }}" target="_blank"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Abrir impresión
                    </a>
                </div>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm text-slate-500">
                    Aún no hay un lote reciente. Usa <span class="font-medium text-slate-700">Imprimir requisición</span> para generarlo. Si no aparece, <span class="font-medium text-slate-700">revisa el historial de impresiones.</span>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Líder</div>
            <div class="font-semibold">{{ $mr->leader_name }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jobs</div>
            <div class="font-semibold">Ensamble: {{ $mr->job_assembly ?? '-' }}</div>
            <div class="text-slate-700">Empaque: {{ $mr->job_packaging ?? '-' }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Destino / PO</div>
            <div class="font-semibold">{{ $mr->destination ?? '-' }}</div>
            <div class="text-slate-700">{{ $mr->po_number ?? '-' }}</div>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Resumen de folios</div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $totalFolios }}</div>
            <p class="text-sm text-slate-600">Folios totales en la requisición.</p>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-green-700">Impresos</div>
            <div class="mt-1 text-2xl font-semibold text-green-800">{{ $printedFolios }}</div>
            <p class="text-sm text-green-700">Folios con estatus printed.</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">Pendientes</div>
            <div class="mt-1 text-2xl font-semibold text-amber-800">{{ $pendingFolios }}</div>
            <p class="text-sm text-amber-700">Folios por imprimir o reimprimir.</p>
        </div>
    </div>

    <div class="mt-6">
        <h2 class="font-semibold text-slate-900">Folios</h2>
        <p class="mt-1 text-sm text-slate-500">Listado de folios incluidos en esta requisición master.</p>

        <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                <tr class="text-left text-slate-500 border-b border-slate-200">
                    <th class="py-3 px-4">Folio</th>
                    <th class="py-3 px-4">Tipo</th>
                    <th class="py-3 px-4">Qty</th>
                    <th class="py-3 px-4">Status</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($mr->folios->sortBy('folio_number') as $f)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-semibold">{{ str_pad($f->folio_number, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="py-3 px-4">{{ $f->is_partial ? 'Parcial' : 'Normal' }}</td>
                        <td class="py-3 px-4">{{ $f->qty_for_folio ?? '-' }}</td>
                        <td class="py-3 px-4">{{ $f->status }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
