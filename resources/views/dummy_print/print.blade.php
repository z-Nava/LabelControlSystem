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
        <button id="open-dummy-alignment" type="button" class="rounded-xl border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800 hover:border-blue-400 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50">Ajustar posiciones</button>
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

<div id="dummy-alignment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:p-5" role="dialog" aria-modal="true" aria-labelledby="dummy-alignment-modal-title">
    <div class="flex max-h-[94vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 id="dummy-alignment-modal-title" class="text-xl font-bold text-slate-900">Ajustar posiciones del Dummy QR</h2>
                    <span id="dummy-alignment-unsaved-badge" class="hidden rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Cambios sin guardar</span>
                </div>
                <p class="mt-1 text-sm text-slate-600">Los movimientos son locales para la impresora seleccionada y no modifican el template original.</p>
            </div>
            <button id="close-dummy-alignment" type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 text-xl text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Cerrar ajuste">&times;</button>
        </div>

        <div class="grid flex-1 gap-5 overflow-y-auto p-4 sm:p-6 lg:grid-cols-[minmax(0,1fr)_390px]">
            <section class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4" aria-labelledby="dummy-alignment-preview-title">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 id="dummy-alignment-preview-title" class="text-base font-bold text-slate-900">Vista previa</h3>
                            <span id="dummy-alignment-current-type" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800">Dummy QR</span>
                        </div>
                        <p id="dummy-alignment-size-summary" class="mt-1 text-xs text-slate-600">Preparando las medidas de la etiqueta.</p>
                    </div>
                    <div class="flex gap-2" aria-label="Tipo de dummy">
                        <button data-dummy-alignment-type="rmt" type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">RMT</button>
                        <button data-dummy-alignment-type="rw" type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">RW</button>
                    </div>
                </div>

                <div id="dummy-alignment-canvas-viewport" class="mt-4 overflow-auto rounded-xl border border-slate-300 bg-slate-200/70 p-3 shadow-inner">
                    <canvas id="dummy-alignment-fabric-canvas" width="760" height="440" class="block max-w-none"></canvas>
                </div>

                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm border-2 border-blue-600 bg-blue-100"></span>Elemento que puedes mover</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm border border-dashed border-slate-500 bg-white"></span>Posición original</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm border-2 border-dashed border-amber-500 bg-amber-50"></span>Tamaño configurado</span>
                </div>
                <div id="dummy-alignment-canvas-status" class="mt-3 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-900" role="status">Cargando los elementos del dummy…</div>
            </section>

            <aside class="space-y-4">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="font-bold text-slate-900">¿Qué quieres mover?</h3>
                    <p class="mt-1 text-xs text-slate-500">Selecciona un elemento en la lista o en la vista previa. Haz clic en un espacio vacío o repite la selección para desmarcarlo.</p>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button data-dummy-alignment-element="title" type="button" class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-left hover:border-blue-300 hover:bg-blue-50">
                            <span class="block text-sm font-bold text-slate-900">Título</span>
                            <span class="mt-1 block text-xs text-slate-500">RMT / RW Dummy QR</span>
                        </button>
                        <button data-dummy-alignment-element="qr" type="button" class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-left hover:border-blue-300 hover:bg-blue-50">
                            <span class="block text-sm font-bold text-slate-900">Código QR</span>
                            <span class="mt-1 block text-xs text-slate-500">Contenido codificado</span>
                        </button>
                        <button data-dummy-alignment-element="fg" type="button" class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-left hover:border-blue-300 hover:bg-blue-50">
                            <span class="block text-sm font-bold text-slate-900">FG</span>
                            <span class="mt-1 block text-xs text-slate-500">Finished good</span>
                        </button>
                        <button data-dummy-alignment-element="job" type="button" class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-left hover:border-blue-300 hover:bg-blue-50">
                            <span class="block text-sm font-bold text-slate-900">Job</span>
                            <span class="mt-1 block text-xs text-slate-500">Número de trabajo</span>
                        </button>
                        <button data-dummy-alignment-element="consecutive" type="button" class="col-span-2 rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-left hover:border-blue-300 hover:bg-blue-50">
                            <span class="block text-sm font-bold text-slate-900">Consecutivo</span>
                            <span class="mt-1 block text-xs text-slate-500">Número de diez dígitos</span>
                        </button>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div>
                        <h3 class="font-bold text-slate-900">Mover <span id="dummy-alignment-selected-element">Título</span></h3>
                        <p class="mt-1 text-xs text-slate-500">Los valores positivos desplazan a la derecha o hacia abajo.</p>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-[170px_minmax(0,1fr)] lg:grid-cols-1 xl:grid-cols-[170px_minmax(0,1fr)]">
                        <div class="grid grid-cols-3 gap-2" aria-label="Controles de movimiento">
                            <span></span>
                            <button data-dummy-alignment-move="up" type="button" class="rounded-xl border border-slate-300 bg-slate-50 px-2 py-3 text-sm font-bold text-slate-800 hover:border-blue-400 hover:bg-blue-50">↑ Arriba</button>
                            <span></span>
                            <button data-dummy-alignment-move="left" type="button" class="rounded-xl border border-slate-300 bg-slate-50 px-2 py-3 text-sm font-bold text-slate-800 hover:border-blue-400 hover:bg-blue-50">← Izq.</button>
                            <div class="flex items-center justify-center rounded-xl bg-slate-900 px-2 text-center text-xs font-bold text-white"><span id="dummy-alignment-step-center">5 puntos</span></div>
                            <button data-dummy-alignment-move="right" type="button" class="rounded-xl border border-slate-300 bg-slate-50 px-2 py-3 text-sm font-bold text-slate-800 hover:border-blue-400 hover:bg-blue-50">Der. →</button>
                            <span></span>
                            <button data-dummy-alignment-move="down" type="button" class="rounded-xl border border-slate-300 bg-slate-50 px-2 py-3 text-sm font-bold text-slate-800 hover:border-blue-400 hover:bg-blue-50">↓ Abajo</button>
                            <span></span>
                        </div>

                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Cada toque mueve</div>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <button data-dummy-alignment-step="1" type="button" class="rounded-lg border px-2 py-2 text-xs font-semibold">1 · Fino</button>
                                <button data-dummy-alignment-step="5" type="button" class="rounded-lg border px-2 py-2 text-xs font-semibold">5 · Normal</button>
                                <button data-dummy-alignment-step="10" type="button" class="rounded-lg border px-2 py-2 text-xs font-semibold">10 · Rápido</button>
                            </div>
                            <div class="mt-3 rounded-xl bg-slate-50 p-3 text-sm">
                                <div id="dummy-alignment-horizontal-summary" class="font-semibold text-slate-800">Horizontal: sin ajuste</div>
                                <div id="dummy-alignment-vertical-summary" class="mt-1 font-semibold text-slate-800">Vertical: sin ajuste</div>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <button id="undo-dummy-alignment" type="button" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Deshacer</button>
                                <button id="reset-dummy-alignment-element" type="button" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Posición original</button>
                            </div>
                        </div>
                    </div>
                </section>

                <div id="dummy-alignment-printer-note" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Conecta y selecciona la impresora para guardar ajustes exclusivos para ese equipo.</div>

                <details class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-700">Opciones avanzadas: valores exactos</summary>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <label>
                            Horizontal
                            <input id="dummy-alignment-horizontal" type="number" step="1" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                        </label>
                        <label>
                            Vertical
                            <input id="dummy-alignment-vertical" type="number" step="1" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                        </label>
                    </div>
                </details>
            </aside>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <button id="reset-dummy-alignment-type" type="button" class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Restablecer este tipo</button>
            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <button id="cancel-dummy-alignment" type="button" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                <button id="save-dummy-alignment" type="button" class="rounded-xl border border-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-100">Guardar</button>
                <button id="save-test-dummy-alignment" type="button" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60">Guardar e imprimir prueba</button>
            </div>
        </div>
    </div>
</div>

<script id="dummy-print-center-config" type="application/json">{!! Illuminate\Support\Js::encode($printCenterConfig) !!}</script>
@endsection

@push('scripts')
    @vite('resources/js/pages/dummy-print-center.js')
@endpush
