@extends('layouts.app', ['title' => 'Detalle Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Requisición Master #{{ $mr->id }}</h1>
            <p class="text-slate-600 mt-1">
                {{ $mr->line?->code }}
                @if($mr->shift) · Turno {{ $mr->shift->code }} @endif
                @if($mr->request_date) · {{ $mr->request_date->format('Y-m-d') }} @endif
            </p>
            <span class="mt-2 inline-flex rounded-full px-2 py-1 text-xs {{ $mr->isFromKiosk() ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700' }}">
                Origen: {{ $mr->request_source_label }}
            </span>
            <span class="mt-2 inline-flex rounded-full px-2 py-1 text-xs {{ $mr->statusBadgeClasses() }}">
                Estado: {{ $mr->statusLabel() }}
            </span>
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

            @if($mr->isCancelled())
                <div class="mt-4 rounded-xl border border-red-200 bg-white px-4 py-3 text-center text-sm font-medium text-red-700">
                    Impresión bloqueada: requisición cancelada
                </div>
            @else
                <a href="{{ route('master_requests.print.create', $mr->id) }}"
                    id="create-batch-print"
                    class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">
                    Imprimir requisición
                </a>
            @endif
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

            @if($mr->isCancelled())
                <div class="mt-4 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm text-red-700">
                    No hay documentos imprimibles para una requisición cancelada.
                </div>
            @elseif(session('batch_id'))
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

    @if($mr->isCancelled())
        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-red-700">Auditoría de cancelación</div>
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <div class="text-xs text-red-700">Cancelada por</div>
                    <div class="font-semibold text-slate-900">{{ $mr->cancelled_by_name ?? $mr->cancelledBy?->name ?? 'Dato histórico no disponible' }}</div>
                </div>
                <div>
                    <div class="text-xs text-red-700">Fecha y hora</div>
                    <div class="font-semibold text-slate-900">{{ $mr->cancelled_at?->format('Y-m-d H:i') ?? 'Dato histórico no disponible' }}</div>
                </div>
                <div class="md:col-span-3">
                    <div class="text-xs text-red-700">Motivo</div>
                    <div class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $mr->cancellation_reason ?: 'Dato histórico no disponible' }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Registro</div>
            <div class="font-semibold">Solicita: {{ $mr->requested_by_name ?: $mr->requestedBy?->name ?: '-' }}</div>
            <div class="text-slate-700">{{ $mr->created_at?->format('Y-m-d H:i') ?? '-' }}</div>
            @if($mr->leader_name)
                <div class="mt-1 text-xs text-slate-500">Líder histórico: {{ $mr->leader_name }}</div>
            @endif
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
        <div class="rounded-xl border {{ $mr->isCancelled() ? 'border-slate-200 bg-slate-100' : 'border-amber-200 bg-amber-50' }} p-4">
            <div class="text-xs font-semibold uppercase tracking-wide {{ $mr->isCancelled() ? 'text-slate-600' : 'text-amber-700' }}">
                {{ $mr->isCancelled() ? 'No aplican' : 'Pendientes' }}
            </div>
            <div class="mt-1 text-2xl font-semibold {{ $mr->isCancelled() ? 'text-slate-700' : 'text-amber-800' }}">{{ $pendingFolios }}</div>
            <p class="text-sm {{ $mr->isCancelled() ? 'text-slate-600' : 'text-amber-700' }}">
                {{ $mr->isCancelled() ? 'Folios conservados como pending por auditoría; ya no pueden imprimirse.' : 'Folios por imprimir o reimprimir.' }}
            </p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modelo</div>
            <div class="font-semibold">{{ $mr->model ?: '-' }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Local</div>
            <div class="font-semibold">{{ $mr->local ?: '-' }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subinventory</div>
            <div class="font-semibold">{{ $mr->subinventory ?: '-' }}</div>
        </div>
    </div>

    @if(!$mr->isRework() && $mr->revisions->isNotEmpty())
        <section class="mt-6 rounded-2xl border border-purple-200 bg-purple-50 p-4">
            <h2 class="font-semibold text-slate-900">Revisiones de retrabajo</h2>
            <div class="mt-3 space-y-2">
                @foreach($mr->revisions as $revision)
                    <a href="{{ route('master_reworks.show', $revision) }}" class="flex flex-col gap-1 rounded-xl border border-purple-200 bg-white px-4 py-3 hover:bg-purple-50 sm:flex-row sm:items-center sm:justify-between">
                        <span class="font-semibold text-purple-800">R{{ $revision->revision_number }} · Revisión #{{ $revision->id }}</span>
                        <span class="text-sm text-slate-600">{{ $revision->folios_count }} folio(s) · {{ $revision->reworked_by_name ?: $revision->reworkedBy?->name ?: '—' }} · {{ $revision->reworked_at?->format('Y-m-d H:i') ?: '—' }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

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
                        <td class="py-3 px-4">
                            {{ $mr->isCancelled() && $f->status === 'pending' ? 'No aplica (requisición cancelada)' : $f->status }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/master-requests-show.js')
@endpush
