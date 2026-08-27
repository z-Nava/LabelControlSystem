@extends('layouts.app', ['title' => 'Detalle de requisición de etiquetas'])

@section('content')
<div class="rounded-2xl bg-white p-6 shadow">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-slate-900">Requisición{{ $labelRequest->isLpk() ? ' LPK' : '' }} #{{ $labelRequest->id }}</h1>
                @if($labelRequest->isLpk())
                    <span class="inline-flex rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">LPK</span>
                @endif
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
        <p class="mt-1 text-sm">La preparación y entrega se controlan con el estatus general de la requisición. La impresión del comprobante en Kiosk se registra por separado.</p>
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
            <div class="text-slate-700">{{ $hasGroupedLpkDetails ? 'Reserva total por Jobs' : 'Cantidad general' }}: {{ number_format($labelRequest->quantity_requested) }}</div>
            <div class="text-slate-700">{{ $hasGroupedLpkDetails ? 'Grupos Shipping' : 'Cantidad Shipping' }}: {{ $hasGroupedLpkDetails ? $labelRequest->lpkShippingGroups->count() : ($labelRequest->include_shipping ? number_format($labelRequest->shipping_quantity ?? $labelRequest->quantity_requested) : 'No requerida') }}</div>
            <div class="text-slate-700">Semana: {{ $labelRequest->week }}</div>
            <div class="text-slate-700">Solicita: {{ $labelRequest->requested_by_name }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Producción</div>
            @if($hasGroupedLpkDetails)
                <div class="mt-1 font-semibold">Jobs de producción: {{ $lpkProductionJobs->count() }}</div>
                <div class="break-words text-slate-700">{{ $lpkProductionJobs->implode(', ') ?: 'Sólo Shipping' }}</div>
                <div class="text-slate-700">Los Jobs Shipping son informativos.</div>
            @else
                <div class="mt-1 font-semibold">Job: {{ $labelRequest->job_number ?: '—' }}</div>
                <div class="text-slate-700">Assembly: {{ $labelRequest->oracleJob?->assembly ?: '—' }}</div>
                <div class="text-slate-700">{{ $labelRequest->isLpk() ? 'Ensamble final' : 'Modelo general' }}: {{ $labelRequest->model ?: '—' }}</div>
            @endif
            <div class="text-slate-700">Líder: {{ $labelRequest->leader_name }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Números de parte y folios</div>
            @if($hasGroupedLpkDetails)
                @foreach($labelRequest->lpkLabelGroups as $group)
                    <div class="{{ $loop->first ? 'mt-1 font-semibold' : 'text-slate-700' }}">{{ $group->type_label }}: {{ $group->part_number }} · {{ $group->items->count() }} modelo(s)/Job(s)</div>
                @endforeach
                @foreach($labelRequest->lpkShippingGroups as $group)
                    <div class="text-amber-800">Shipping: {{ $group->part_number }} · {{ number_format($group->quantity) }} etiqueta(s) · {{ $group->items->count() }} modelo(s)/Job(s)</div>
                @endforeach
            @else
                @forelse($labelRequest->requestedSerialItems() as $item)
                    <div class="{{ $loop->first ? 'mt-1 font-semibold' : 'text-slate-700' }}">NP Serial: {{ $item['part_number'] }} · Modelo: {{ $item['model'] ?: '—' }}</div>
                @empty
                    <div class="mt-1 font-semibold">NP Serial: No requerido</div>
                @endforelse
                @forelse($labelRequest->requestedRatingItems() as $item)
                    <div class="text-slate-700">NP Rating: {{ $item['part_number'] }} · Modelo: {{ $item['model'] ?: '—' }}</div>
                @empty
                    <div class="text-slate-700">NP Rating: No requerido</div>
                @endforelse
            @endif
            <div class="text-slate-700">Folio inicial: {{ $labelRequest->folio_start ?? 'No requerido' }}</div>
            <div class="text-slate-700">Folio final: {{ $labelRequest->folio_end ?? 'No requerido' }}</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Oracle</div>
            @if($hasGroupedLpkDetails)
                <div class="mt-1 font-semibold">Todos los Jobs fueron validados al crear la requisición.</div>
                <div class="text-slate-700">La disponibilidad aplica sólo a Serial, Rating e Inner.</div>
            @else
                <div class="mt-1 font-semibold">PO: {{ $labelRequest->po_number ?: '—' }}</div>
                <div class="text-slate-700">Destino: {{ $labelRequest->destination ?: '—' }}</div>
                <div class="text-slate-700">Job Qty: {{ $labelRequest->oracleJob?->job_qty !== null ? number_format($labelRequest->oracleJob->job_qty) : '—' }}</div>
                <div class="text-slate-700">Restante: {{ $labelRequest->oracleJob?->quantity_remainder !== null ? number_format($labelRequest->oracleJob->quantity_remainder) : '—' }}</div>
            @endif
        </div>
    </div>

    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/60">
        <div class="border-b border-slate-200 bg-white px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Bloques de trabajo por etiqueta</h2>
            <p class="mt-1 text-sm text-slate-600">Cada bloque puede asignarse como una tarea independiente. Dentro de cada tipo, cada tarjeta corresponde a un NP de etiqueta física.</p>
        </div>

        <div class="space-y-5 p-4 sm:p-5">
            @foreach($workBlocks as $workBlock)
                <article @class([
                    'overflow-hidden rounded-2xl border bg-white shadow-sm',
                    'border-blue-200' => $workBlock['key'] === 'serial',
                    'border-violet-200' => $workBlock['key'] === 'rating',
                    'border-emerald-200' => $workBlock['key'] === 'inner',
                    'border-amber-200' => $workBlock['key'] === 'shipping',
                ])>
                    <header @class([
                        'flex flex-col gap-3 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between',
                        'border-blue-200 bg-blue-50' => $workBlock['key'] === 'serial',
                        'border-violet-200 bg-violet-50' => $workBlock['key'] === 'rating',
                        'border-emerald-200 bg-emerald-50' => $workBlock['key'] === 'inner',
                        'border-amber-200 bg-amber-50' => $workBlock['key'] === 'shipping',
                    ])>
                        <div class="flex items-center gap-3">
                            <span @class([
                                'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white',
                                'bg-blue-700' => $workBlock['key'] === 'serial',
                                'bg-violet-700' => $workBlock['key'] === 'rating',
                                'bg-emerald-700' => $workBlock['key'] === 'inner',
                                'bg-amber-600' => $workBlock['key'] === 'shipping',
                            ])>
                                {{ $loop->iteration }}
                            </span>
                            <div>
                                <h3 class="text-lg font-bold text-slate-950">Etiqueta {{ $workBlock['label'] }}</h3>
                                <p class="text-xs text-slate-600">Bloque listo para asignar a una operadora.</p>
                            </div>
                        </div>
                        <span class="inline-flex w-fit rounded-full border border-white/80 bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">
                            {{ $workBlock['task_count'] }} {{ $workBlock['task_count'] === 1 ? 'NP físico' : 'NP físicos' }}
                        </span>
                    </header>

                    <div class="space-y-4 p-4">
                        @if($workBlock['mode'] === 'production')
                            @forelse($workBlock['groups'] as $group)
                                <section class="overflow-hidden rounded-xl border border-slate-200">
                                    <div class="flex flex-col gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Número de parte de etiqueta</div>
                                            <div class="mt-0.5 font-mono text-base font-bold text-slate-950">{{ $group->part_number }}</div>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-600">{{ $group->items->count() }} {{ $group->items->count() === 1 ? 'renglón' : 'renglones' }}</span>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full min-w-[560px] text-sm">
                                            <thead class="bg-white text-left text-xs uppercase text-slate-500">
                                                <tr>
                                                    <th class="px-4 py-2.5">Job</th>
                                                    <th class="px-4 py-2.5">Modelo</th>
                                                    <th class="px-4 py-2.5 text-right">Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($group->items as $item)
                                                    <tr>
                                                        <td class="px-4 py-3 font-mono font-semibold text-slate-900">{{ $item->job_number }}</td>
                                                        <td class="px-4 py-3 text-slate-700">{{ $item->model ?: 'Sin modelo' }}</td>
                                                        <td class="px-4 py-3 text-right text-base font-bold text-slate-950">{{ number_format($item->quantity) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">No hay NP capturados para este tipo.</div>
                            @endforelse
                        @elseif($workBlock['mode'] === 'shipping')
                            @forelse($workBlock['groups'] as $group)
                                <section class="overflow-hidden rounded-xl border border-amber-200">
                                    <div class="grid grid-cols-1 gap-3 border-b border-amber-200 bg-amber-50/70 px-4 py-3 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-center">
                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-wide text-amber-800">NP Shipping</div>
                                            <div class="mt-0.5 font-mono text-base font-bold text-slate-950">{{ $group->part_number }}</div>
                                        </div>
                                        <div class="text-sm text-slate-700">
                                            <span class="font-semibold">PO:</span> {{ $group->po_number ?: 'Sin PO' }}<br>
                                            <span class="font-semibold">Destino:</span> {{ $group->destination ?: 'Sin destino' }}
                                        </div>
                                        <div class="rounded-xl bg-amber-600 px-4 py-2 text-center text-white">
                                            <div class="text-[10px] font-semibold uppercase tracking-wide">Cantidad total</div>
                                            <div class="text-xl font-black">{{ number_format($group->quantity) }}</div>
                                        </div>
                                    </div>
                                    <div class="border-b border-amber-100 bg-white px-4 py-2 text-xs font-medium text-amber-800">Esta cantidad corresponde al grupo completo; no se multiplica por los modelos.</div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full min-w-[480px] text-sm">
                                            <thead class="bg-white text-left text-xs uppercase text-slate-500">
                                                <tr>
                                                    <th class="px-4 py-2.5">Job informativo</th>
                                                    <th class="px-4 py-2.5">Modelo</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($group->items as $item)
                                                    <tr>
                                                        <td class="px-4 py-3 font-mono font-semibold text-slate-900">{{ $item->job_number }}</td>
                                                        <td class="px-4 py-3 text-slate-700">{{ $item->model ?: 'Sin modelo' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            @empty
                                <div class="rounded-xl border border-dashed border-amber-300 p-4 text-sm text-amber-800">No hay grupos Shipping capturados.</div>
                            @endforelse
                        @else
                            @forelse($workBlock['lines'] as $line)
                                <section class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-4">
                                    <div>
                                        <div class="text-xs font-semibold uppercase text-slate-500">NP de etiqueta</div>
                                        <div class="mt-1 font-mono font-bold text-slate-950">{{ $line['part_number'] ?: 'No capturado' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase text-slate-500">Job</div>
                                        <div class="mt-1 font-mono font-semibold text-slate-900">{{ ($line['job_number'] ?? $labelRequest->job_number) ?: '—' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold uppercase text-slate-500">Modelo</div>
                                        <div class="mt-1 text-slate-700">{{ $line['model'] ?: 'Sin modelo' }}</div>
                                    </div>
                                    <div class="sm:text-right">
                                        <div class="text-xs font-semibold uppercase text-slate-500">Cantidad</div>
                                        <div class="mt-1 text-xl font-black text-slate-950">{{ number_format($line['quantity']) }}</div>
                                    </div>
                                </section>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">No hay detalle capturado para este tipo.</div>
                            @endforelse
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

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
                <div class="font-semibold text-slate-900">Comprobante Kiosk</div>
                <div class="mt-1 text-slate-600">{{ $labelRequest->requisition_printed_at?->format('Y-m-d H:i') ?? 'Pendiente' }}</div>
                <div class="text-slate-500">{{ $labelRequest->requisitionPrintedByUser?->name ?? '—' }}</div>
            </div>
            <div class="p-4">
                <div class="font-semibold text-slate-900">Lista para entregar</div>
                <div class="mt-1 text-slate-600">{{ $labelRequest->attended_at?->format('Y-m-d H:i') ?? 'Pendiente' }}</div>
                <div class="text-slate-500">{{ $labelRequest->readyForDeliveryByUser?->name ?? '—' }}</div>
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
