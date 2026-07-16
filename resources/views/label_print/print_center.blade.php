@extends('layouts.app', ['title' => 'Centro de impresion'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6" id="label-print-center"
     data-preview-url="{{ route('label_requests.print_batches.preview', ['label_request' => $labelRequest, 'batch' => $batch]) }}"
     data-confirm-url="{{ route('label_requests.print_batches.confirm', ['label_request' => $labelRequest, 'batch' => $batch]) }}"
     data-fail-url="{{ route('label_requests.print_batches.fail', ['label_request' => $labelRequest, 'batch' => $batch]) }}"
     data-browser-print-url="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"
     data-csrf-token="{{ csrf_token() }}"
     data-already-printed="{{ $batch->printed_at ? '1' : '0' }}"
     data-back-url="{{ route('label_requests.show', $labelRequest) }}">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Centro de impresion</h1>
            <p class="text-slate-600 mt-1">Requisicion #{{ $labelRequest->id }} - Batch #{{ $batch->id }}</p>
        </div>
        <a href="{{ route('label_requests.show', $labelRequest) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al detalle</a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
        <h2 class="text-base font-semibold text-blue-900">Que acabas de hacer y que sigue?</h2>
        <ol class="mt-3 list-decimal list-inside space-y-2 text-sm text-blue-900">
            <li>Esta pantalla corresponde al <span class="font-semibold">batch #{{ $batch->id }}</span> de la requisicion <span class="font-semibold">#{{ $labelRequest->id }}</span>.</li>
            <li>Conecta tu impresora y presiona <span class="font-semibold">Preparar impresion</span> una sola vez para validar el primer bloque.</li>
            <li>Si el bloque sale correcto, presiona <span class="font-semibold">Imprimir ahora</span>; el sistema cargara el siguiente bloque automaticamente.</li>
            <li>Al finalizar todos los bloques, veras una confirmacion de impresion para saber que el sistema registro correctamente lo impreso.</li>
        </ol>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <button id="connect-printer" type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Conectar impresora</button>
        <button id="preview-batch" type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Preparar impresion</button>
        <button id="open-alignment-modal" type="button" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100">Ajustar posiciones</button>
        <button id="print-batch" type="button" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">Imprimir ahora</button>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Impresora SERIAL</div>
            <div id="selected-printer-serial" class="mt-1 text-sm text-slate-800">Sin seleccionar</div>
            <div class="mt-3">
                <label for="printer-select-serial" class="text-xs uppercase tracking-wide text-slate-500">Elegir impresora para Serial</label>
                <select id="printer-select-serial" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Primero detecta impresoras</option>
                </select>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Impresora RATING</div>
            <div id="selected-printer-rating" class="mt-1 text-sm text-slate-800">Sin seleccionar</div>
            <div class="mt-3">
                <label for="printer-select-rating" class="text-xs uppercase tracking-wide text-slate-500">Elegir impresora para Rating</label>
                <select id="printer-select-rating" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Primero detecta impresoras</option>
                </select>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Estado</div>
            <div id="print-status" class="mt-1 text-sm text-slate-700">Pendiente de conexion y preparacion de impresion.</div>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="text-xs uppercase tracking-wide text-emerald-700">Confirmacion</div>
            <div id="print-confirmation" class="mt-1 text-sm text-emerald-800">Aun no hay impresion confirmada.</div>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 text-sm font-semibold text-slate-900">Resumen de lo que se imprimira</div>
        <div id="preview-summary" class="p-4 text-sm text-slate-600">Aun no se ha preparado la impresion.</div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3">
            <div class="text-sm font-semibold text-slate-900">Progreso por bloques</div>
            <div id="block-progress-summary" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sin preparar</div>
        </div>
        <div class="p-4">
            <div id="block-current-message" class="mb-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">Prepara la impresion para ver el bloque activo.</div>
            <div id="block-progress" class="grid gap-3 text-sm text-slate-600 md:grid-cols-2 xl:grid-cols-4">Prepara la impresion para ver los bloques generados por el sistema.</div>
        </div>
    </div>
</div>



<div id="alignment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4">
    <div class="max-h-[92vh] w-full max-w-6xl overflow-y-auto rounded-2xl bg-white shadow-xl">
        <div class="border-b px-5 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Ajuste rapido de posiciones</h3>
            <p class="mt-1 text-sm text-slate-600">Si la etiqueta salio desfazada, conserva el ajuste manual por pixeles o arrastra libremente los elementos de la etiqueta con el editor visual.</p>
        </div>
        <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Editor visual con Fabric JS</div>
                        <p class="text-xs text-slate-600">Presiona Cargar elementos si aun no preparaste la impresion. Arrastra el QR o el grupo de textos; SKU y SN se mueven juntos.</p>
                    </div>
                    <div class="flex gap-2">
                        <button data-alignment-type="serial" type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Serial</button>
                        <button data-alignment-type="rating" type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Rating</button>
                    </div>
                </div>
                <div class="mt-3 overflow-auto rounded-lg border border-slate-200 bg-white p-3">
                    <canvas id="alignment-fabric-canvas" width="640" height="360" class="max-w-full"></canvas>
                </div>
                <div id="alignment-canvas-status" class="mt-2 text-xs text-slate-600">El preview visual se cargara desde los ZPL de esta requisicion.</div>
                <button id="load-alignment-preview" type="button" class="mt-3 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cargar elementos</button>
            </div>
            <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 p-3">
                <div class="text-sm font-semibold text-slate-900">Etiqueta SERIAL</div>
                <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                    <label>Texto (SN/SKU) X <input data-align="serial_text_x" type="number" class="mt-1 w-full rounded border px-2 py-1"></label>
                    <label>Texto (SN/SKU) Y <input data-align="serial_text_y" type="number" class="mt-1 w-full rounded border px-2 py-1"></label>
                    <label>QR X <input data-align="serial_qr_x" type="number" class="mt-1 w-full rounded border px-2 py-1"></label>
                    <label>QR Y <input data-align="serial_qr_y" type="number" class="mt-1 w-full rounded border px-2 py-1"></label>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 p-3">
                <div class="text-sm font-semibold text-slate-900">Etiqueta RATING</div>
                <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                    <label>Texto (SN) X <input data-align="rating_text_x" type="number" class="mt-1 w-full rounded border px-2 py-1"></label>
                    <label>Texto (SN) Y <input data-align="rating_text_y" type="number" class="mt-1 w-full rounded border px-2 py-1"></label>
                    <label>QR X <input data-align="rating_qr_x" type="number" class="mt-1 w-full rounded border px-2 py-1"></label>
                    <label>QR Y <input data-align="rating_qr_y" type="number" class="mt-1 w-full rounded border px-2 py-1"></label>
                </div>
            </div>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t px-5 py-4">
            <button id="reset-alignment" type="button" class="rounded-xl border px-4 py-2 text-sm">Reset</button>
            <button id="close-alignment-modal" type="button" class="rounded-xl border px-4 py-2 text-sm">Cerrar</button>
            <button id="save-alignment" type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Guardar ajustes</button>
        </div>
    </div>
</div>

@endsection
