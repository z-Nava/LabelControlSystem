@extends('layouts.app', ['title' => 'Detalle de requisición de etiquetas'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Requisición #{{ $labelRequest->id }}</h1>
            <p class="text-slate-600 mt-1">{{ $labelRequest->line?->code }} · Turno {{ $labelRequest->shift?->code }} · {{ $labelRequest->request_date?->format('Y-m-d') }}</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('label_requests.index') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al listado</a>
            <a href="{{ route('label_requests.print.create', $labelRequest) }}" class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500">Ir a imprimir</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @php
        $status = $labelRequest->status;
        $statusText = match ($status) {
            'requested' => 'Solicitada',
            'in_progress' => 'En proceso',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => ucfirst(str_replace('_', ' ', (string) $status)),
        };
        $statusClasses = match ($status) {
            'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            'cancelled' => 'bg-red-100 text-red-700 border-red-200',
            'in_progress' => 'bg-amber-100 text-amber-700 border-amber-200',
            default => 'bg-sky-100 text-sky-700 border-sky-200',
        };
    @endphp

    <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
        <h2 class="text-base font-semibold text-blue-900">¿Qué debes hacer en esta pantalla?</h2>
        <ol class="mt-3 space-y-2 text-sm text-blue-900 list-decimal list-inside">
            <li>Revisa que los datos de la requisición sean correctos (NP, cantidad, semana y datos de producción).</li>
            <li>Confirma el estatus actual:
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $statusClasses }}">
                    {{ $statusText }}
                </span>
            </li>
            <li>Si todo está listo, haz clic en <span class="font-semibold">“Ir a imprimir”</span> para generar o reimprimir etiquetas.</li>
            <li>Cuando la requisición quede terminada, usa <span class="font-semibold">“Marcar como completada”</span>.</li>
        </ol>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Resumen</div>
            <div class="font-semibold mt-1">NP: {{ $labelRequest->label_part_number }}</div>
            <div class="text-slate-700">Qty: {{ number_format($labelRequest->quantity_requested) }}</div>
            <div class="text-slate-700">Semana/Año: {{ $labelRequest->week }} / {{ $labelRequest->request_date?->format('Y') }}</div>
            <div class="text-slate-700">Incluye serial: {{ $labelRequest->include_serial ? 'Sí' : 'No' }}</div>
            <div class="text-slate-700">Incluye rating: {{ $labelRequest->include_rating ? 'Sí' : 'No' }}</div>
            <div class="mt-1 text-slate-700">Estatus:
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $statusClasses }}">
                    {{ $statusText }}
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Datos de producción</div>
            <div class="font-semibold mt-1">Job: {{ $labelRequest->job_number ?? '-' }}</div>
            <div class="text-slate-700">PO: {{ $labelRequest->po_number ?? '-' }}</div>
            <div class="text-slate-700">Destino: {{ $labelRequest->destination ?? '-' }}</div>
            <div class="text-slate-700">Modelo: {{ $labelRequest->model ?? '-' }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Acciones de estado</div>
            <p class="mt-1 text-xs text-slate-600">Usa estas acciones solo cuando corresponda para mantener trazabilidad correcta.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @if(in_array($labelRequest->status, ['requested', 'in_progress'], true))
                    <form method="POST" action="{{ route('label_requests.complete', $labelRequest) }}">
                        @csrf
                        <button class="rounded-lg border border-emerald-200 text-emerald-700 px-3 py-1.5 text-sm hover:bg-emerald-50">Marcar como completada</button>
                    </form>

                    <form method="POST" action="{{ route('label_requests.cancel', $labelRequest) }}">
                        @csrf
                        <button class="rounded-lg border border-red-200 text-red-700 px-3 py-1.5 text-sm hover:bg-red-50" onclick="return confirm('¿Cancelar requisición?')">Cancelar</button>
                    </form>
                @else
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">
                        Esta requisición ya no permite cambios de estado.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
            <h2 class="font-semibold text-slate-900">Rangos de serial asignados</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500 border-b border-slate-200">
                    <th class="py-3 px-4">Semana/Año</th>
                    <th class="py-3 px-4">Prefijo</th>
                    <th class="py-3 px-4">Rango</th>
                    <th class="py-3 px-4">Cantidad</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($labelRequest->serialRanges as $range)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">{{ $range->week?->week ?? '-' }} / {{ $range->week?->year ?? '-' }}</td>
                        <td class="py-3 px-4">{{ $range->week?->prefix ?? '-' }}</td>
                        <td class="py-3 px-4 font-mono">{{ $range->range_start }} - {{ $range->range_end }}</td>
                        <td class="py-3 px-4">{{ number_format($range->quantity) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">Aún no hay rangos asignados. Se generan al primer batch de tipo print.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
            <h2 class="font-semibold text-slate-900">Historial de impresiones (batches)</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-4">Tipo</th>
                        <th class="py-3 px-4">Impreso por</th>
                        <th class="py-3 px-4">Razón</th>
                        <th class="py-3 px-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($labelRequest->printBatches as $batch)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4">{{ $batch->printed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="py-3 px-4">{{ $batch->batch_type }}</td>
                            <td class="py-3 px-4">{{ $batch->printed_by_name ?? $batch->printedByUser?->name ?? '-' }}</td>
                            <td class="py-3 px-4">{{ $batch->reason ?: '-' }}</td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('label_requests.print_batches.print', ['label_request' => $labelRequest, 'batch' => $batch]) }}" class="rounded-lg border px-3 py-1.5 text-xs hover:bg-slate-50">Centro de impresión</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Aún no hay batches registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
