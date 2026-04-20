@extends('layouts.app', ['title' => 'Detalle de requisición Dummy QR'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Requisición Dummy QR #{{ $dummyRequest->id }}</h1>
            <p class="text-slate-600 mt-1">{{ $dummyRequest->line?->code }} · Turno {{ $dummyRequest->shift?->code }} · {{ $dummyRequest->request_date?->format('Y-m-d') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dummy_requests.index') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al listado</a>
            @if(in_array($dummyRequest->status, ['requested', 'in_progress'], true))
                <a href="{{ route('dummy_requests.print.create', $dummyRequest) }}" class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500">Ir a imprimir</a>
            @endif
            @if($canAccessSelectionReprint)
                <a href="{{ route('dummy_reprints.show', $dummyRequest) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Reimpresión por selección</a>
            @else
                <span class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm text-slate-500 cursor-not-allowed" title="{{ $selectionReprintBlockedReason }}">
                    Reimpresión por selección
                </span>
            @endif
        </div>
        @if(!$canAccessSelectionReprint && filled($selectionReprintBlockedReason))
            <p class="text-xs text-slate-500">{{ $selectionReprintBlockedReason }}</p>
        @endif
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
        <h2 class="text-base font-semibold text-blue-900">Flujo sugerido</h2>
        <ol class="mt-2 list-decimal list-inside text-sm text-blue-900 space-y-1">
            <li>Validar resumen y rango consecutivo del Job.</li>
            <li>Entrar a <span class="font-semibold">Ir a imprimir</span> para generar el batch inicial o reimpresiones.</li>
            <li>Cuando la producción termine, marcar como <span class="font-semibold">Completada</span>.</li>
            <li>Si se detecta error operativo antes de cerrar, usar <span class="font-semibold">Cancelar</span>.</li>
        </ol>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Resumen</div>
            <div class="font-semibold mt-1">{{ $dummyRequest->requestTypeTitle() }}</div>
            <div class="text-slate-700">Qty solicitada: {{ number_format($dummyRequest->quantity_requested) }}</div>
            <div class="text-slate-700">Qty impresa (batches): {{ number_format($dummyRequest->printedQuantity()) }}</div>
            <div class="text-slate-700">Rango:</div>
            <div class="font-mono text-xs">{{ str_pad((string) $dummyRequest->range_from, 10, '0', STR_PAD_LEFT) }} - {{ str_pad((string) $dummyRequest->range_to, 10, '0', STR_PAD_LEFT) }}</div>
            <div class="mt-1 text-slate-700">Estatus:
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $dummyRequest->statusBadgeClasses() }}">
                    {{ $dummyRequest->statusLabel() }}
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Datos del Job</div>
            <div class="font-semibold mt-1">Job: {{ $dummyRequest->job_number }}</div>
            <div class="text-slate-700">FG: {{ $dummyRequest->fg_code }}</div>
            <div class="text-slate-700">Solicitante: {{ $dummyRequest->requested_by_name }}</div>
            <div class="text-slate-700">Líder: {{ $dummyRequest->leader_name }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Acciones de estado</div>
            <p class="mt-1 text-xs text-slate-600">Estas acciones cierran el ciclo operativo de la requisición.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @if(in_array($dummyRequest->status, ['requested', 'in_progress'], true))
                    <form method="POST" action="{{ route('dummy_requests.complete', $dummyRequest) }}">
                        @csrf
                        <button class="rounded-lg border border-emerald-200 text-emerald-700 px-3 py-1.5 text-sm hover:bg-emerald-50">Marcar como completada</button>
                    </form>

                    <form method="POST" action="{{ route('dummy_requests.cancel', $dummyRequest) }}">
                        @csrf
                        <button class="rounded-lg border border-red-200 text-red-700 px-3 py-1.5 text-sm hover:bg-red-50" onclick="return confirm('¿Seguro que deseas cancelar la requisición dummy?')">Cancelar</button>
                    </form>
                @else
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">
                        Esta requisición ya no permite cambios de estado.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200" id="historial-impresiones">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
            <h2 class="font-semibold text-slate-900">Historial de impresiones (batches)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500 border-b border-slate-200">
                    <th class="py-3 px-4">Fecha</th>
                    <th class="py-3 px-4">Tipo</th>
                    <th class="py-3 px-4">Cantidad</th>
                    <th class="py-3 px-4">Impreso por</th>
                    <th class="py-3 px-4">Motivo</th>
                    <th class="py-3 px-4 text-right">Acción</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($dummyRequest->printBatches as $batch)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">{{ $batch->printed_at?->format('Y-m-d H:i') ?? $batch->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td class="py-3 px-4">{{ strtoupper($batch->batch_type) }}</td>
                        <td class="py-3 px-4">{{ number_format((int) $batch->quantity) }}</td>
                        <td class="py-3 px-4">{{ $batch->printed_by_name ?? $batch->printedByUser?->name ?? '-' }}</td>
                        <td class="py-3 px-4">{{ $batch->reason ?: '-' }}</td>
                        <td class="py-3 px-4 text-right">
                            @if($batch->printed_at)
                                <span class="inline-flex rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs text-slate-500 cursor-not-allowed" title="Bloqueado para evitar duplicaciones tras confirmar la impresión inicial.">
                                    Centro de impresión bloqueado
                                </span>
                            @else
                                <a href="{{ route('dummy_requests.print_batches.print', ['dummy_request' => $dummyRequest, 'batch' => $batch]) }}" class="rounded-lg border px-3 py-1.5 text-xs hover:bg-slate-50">Centro de impresión</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Aún no hay batches registrados para esta requisición.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
            <h2 class="font-semibold text-slate-900">Etiquetas Dummy generadas (primeros 200 registros)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-3 px-4">Consecutivo</th>
                        <th class="py-3 px-4">Tipo</th>
                        <th class="py-3 px-4">QR payload</th>
                        <th class="py-3 px-4">Reimpresiones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($dummyRequest->items as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono">{{ $item->consecutive_10d }}</td>
                            <td class="py-3 px-4">{{ strtoupper($item->dummy_type) }}</td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $item->qr_payload }}</td>
                            <td class="py-3 px-4">{{ number_format((int) $item->print_count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-slate-500">No hay etiquetas generadas para esta requisición.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
