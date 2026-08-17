@extends('layouts.app', ['title' => 'Detalle de requisición de etiquetas'])

@section('content')
@php
    $printBatches = $labelRequest->printBatches;
    $hasUnprintedPrintBatch = $printBatches->contains(fn ($batch) => $batch->batch_type === 'print' && $batch->printed_at === null);
@endphp

<div class="rounded-2xl bg-white p-6 shadow">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-slate-900">Requisición #{{ $labelRequest->id }}</h1>
                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $labelRequest->status_badge_classes }}">{{ $labelRequest->status_label }}</span>
            </div>
            <p class="mt-1 text-slate-600">{{ $labelRequest->line?->code }} · Turno {{ $labelRequest->shift?->code }} · {{ $labelRequest->request_date?->format('Y-m-d') }}</p>
        </div>

        <a href="{{ route('label_requests.index') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al listado</a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
        <div class="font-semibold">Impresión automática de etiquetas no disponible temporalmente</div>
        <p class="mt-1 text-sm">La operación habilitada en este flujo es imprimir la hoja física de requisición y actualizar manualmente su avance.</p>
    </div>

    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Acciones de la requisición</div>
        <div class="mt-3">
            @include('label_requests.partials.workflow-actions', ['labelRequest' => $labelRequest])
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Solicitud</div>
            <div class="mt-1 font-semibold">{{ implode(' + ', $labelRequest->requestedLabelTypes()) ?: 'Sin tipo' }}</div>
            <div class="text-slate-700">Cantidad general: {{ number_format($labelRequest->quantity_requested) }}</div>
            <div class="text-slate-700">Cantidad Shipping: {{ $labelRequest->include_shipping ? number_format($labelRequest->shipping_quantity ?? $labelRequest->quantity_requested) : 'No requerida' }}</div>
            <div class="text-slate-700">Semana: {{ $labelRequest->week }}</div>
            <div class="text-slate-700">Solicita: {{ $labelRequest->requested_by_name }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Producción</div>
            <div class="mt-1 font-semibold">Job: {{ $labelRequest->job_number ?: '—' }}</div>
            <div class="text-slate-700">Assembly: {{ $labelRequest->oracleJob?->assembly ?: '—' }}</div>
            <div class="text-slate-700">Modelo: {{ $labelRequest->model ?: '—' }}</div>
            <div class="text-slate-700">Líder: {{ $labelRequest->leader_name }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Números de parte y folios</div>
            <div class="mt-1 font-semibold">NP Serial: {{ $labelRequest->serial_part_number ?: ($labelRequest->label_part_number ?: '—') }}</div>
            @forelse($labelRequest->requestedRatingPartNumbers() as $ratingPartNumber)
                <div class="text-slate-700">NP Rating: {{ $ratingPartNumber }}</div>
            @empty
                <div class="text-slate-700">NP Rating: No requerido</div>
            @endforelse
            <div class="text-slate-700">Folio inicial: {{ $labelRequest->folio_start ?? 'No requerido' }}</div>
            <div class="text-slate-700">Folio final: {{ $labelRequest->folio_end ?? 'No requerido' }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Oracle</div>
            <div class="mt-1 font-semibold">PO: {{ $labelRequest->po_number ?: '—' }}</div>
            <div class="text-slate-700">Destino: {{ $labelRequest->destination ?: '—' }}</div>
            <div class="text-slate-700">Job Qty: {{ $labelRequest->oracleJob?->job_qty !== null ? number_format($labelRequest->oracleJob->job_qty) : '—' }}</div>
            <div class="text-slate-700">Restante: {{ $labelRequest->oracleJob?->quantity_remainder !== null ? number_format($labelRequest->oracleJob->quantity_remainder) : '—' }}</div>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h2 class="font-semibold text-slate-900">Detalle de etiquetas solicitado</h2>
            <p class="mt-1 text-xs text-slate-500">Cada Rating del combo conserva la cantidad general completa.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="px-4 py-3">Job</th>
                        <th class="px-4 py-3">Modelo</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Número de parte</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($labelRequest->requestedLabelLines() as $line)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $labelRequest->job_number ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $labelRequest->model ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $line['type'] }}</td>
                            <td class="px-4 py-3 font-mono">{{ $line['part_number'] ?: 'No aplica' }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($line['quantity']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h2 class="font-semibold text-slate-900">Trazabilidad del flujo físico</h2>
        </div>
        <div class="grid grid-cols-1 divide-y text-sm md:grid-cols-4 md:divide-x md:divide-y-0">
            <div class="p-4">
                <div class="font-semibold text-slate-900">Registrada</div>
                <div class="mt-1 text-slate-600">{{ $labelRequest->created_at?->format('Y-m-d H:i') }}</div>
                <div class="text-slate-500">{{ $labelRequest->requested_by_name }}</div>
            </div>
            <div class="p-4">
                <div class="font-semibold text-slate-900">Hoja impresa</div>
                <div class="mt-1 text-slate-600">{{ $labelRequest->requisition_printed_at?->format('Y-m-d H:i') ?? 'Pendiente' }}</div>
                <div class="text-slate-500">{{ $labelRequest->requisitionPrintedByUser?->name ?? '—' }}</div>
            </div>
            <div class="p-4">
                <div class="font-semibold text-slate-900">Atendida</div>
                <div class="mt-1 text-slate-600">{{ $labelRequest->attended_at?->format('Y-m-d H:i') ?? 'Pendiente' }}</div>
                <div class="text-slate-500">{{ $labelRequest->attendedByUser?->name ?? '—' }}</div>
            </div>
            <div class="p-4">
                <div class="font-semibold text-slate-900">Entregada</div>
                <div class="mt-1 text-slate-600">{{ $labelRequest->delivered_at?->format('Y-m-d H:i') ?? 'Pendiente' }}</div>
                <div class="text-slate-500">{{ $labelRequest->deliveredByUser?->name ?? '—' }}</div>
            </div>
        </div>
    </div>

    @if($labelRequest->notes)
        <div class="mt-6 rounded-xl border border-slate-200 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Notas</div>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $labelRequest->notes }}</p>
        </div>
    @endif

    @if($hasUnprintedPrintBatch)
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Existe un batch del sistema anterior creado pero no confirmado como impreso.
        </div>
    @endif

    <div id="rangos-serial" class="mt-6 rounded-xl border border-slate-200">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h2 class="font-semibold text-slate-900">Rangos de serial asignados por el sistema anterior</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="px-4 py-3">Semana/Año</th>
                        <th class="px-4 py-3">Prefijo</th>
                        <th class="px-4 py-3">Rango</th>
                        <th class="px-4 py-3">Cantidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($labelRequest->serialRanges as $range)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">{{ $range->week?->week ?? '—' }} / {{ $range->week?->year ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $range->week?->prefix ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono">{{ $range->range_start }} - {{ $range->range_end }}</td>
                            <td class="px-4 py-3">{{ number_format($range->quantity) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No hay rangos generados por el sistema anterior.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="historial-impresiones" class="mt-6 rounded-xl border border-slate-200">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h2 class="font-semibold text-slate-900">Historial de impresiones automáticas (batches)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Impreso por</th>
                        <th class="px-4 py-3">Razón</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($printBatches as $batch)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">{{ $batch->printed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $batch->batch_type }}</td>
                            <td class="px-4 py-3">{{ $batch->printed_by_name ?? $batch->printedByUser?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $batch->reason ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $batch->printed_at ? 'Confirmada' : 'Pendiente' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($labelRequest->status === \App\Models\LabelRequest::STATUS_COMPLETED)
                                    <span class="rounded-lg border bg-slate-100 px-3 py-1.5 text-xs text-slate-500">Cerrada</span>
                                @else
                                    <a href="{{ route('label_requests.print_batches.print', ['label_request' => $labelRequest, 'batch' => $batch]) }}" class="rounded-lg border px-3 py-1.5 text-xs hover:bg-slate-50">Centro de impresión</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay batches registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
