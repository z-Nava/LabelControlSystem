@extends('layouts.app', ['title' => 'Centro de impresión Dummy QR'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6" id="dummy-print-center">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Centro de impresión Dummy QR</h1>
            <p class="text-slate-600 mt-1">Requisición #{{ $printCenter['request_id'] }} · Batch #{{ $printCenter['batch_id'] }} · {{ $printCenter['batch_type_label'] }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ $printCenter['detail_url'] }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al detalle</a>
        </div>
    </div>

    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
        <div><span class="font-semibold">Job:</span> {{ $printCenter['job_number'] }} | <span class="font-semibold">FG:</span> {{ $printCenter['fg_code'] }}</div>
        <div><span class="font-semibold">Cantidad total a imprimir:</span> {{ $printCenter['quantity_formatted'] }}</div>
        <div><span class="font-semibold">Línea/Turno:</span> {{ $printCenter['line_code'] }} · {{ $printCenter['shift_code'] }}</div>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <button id="connect-printer" type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Conectar impresora</button>
        <button id="prepare-print" type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Preparar impresión</button>
        <button id="print-batch" type="button" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:bg-slate-400">Imprimir</button>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Impresora seleccionada</div>
            <div id="selected-printer" class="mt-1 text-sm text-slate-800">Sin conectar</div>
            <div class="mt-3">
                <label for="printer-select" class="text-xs uppercase tracking-wide text-slate-500">Elegir impresora Zebra detectada</label>
                <select id="printer-select" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Primero detecta impresoras</option>
                </select>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Estado</div>
            <div id="print-status" class="mt-1 text-sm text-slate-700">Primero conecta una impresora para habilitar la impresión directa.</div>
        </div>
    </div>

    <div id="print-progress-panel" class="mt-4 rounded-xl border border-slate-200 bg-white p-4" aria-live="polite">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Progreso de impresión</div>
            <div id="print-progress-summary" class="text-sm font-semibold text-slate-700">Pendiente</div>
        </div>
        <div
            id="print-progress"
            class="mt-3 h-3 overflow-hidden rounded-full bg-slate-200"
            role="progressbar"
            aria-label="Progreso informativo de impresión"
            aria-valuemin="0"
            aria-valuemax="{{ $printCenter['quantity'] }}"
            aria-valuenow="0"
        >
            <div id="print-progress-bar" class="h-full w-0 rounded-full bg-red-600 transition-all duration-200 ease-out"></div>
        </div>
        <div id="print-progress-detail" class="mt-2 text-sm text-slate-600">0 de {{ $printCenter['quantity_formatted'] }} etiquetas enviadas (0%).</div>
        <p class="mt-1 text-xs text-slate-500">Indicador informativo: muestra los datos aceptados por Zebra Browser Print; no confirma la salida física de cada etiqueta.</p>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
            <tr class="text-left text-slate-500 border-b border-slate-200">
                <th class="py-3 px-4">Consecutivo</th>
                <th class="py-3 px-4">Tipo</th>
                <th class="py-3 px-4">Copias</th>
                <th class="py-3 px-4">QR payload</th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @foreach($printCenter['items'] as $item)
                <tr class="hover:bg-slate-50">
                    <td class="py-3 px-4 font-mono">{{ $item['consecutive'] }}</td>
                    <td class="py-3 px-4">{{ $item['dummy_type_label'] }}</td>
                    <td class="py-3 px-4">{{ $item['copies_formatted'] }}</td>
                    <td class="py-3 px-4 font-mono text-xs">{{ $item['qr_payload'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script id="dummy-print-center-config" type="application/json">{!! Illuminate\Support\Js::encode($printCenterConfig) !!}</script>
@endsection

@push('scripts')
    @vite('resources/js/pages/dummy-print-center.js')
@endpush
