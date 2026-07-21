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
            <li>Conecta tu impresora, elige si deseas probar <span class="font-semibold">Serial, Rating o ambas</span> y presiona <span class="font-semibold">Preparar impresion</span>.</li>
            <li>Si las pruebas salen correctas, presiona <span class="font-semibold">Imprimir ahora</span>; el sistema imprimira el bloque activo y cargara el siguiente automaticamente.</li>
            <li>Al finalizar todos los bloques, veras una confirmacion de impresion para saber que el sistema registro correctamente lo impreso.</li>
        </ol>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <button id="connect-printer" type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Conectar impresora</button>
        <label class="flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-900">
            Etiqueta de prueba
            <select id="prepare-label-type" class="rounded-lg border border-blue-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-800">
                <option value="all">Serial y Rating</option>
                <option value="serial">Solo Serial</option>
                <option value="rating">Solo Rating</option>
            </select>
        </label>
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



<div id="alignment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:p-5" role="dialog" aria-modal="true" aria-labelledby="alignment-modal-title">
    <div class="flex max-h-[94vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 id="alignment-modal-title" class="text-xl font-bold text-slate-900">Ajustar posición de la etiqueta</h3>
                    <span id="alignment-unsaved-badge" class="hidden rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Cambios sin guardar</span>
                </div>
                <p class="mt-1 text-sm text-slate-600">Selecciona un elemento y muévelo hasta que coincida con la etiqueta física.</p>
            </div>
            <button id="close-alignment-modal" type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 text-xl text-slate-500 hover:bg-slate-100 hover:text-slate-800" aria-label="Cerrar ajuste">&times;</button>
        </div>

        <div class="grid flex-1 gap-5 overflow-y-auto p-4 sm:p-6 lg:grid-cols-[minmax(0,1fr)_390px]">
            <section class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 p-3 sm:p-4" aria-labelledby="alignment-preview-title">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 id="alignment-preview-title" class="text-base font-bold text-slate-900">Vista previa</h4>
                            <span id="alignment-current-label" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800">Cargando etiqueta…</span>
                        </div>
                        <p id="alignment-size-summary" class="mt-1 text-xs text-slate-600">Estamos preparando las medidas de la etiqueta.</p>
                    </div>
                    <div id="alignment-type-switch" class="flex gap-2" aria-label="Tipo de etiqueta">
                        <button data-alignment-type="serial" type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">Serial</button>
                        <button data-alignment-type="rating" type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">Rating</button>
                    </div>
                </div>

                <div id="alignment-canvas-viewport" class="mt-4 overflow-auto rounded-xl border border-slate-300 bg-slate-200/70 p-3 shadow-inner">
                    <canvas id="alignment-fabric-canvas" width="760" height="440" class="block max-w-none"></canvas>
                </div>

                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-600">
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm border-2 border-blue-600 bg-blue-100"></span>Elemento que puedes mover</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm border border-dashed border-slate-500 bg-white"></span>Posición original</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm border-2 border-dashed border-amber-500 bg-amber-50"></span>Tamaño configurado</span>
                </div>
                <div id="alignment-canvas-status" class="mt-3 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-900" role="status">Cargando la etiqueta actual…</div>
            </section>

            <aside class="space-y-4">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">1</span>
                        <div>
                            <h4 class="font-bold text-slate-900">¿Qué quieres mover?</h4>
                            <p class="text-xs text-slate-500">Elige una opción; quedará marcada en azul.</p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button data-alignment-element="text" type="button" class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-left hover:border-blue-300 hover:bg-blue-50">
                            <span id="alignment-text-element-title" class="block text-sm font-bold text-slate-900">Serial y SKU</span>
                            <span id="alignment-text-element-help" class="mt-1 block text-xs text-slate-500">Mueve los textos juntos</span>
                        </button>
                        <button data-alignment-element="qr" type="button" class="rounded-xl border-2 border-slate-200 bg-white px-3 py-3 text-left hover:border-blue-300 hover:bg-blue-50">
                            <span class="block text-sm font-bold text-slate-900">Código QR</span>
                            <span class="mt-1 block text-xs text-slate-500">Mueve solamente el QR</span>
                        </button>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">2</span>
                        <div>
                            <h4 class="font-bold text-slate-900">Muévelo en la dirección necesaria</h4>
                            <p class="text-xs text-slate-500">También puedes arrastrarlo directamente en la vista previa.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-[170px_minmax(0,1fr)] lg:grid-cols-1 xl:grid-cols-[170px_minmax(0,1fr)]">
                        <div class="grid grid-cols-3 gap-2" aria-label="Controles de movimiento">
                            <span></span>
                            <button data-alignment-move="up" type="button" class="rounded-xl border border-slate-300 bg-slate-50 px-2 py-3 text-sm font-bold text-slate-800 hover:border-blue-400 hover:bg-blue-50">↑ Arriba</button>
                            <span></span>
                            <button data-alignment-move="left" type="button" class="rounded-xl border border-slate-300 bg-slate-50 px-2 py-3 text-sm font-bold text-slate-800 hover:border-blue-400 hover:bg-blue-50">← Izq.</button>
                            <div class="flex items-center justify-center rounded-xl bg-slate-900 px-2 text-center text-xs font-bold text-white"><span id="alignment-step-center">5 puntos</span></div>
                            <button data-alignment-move="right" type="button" class="rounded-xl border border-slate-300 bg-slate-50 px-2 py-3 text-sm font-bold text-slate-800 hover:border-blue-400 hover:bg-blue-50">Der. →</button>
                            <span></span>
                            <button data-alignment-move="down" type="button" class="rounded-xl border border-slate-300 bg-slate-50 px-2 py-3 text-sm font-bold text-slate-800 hover:border-blue-400 hover:bg-blue-50">↓ Abajo</button>
                            <span></span>
                        </div>

                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Cada toque mueve</div>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <button data-alignment-step="1" type="button" class="rounded-lg border px-2 py-2 text-xs font-semibold">1 · Fino</button>
                                <button data-alignment-step="5" type="button" class="rounded-lg border px-2 py-2 text-xs font-semibold">5 · Normal</button>
                                <button data-alignment-step="10" type="button" class="rounded-lg border px-2 py-2 text-xs font-semibold">10 · Rápido</button>
                            </div>
                            <div class="mt-3 rounded-xl bg-slate-50 p-3 text-sm">
                                <div id="alignment-horizontal-summary" class="font-semibold text-slate-800">Horizontal: sin ajuste</div>
                                <div id="alignment-vertical-summary" class="mt-1 font-semibold text-slate-800">Vertical: sin ajuste</div>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <button id="undo-alignment" type="button" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Deshacer</button>
                                <button id="reset-alignment-element" type="button" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Posición original</button>
                            </div>
                        </div>
                    </div>
                </section>

                <div id="alignment-printer-note" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Conecta y selecciona la impresora para guardar un ajuste exclusivo para ese equipo.</div>

                <details class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-700">Opciones avanzadas: valores exactos</summary>
                    <p class="mt-2 text-xs text-slate-500">Los valores positivos mueven a la derecha o hacia abajo.</p>
                    <div data-alignment-panel="serial" class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <label>Textos horizontal <input data-align="serial_text_x" type="number" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                        <label>Textos vertical <input data-align="serial_text_y" type="number" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                        <label>QR horizontal <input data-align="serial_qr_x" type="number" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                        <label>QR vertical <input data-align="serial_qr_y" type="number" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                    </div>
                    <div data-alignment-panel="rating" class="mt-3 hidden grid-cols-2 gap-3 text-sm">
                        <label>Textos horizontal <input data-align="rating_text_x" type="number" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                        <label>Textos vertical <input data-align="rating_text_y" type="number" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                        <label>QR horizontal <input data-align="rating_qr_x" type="number" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                        <label>QR vertical <input data-align="rating_qr_y" type="number" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2"></label>
                    </div>
                </details>
            </aside>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <button id="reset-alignment" type="button" class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Restablecer esta etiqueta</button>
            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <button id="cancel-alignment-modal" type="button" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                <button id="save-alignment" type="button" class="rounded-xl border border-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-100">Guardar</button>
                <button id="save-test-alignment" type="button" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-60">Guardar e imprimir prueba</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @vite('resources/js/pages/label-print-center.js')
@endpush
