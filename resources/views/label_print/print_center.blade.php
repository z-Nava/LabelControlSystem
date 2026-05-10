@extends('layouts.app', ['title' => 'Centro de impresión'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6" id="label-print-center"
     data-preview-url="{{ route('label_requests.print_batches.preview', ['label_request' => $labelRequest, 'batch' => $batch]) }}"
     data-confirm-url="{{ route('label_requests.print_batches.confirm', ['label_request' => $labelRequest, 'batch' => $batch]) }}"
     data-csrf-token="{{ csrf_token() }}"
     data-already-printed="{{ $batch->printed_at ? '1' : '0' }}"
     data-back-url="{{ route('label_requests.show', $labelRequest) }}">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Centro de impresión</h1>
            <p class="text-slate-600 mt-1">Requisición #{{ $labelRequest->id }} · Batch #{{ $batch->id }}</p>
        </div>
        <a href="{{ route('label_requests.show', $labelRequest) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al detalle</a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
        <h2 class="text-base font-semibold text-blue-900">¿Qué acabas de hacer y qué sigue?</h2>
        <ol class="mt-3 list-decimal list-inside space-y-2 text-sm text-blue-900">
            <li>Esta pantalla corresponde al <span class="font-semibold">batch #{{ $batch->id }}</span> de la requisición <span class="font-semibold">#{{ $labelRequest->id }}</span>.</li>
            <li>Conecta tu impresora y presiona <span class="font-semibold">“Preparar impresión”</span> para revisar el resumen del lote.</li>
            <li>Si los datos son correctos, presiona <span class="font-semibold">“Imprimir ahora”</span>.</li>
            <li>Al finalizar, verás una confirmación de impresión para saber que el sistema registró correctamente lo impreso.</li>
        </ol>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <button id="connect-printer" type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Conectar impresora</button>
        <button id="preview-batch" type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Preparar impresión</button>
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
            <div id="print-status" class="mt-1 text-sm text-slate-700">Pendiente de conexión y preparación de impresión.</div>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="text-xs uppercase tracking-wide text-emerald-700">Confirmación</div>
            <div id="print-confirmation" class="mt-1 text-sm text-emerald-800">Aún no hay impresión confirmada.</div>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 text-sm font-semibold text-slate-900">Resumen de lo que se imprimirá</div>
        <div id="preview-summary" class="p-4 text-sm text-slate-600">Aún no se ha preparado la impresión.</div>
    </div>
</div>



<div id="alignment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4">
    <div class="max-h-[92vh] w-full max-w-6xl overflow-y-auto rounded-2xl bg-white shadow-xl">
        <div class="border-b px-5 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Ajuste rápido de posiciones</h3>
            <p class="mt-1 text-sm text-slate-600">Si la etiqueta salió desfazada, conserva el ajuste manual por pixeles o arrastra libremente los elementos de la etiqueta con el editor visual.</p>
        </div>
        <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Editor visual con Fabric JS</div>
                        <p class="text-xs text-slate-600">Presiona “Cargar elementos” si aún no preparaste la impresión. Arrastra el QR o el grupo de textos; SKU y SN se mueven juntos.</p>
                    </div>
                    <div class="flex gap-2">
                        <button data-alignment-type="serial" type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Serial</button>
                        <button data-alignment-type="rating" type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">Rating</button>
                    </div>
                </div>
                <div class="mt-3 overflow-auto rounded-lg border border-slate-200 bg-white p-3">
                    <canvas id="alignment-fabric-canvas" width="640" height="360" class="max-w-full"></canvas>
                </div>
                <div id="alignment-canvas-status" class="mt-2 text-xs text-slate-600">El preview visual se cargará desde los ZPL de esta requisición.</div>
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

<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
<script>
(() => {
    const root = document.getElementById('label-print-center');
    if (!root) return;

    const connectButton = document.getElementById('connect-printer');
    const previewButton = document.getElementById('preview-batch');
    const printButton = document.getElementById('print-batch');
    const serialPrinterBox = document.getElementById('selected-printer-serial');
    const ratingPrinterBox = document.getElementById('selected-printer-rating');
    const serialPrinterSelect = document.getElementById('printer-select-serial');
    const ratingPrinterSelect = document.getElementById('printer-select-rating');
    const statusBox = document.getElementById('print-status');
    const confirmationBox = document.getElementById('print-confirmation');
    const previewSummary = document.getElementById('preview-summary');
    const alignmentModal = document.getElementById('alignment-modal');
    const openAlignmentModalButton = document.getElementById('open-alignment-modal');
    const closeAlignmentModalButton = document.getElementById('close-alignment-modal');
    const saveAlignmentButton = document.getElementById('save-alignment');
    const resetAlignmentButton = document.getElementById('reset-alignment');
    const alignmentCanvasElement = document.getElementById('alignment-fabric-canvas');
    const alignmentCanvasStatus = document.getElementById('alignment-canvas-status');
    const loadAlignmentPreviewButton = document.getElementById('load-alignment-preview');
    const alignmentTypeButtons = document.querySelectorAll('[data-alignment-type]');
    const storageKeys = {
        serial: 'label_print_selected_printer_serial',
        rating: 'label_print_selected_printer_rating',
        alignment: 'label_print_alignment_offsets',
    };

    let availablePrinters = [];
    let selectedPrinters = { serial: null, rating: null };
    let previewPayload = null;
    let printPrepared = false;
    let alignmentFabricCanvas = null;
    let currentAlignmentType = 'serial';
    let syncingCanvasFromInputs = false;

    const defaultAlignment = {
        serial_text_x: 0, serial_text_y: 0, serial_qr_x: 0, serial_qr_y: 0,
        rating_text_x: 0, rating_text_y: 0, rating_qr_x: 0, rating_qr_y: 0,
    };

    const getAlignment = () => {
        try {
            return { ...defaultAlignment, ...(JSON.parse(localStorage.getItem(storageKeys.alignment) || '{}')) };
        } catch (_error) {
            return { ...defaultAlignment };
        }
    };

    const setAlignmentInputs = (values) => {
        document.querySelectorAll('[data-align]').forEach((input) => {
            input.value = values[input.dataset.align] ?? 0;
        });
    };

    const readAlignmentInputs = () => {
        const values = { ...defaultAlignment };
        document.querySelectorAll('[data-align]').forEach((input) => {
            values[input.dataset.align] = Number(input.value || 0);
        });
        return values;
    };

    const setAlignmentCanvasStatus = (message, isError = false) => {
        if (!alignmentCanvasStatus) return;

        alignmentCanvasStatus.textContent = message;
        alignmentCanvasStatus.classList.toggle('text-red-700', isError);
        alignmentCanvasStatus.classList.toggle('text-slate-600', !isError);
    };

    const setActiveAlignmentType = (labelType) => {
        currentAlignmentType = ['serial', 'rating'].includes(labelType) ? labelType : 'serial';
        alignmentTypeButtons.forEach((button) => {
            const isActive = button.dataset.alignmentType === currentAlignmentType;
            button.classList.toggle('bg-slate-900', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('border-slate-900', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('text-slate-700', !isActive);
        });
    };

    const ensureAlignmentCanvas = () => {
        if (alignmentFabricCanvas || !alignmentCanvasElement) {
            return alignmentFabricCanvas;
        }

        if (!window.fabric?.Canvas) {
            setAlignmentCanvasStatus('Fabric JS aún no está disponible. Espera un momento y vuelve a abrir el modal.', true);
            return null;
        }

        alignmentFabricCanvas = new window.fabric.Canvas(alignmentCanvasElement, {
            backgroundColor: '#f8fafc',
            preserveObjectStacking: true,
            selection: true,
        });

        alignmentFabricCanvas.on('object:moving', (event) => syncAlignmentFromCanvasObject(event.target));
        alignmentFabricCanvas.on('object:modified', (event) => syncAlignmentFromCanvasObject(event.target));

        return alignmentFabricCanvas;
    };

    const parseZplElements = (zpl) => {
        const elements = [];
        const blocks = String(zpl || '').match(/\^FO-?\d+,-?\d+[\s\S]*?\^FS/g) || [];

        blocks.forEach((block, index) => {
            const position = block.match(/\^FO(-?\d+),(-?\d+)/);
            if (!position) return;

            const x = Number(position[1]);
            const y = Number(position[2]);
            const isQr = block.includes('^BQN');
            const fieldData = block.match(/\^FD([\s\S]*?)\^FS/)?.[1] || (isQr ? 'QR' : 'Texto');
            const font = block.match(/\^A0[A-Z]?,(\d+),(\d+)/i);
            const qrMag = Number(block.match(/\^BQ[A-Z]?,\d+,(\d+)/i)?.[1] || 4);
            const fontSize = Math.max(14, Math.min(72, Number(font?.[1] || 24)));

            elements.push({
                x,
                y,
                kind: isQr ? 'qr' : 'text',
                label: isQr ? `QR ${index + 1}` : fieldData.replace(/^LA,/, '').slice(0, 34),
                width: isQr ? Math.max(76, qrMag * 34) : Math.max(90, fieldData.length * (fontSize * 0.55)),
                height: isQr ? Math.max(76, qrMag * 34) : fontSize + 16,
                fontSize,
            });
        });

        return elements;
    };

    const getDocumentForAlignmentType = (labelType) => (previewPayload?.documents || [])
        .find((doc) => String(doc.label_type || '').toLowerCase() === labelType);

    const getAlignmentFieldForObject = (object) => {
        const objectType = object?.data?.kind === 'qr' ? 'qr' : 'text';
        return {
            x: `${object.data.labelType}_${objectType}_x`,
            y: `${object.data.labelType}_${objectType}_y`,
        };
    };

    const syncAlignmentFromCanvasObject = (object) => {
        if (syncingCanvasFromInputs || !object?.data?.basePoint || !object?.data?.scale) return;

        const fields = getAlignmentFieldForObject(object);
        const nextX = Math.round((object.left - object.data.basePoint.left) / object.data.scale);
        const nextY = Math.round((object.top - object.data.basePoint.top) / object.data.scale);
        const current = readAlignmentInputs();

        current[fields.x] = nextX;
        current[fields.y] = nextY;
        setAlignmentInputs(current);
    };

    const createAlignmentObject = (element, labelType, metrics, offsets) => {
        const { Rect } = window.fabric || {};
        if (!Rect) return null;

        const offsetX = offsets[`${labelType}_qr_x`];
        const offsetY = offsets[`${labelType}_qr_y`];
        const left = metrics.padding + ((element.x + Number(offsetX || 0)) * metrics.scale);
        const top = metrics.padding + ((element.y + Number(offsetY || 0)) * metrics.scale);
        const basePoint = {
            left: metrics.padding + (element.x * metrics.scale),
            top: metrics.padding + (element.y * metrics.scale),
        };

        return new Rect({
            left,
            top,
            width: element.width * metrics.scale,
            height: element.height * metrics.scale,
            fill: '#dbeafe',
            stroke: '#2563eb',
            strokeWidth: 2,
            strokeDashArray: [6, 4],
            data: { labelType, kind: element.kind, basePoint, scale: metrics.scale },
        });
    };

    const createAlignmentTextGroup = (textElements, labelType, metrics, offsets) => {
        const { Group, Text } = window.fabric || {};
        if (!Group || !Text || !textElements.length) return null;

        const minX = Math.min(...textElements.map((element) => element.x));
        const minY = Math.min(...textElements.map((element) => element.y));
        const offsetX = Number(offsets[`${labelType}_text_x`] || 0);
        const offsetY = Number(offsets[`${labelType}_text_y`] || 0);
        const basePoint = {
            left: metrics.padding + (minX * metrics.scale),
            top: metrics.padding + (minY * metrics.scale),
        };

        const textObjects = textElements.map((element) => new Text(element.label || 'Texto', {
            left: (element.x - minX) * metrics.scale,
            top: (element.y - minY) * metrics.scale,
            fontSize: Math.max(12, element.fontSize * metrics.scale),
            fontFamily: 'Arial',
            fill: '#0f172a',
            backgroundColor: 'rgba(254, 243, 199, 0.9)',
            padding: 6,
            selectable: false,
            evented: false,
        }));

        return new Group(textObjects, {
            left: basePoint.left + (offsetX * metrics.scale),
            top: basePoint.top + (offsetY * metrics.scale),
            subTargetCheck: false,
            data: { labelType, kind: 'text', basePoint, scale: metrics.scale },
        });
    };

    const renderAlignmentCanvas = (labelType = currentAlignmentType) => {
        const canvas = ensureAlignmentCanvas();
        if (!canvas) return;

        setActiveAlignmentType(labelType);
        canvas.clear();
        canvas.backgroundColor = '#f8fafc';

        const documentForType = getDocumentForAlignmentType(currentAlignmentType);
        if (!documentForType?.test_zpl) {
            setAlignmentCanvasStatus(`No hay elementos ${currentAlignmentType.toUpperCase()} cargados. Presiona “Preparar impresión” o “Cargar elementos”.`, true);
            canvas.renderAll();
            return;
        }

        const elements = parseZplElements(documentForType.test_zpl);
        if (!elements.length) {
            setAlignmentCanvasStatus(`El ZPL ${currentAlignmentType.toUpperCase()} no tiene bloques ^FO para editar visualmente.`, true);
            canvas.renderAll();
            return;
        }

        const padding = 28;
        const maxX = Math.max(...elements.map((element) => element.x + element.width), 320);
        const maxY = Math.max(...elements.map((element) => element.y + element.height), 180);
        const scale = Math.min((canvas.getWidth() - padding * 2) / maxX, (canvas.getHeight() - padding * 2) / maxY, 1.6);
        const metrics = { padding, scale };
        const { Rect, Text } = window.fabric || {};

        canvas.add(new Rect({
            left: padding,
            top: padding,
            width: maxX * scale,
            height: maxY * scale,
            fill: '#ffffff',
            stroke: '#cbd5e1',
            strokeWidth: 1,
            selectable: false,
            evented: false,
        }));

        canvas.add(new Text(`Etiqueta ${currentAlignmentType.toUpperCase()} · escala ${scale.toFixed(2)}x`, {
            left: padding,
            top: 8,
            fontSize: 12,
            fill: '#64748b',
            selectable: false,
            evented: false,
        }));

        const offsets = readAlignmentInputs();
        syncingCanvasFromInputs = true;
        elements.filter((element) => element.kind === 'qr').forEach((element) => {
            const object = createAlignmentObject(element, currentAlignmentType, metrics, offsets);
            if (object) {
                object.set({ cornerColor: '#dc2626', borderColor: '#dc2626' });
                canvas.add(object);
            }
        });

        const textGroup = createAlignmentTextGroup(
            elements.filter((element) => element.kind === 'text'),
            currentAlignmentType,
            metrics,
            offsets,
        );
        if (textGroup) {
            textGroup.set({ cornerColor: '#dc2626', borderColor: '#dc2626' });
            canvas.add(textGroup);
        }
        syncingCanvasFromInputs = false;

        canvas.renderAll();
        setAlignmentCanvasStatus(`Elementos ${currentAlignmentType.toUpperCase()} cargados desde el ZPL de esta requisición. Arrastra el QR o el grupo de textos para moverlos libremente.`);
    };

    const refreshAlignmentPreview = async () => {
        try {
            if (!previewPayload?.documents?.length) {
                await loadPreview();
            }

            const availableTypes = (previewPayload?.documents || []).map((doc) => String(doc.label_type || '').toLowerCase());
            const nextType = availableTypes.includes(currentAlignmentType) ? currentAlignmentType : (availableTypes[0] || 'serial');
            renderAlignmentCanvas(nextType);
        } catch (error) {
            setAlignmentCanvasStatus(`No se pudieron cargar elementos para el editor visual: ${error.message}`, true);
        }
    };

    const moveFO = (block, dx, dy) => block.replace(/\^FO(-?\d+),(-?\d+)/, (_m, x, y) => `^FO${Number(x)+dx},${Number(y)+dy}`);

    const applyAlignmentToZpl = (zpl, labelType) => {
        const a = getAlignment();
        const textDx = labelType === 'rating' ? a.rating_text_x : a.serial_text_x;
        const textDy = labelType === 'rating' ? a.rating_text_y : a.serial_text_y;
        const qrDx = labelType === 'rating' ? a.rating_qr_x : a.serial_qr_x;
        const qrDy = labelType === 'rating' ? a.rating_qr_y : a.serial_qr_y;

        return zpl.replace(/(\^FO-?\d+,-?\d+[\s\S]*?\^FS)/g, (block) => {
            if (block.includes('^BQN')) return moveFO(block, qrDx, qrDy);
            return moveFO(block, textDx, textDy);
        });
    };


    const csrfToken = root.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

    const setStatus = (message, isError = false) => {
        statusBox.textContent = message;
        statusBox.classList.toggle('text-red-700', isError);
    };

    const setPrintBlocked = (message) => {
        if (printButton) {
            printButton.disabled = true;
            printButton.title = message;
            printButton.classList.add('cursor-not-allowed', 'opacity-60');
        }

        printPrepared = false;
        setStatus(message, true);
    };

    const showAlert = (title, text, icon = 'error') => {
        if (window.Swal?.fire) {
            window.Swal.fire(title, text, icon);
            return;
        }

        window.alert(`${title}: ${text}`);
    };

    const getPrinterId = (device) => [device?.name || '', device?.uid || '', device?.connection || ''].join('::');
    const getPrinterLabel = (device) => `${device?.name || 'Sin nombre'} (${device?.connection || 'connection'})`;

    const persistSelectedPrinter = (labelType, device) => {
        localStorage.setItem(storageKeys[labelType], JSON.stringify({
            name: device.name,
            uid: device.uid,
            connection: device.connection,
        }));
    };

    const setSelectedPrinter = (labelType, device) => {
        if (!['serial', 'rating'].includes(labelType)) return;

        selectedPrinters[labelType] = device || null;

        if (labelType === 'serial' && serialPrinterBox) {
            serialPrinterBox.textContent = device ? getPrinterLabel(device) : 'Sin seleccionar';
        }

        if (labelType === 'rating' && ratingPrinterBox) {
            ratingPrinterBox.textContent = device ? getPrinterLabel(device) : 'Sin seleccionar';
        }

        if (device) {
            persistSelectedPrinter(labelType, device);
        }
    };

    const restorePreferredPrinter = (labelType) => {
        const raw = localStorage.getItem(storageKeys[labelType]);
        if (!raw) return;

        try {
            const parsed = JSON.parse(raw);
            return availablePrinters.find((printer) => {
                return (printer.name || '') === (parsed.name || '')
                    && (printer.uid || '') === (parsed.uid || '')
                    && (printer.connection || '') === (parsed.connection || '');
            }) || null;
        } catch (_error) {
            localStorage.removeItem(storageKeys[labelType]);
            return null;
        }
    };

    const renderPrinterOptions = (selectElement, preferredPrinter) => {
        if (!selectElement) return;

        selectElement.innerHTML = '';

        if (!availablePrinters.length) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No se detectaron impresoras';
            selectElement.appendChild(option);
            selectElement.value = '';
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecciona una impresora';
        selectElement.appendChild(placeholder);

        availablePrinters.forEach((printer) => {
            const option = document.createElement('option');
            option.value = getPrinterId(printer);
            option.textContent = getPrinterLabel(printer);
            selectElement.appendChild(option);
        });

        if (preferredPrinter) {
            selectElement.value = getPrinterId(preferredPrinter);
        } else {
            selectElement.value = '';
        }
    };

    const resolveSelectedPrinter = (labelType) => {
        if (selectedPrinters[labelType]) {
            return selectedPrinters[labelType];
        }

        if (selectedPrinters.serial && selectedPrinters.rating && getPrinterId(selectedPrinters.serial) === getPrinterId(selectedPrinters.rating)) {
            return selectedPrinters.serial;
        }

        return null;
    };

    const validatePrintersForDocuments = (documents = []) => {
        const requiredTypes = [...new Set(documents.map((doc) => String(doc.label_type || '').toLowerCase()).filter(Boolean))];

        for (const type of requiredTypes) {
            if (!['serial', 'rating'].includes(type)) {
                continue;
            }

            if (!resolveSelectedPrinter(type)) {
                throw new Error(`Debes seleccionar impresora para ${type.toUpperCase()}.`);
            }
        }
    };

    const connectPrinter = () => {
        if (!window.BrowserPrint) {
            setStatus('No se encontró BrowserPrint. Instala/abre Zebra Browser Print.', true);
            return;
        }

        setStatus('Buscando impresoras Zebra...');

        BrowserPrint.getLocalDevices((devices) => {
            availablePrinters = (devices || []).filter((candidate) => candidate.deviceType === 'printer');
            printPrepared = false;
            previewPayload = null;

            const preferredSerial = restorePreferredPrinter('serial');
            const preferredRating = restorePreferredPrinter('rating');
            renderPrinterOptions(serialPrinterSelect, preferredSerial);
            renderPrinterOptions(ratingPrinterSelect, preferredRating);

            setSelectedPrinter('serial', preferredSerial);
            setSelectedPrinter('rating', preferredRating);

            if (!availablePrinters.length) {
                setStatus('No se detectaron impresoras locales.', true);
                return;
            }

            if (availablePrinters.length === 1) {
                const onlyPrinter = availablePrinters[0];
                if (!selectedPrinters.serial) {
                    if (serialPrinterSelect) serialPrinterSelect.value = getPrinterId(onlyPrinter);
                    setSelectedPrinter('serial', onlyPrinter);
                }

                if (!selectedPrinters.rating) {
                    if (ratingPrinterSelect) ratingPrinterSelect.value = getPrinterId(onlyPrinter);
                    setSelectedPrinter('rating', onlyPrinter);
                }
            }

            const serialReady = Boolean(selectedPrinters.serial);
            const ratingReady = Boolean(selectedPrinters.rating);
            setStatus(`Se detectaron ${availablePrinters.length} impresora(s). Serial: ${serialReady ? 'OK' : 'pendiente'} · Rating: ${ratingReady ? 'OK' : 'pendiente'}.`);
        }, (error) => {
            setStatus(`Error al conectar impresora: ${error}`, true);
        }, 'printer');
    };

    const sendToPrinter = (device, zplChunk) => new Promise((resolve, reject) => {
        device.send(zplChunk, () => resolve(), (error) => reject(new Error(error)));
    });

    const loadPreview = async () => {
        setStatus('Preparando información de impresión...');

        const response = await fetch(root.dataset.previewUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({}),
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || 'No se pudo preparar la impresión.');
        }

        previewPayload = await response.json();

        const lines = (previewPayload.documents || []).map((doc) => {
            return `Tipo de etiqueta: ${doc.label_type} · Cantidad: ${doc.units_count}`;
        });

        previewSummary.textContent = lines.join('\n') || 'No hay documentos para este lote.';
        setStatus('Preparación completada. Revisa el resumen y presiona "Imprimir ahora".');
    };

    const preparePrint = async () => {
        try {
            if (!availablePrinters.length) {
                setStatus('Primero conecta impresoras.', true);
                showAlert('Impresora requerida', 'Conecta una impresora antes de preparar la impresión.', 'error');
                return;
            }

            await loadPreview();
            const documents = previewPayload?.documents || [];
            validatePrintersForDocuments(documents);

            const testDocs = documents.filter((doc) => doc?.test_zpl);
            if (!testDocs.length) {
                printPrepared = false;
                setStatus('No hay contenido de prueba para imprimir.', true);
                showAlert('Sin contenido', 'No se encontró contenido ZPL para la impresión de prueba.', 'warning');
                return;
            }

            setStatus('Enviando etiqueta(s) de prueba para validar template...');
            for (const doc of testDocs) {
                const labelType = String(doc.label_type || '').toLowerCase();
                const printer = resolveSelectedPrinter(labelType);
                if (!printer) {
                    throw new Error(`No hay impresora seleccionada para ${labelType.toUpperCase()}.`);
                }

                const adjustedTestZpl = applyAlignmentToZpl(doc.test_zpl, labelType);
                await sendToPrinter(printer, adjustedTestZpl);
            }

            printPrepared = true;
            setStatus('Impresión de prueba enviada (1 etiqueta por tipo). Si todo está correcto, ya puedes presionar "Imprimir ahora".');
            showAlert('Preparación completada', 'Se envió 1 etiqueta de prueba por tipo (serial/rating) a la impresora seleccionada. Si están correctas, ya puedes imprimir el lote completo.', 'success');
        } catch (error) {
            printPrepared = false;
            setStatus(`Error al preparar impresión: ${error.message}`, true);
            showAlert('Error de preparación', error.message || 'No se pudo enviar la impresión de prueba.', 'error');
        }
    };

    const confirmPrinted = async () => {
        const response = await fetch(root.dataset.confirmUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ printed_ok: true }),
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || 'No se pudo confirmar impresión.');
        }

        return response.json();
    };

    const printBatch = async () => {
        try {
            if (printButton?.disabled) {
                return;
            }

            if (!availablePrinters.length) {
                setStatus('Primero conecta impresoras.', true);
                return;
            }

            if (!printPrepared) {
                showAlert('Preparación requerida', 'Debes presionar "Preparar impresión" antes de imprimir.', 'error');
                setStatus('Debes preparar la impresión primero para liberar el botón de imprimir.', true);
                return;
            }

            if (!previewPayload || !previewPayload.documents) {
                setStatus('No hay preparación activa. Presiona "Preparar impresión" nuevamente.', true);
                printPrepared = false;
                return;
            }

            const documents = (previewPayload.documents || []).filter((doc) => doc?.zpl);
            if (!documents.length) {
                setStatus('No hay contenido listo para imprimir.', true);
                return;
            }

            validatePrintersForDocuments(documents);

            setStatus(`Enviando ${documents.length} documento(s) a las impresoras seleccionadas...`);

            for (const doc of documents) {
                const labelType = String(doc.label_type || '').toLowerCase();
                const printer = resolveSelectedPrinter(labelType);
                if (!printer) {
                    throw new Error(`No hay impresora seleccionada para ${labelType.toUpperCase()}.`);
                }

                const adjustedZpl = applyAlignmentToZpl(doc.zpl, labelType);
                await sendToPrinter(printer, adjustedZpl);
            }

            try {
                const result = await confirmPrinted();
                setPrintBlocked(result.message || 'Impresión confirmada. Botón bloqueado para evitar duplicidad.');
                if (confirmationBox) {
                    const printedAt = result.printed_at ? ` · Confirmado en: ${result.printed_at}` : '';
                    confirmationBox.textContent = `Impresión confirmada para el batch #${result.batch_id}. Seriales actualizados: ${result.updated_serial_units}.${printedAt}`;
                }
            } catch (error) {
                setStatus(`Impreso localmente, pero falló confirmación backend: ${error.message}`, true);
            }
        } catch (error) {
            setStatus(`Error en impresión: ${error.message}`, true);
        }
    };

    connectButton?.addEventListener('click', connectPrinter);
    serialPrinterSelect?.addEventListener('change', (event) => {
        const selectedId = event.target.value;
        const device = availablePrinters.find((printer) => getPrinterId(printer) === selectedId) || null;
        setSelectedPrinter('serial', device);
        printPrepared = false;
        if (device) {
            setStatus('Impresora SERIAL seleccionada. Prepara impresión nuevamente para validar.');
            return;
        }

        setStatus('Selecciona una impresora para SERIAL.', true);
    });
    ratingPrinterSelect?.addEventListener('change', (event) => {
        const selectedId = event.target.value;
        const device = availablePrinters.find((printer) => getPrinterId(printer) === selectedId) || null;
        setSelectedPrinter('rating', device);
        printPrepared = false;
        if (device) {
            setStatus('Impresora RATING seleccionada. Prepara impresión nuevamente para validar.');
            return;
        }

        setStatus('Selecciona una impresora para RATING.', true);
    });
    previewButton?.addEventListener('click', preparePrint);
    openAlignmentModalButton?.addEventListener('click', () => {
        setAlignmentInputs(getAlignment());
        alignmentModal?.classList.remove('hidden');
        alignmentModal?.classList.add('flex');
        setActiveAlignmentType(currentAlignmentType);
        window.requestAnimationFrame(() => renderAlignmentCanvas(currentAlignmentType));
    });
    closeAlignmentModalButton?.addEventListener('click', () => {
        alignmentModal?.classList.add('hidden');
        alignmentModal?.classList.remove('flex');
    });
    saveAlignmentButton?.addEventListener('click', () => {
        const values = readAlignmentInputs();
        localStorage.setItem(storageKeys.alignment, JSON.stringify(values));
        alignmentModal?.classList.add('hidden');
        alignmentModal?.classList.remove('flex');
        printPrepared = false;
        setStatus('Ajustes guardados. Vuelve a preparar impresión para validar posiciones.');
    });
    resetAlignmentButton?.addEventListener('click', () => {
        localStorage.setItem(storageKeys.alignment, JSON.stringify(defaultAlignment));
        setAlignmentInputs(defaultAlignment);
        renderAlignmentCanvas(currentAlignmentType);
    });
    loadAlignmentPreviewButton?.addEventListener('click', refreshAlignmentPreview);
    alignmentTypeButtons.forEach((button) => {
        button.addEventListener('click', () => renderAlignmentCanvas(button.dataset.alignmentType));
    });
    document.querySelectorAll('[data-align]').forEach((input) => {
        input.addEventListener('input', () => renderAlignmentCanvas(currentAlignmentType));
    });
    printButton?.addEventListener('click', printBatch);

    setSelectedPrinter('serial', null);
    setSelectedPrinter('rating', null);
    setActiveAlignmentType('serial');

    if (root.dataset.alreadyPrinted === '1') {
        setPrintBlocked('Este batch ya fue confirmado como impreso. El botón se bloqueó para evitar duplicidad.');
        if (confirmationBox) {
            confirmationBox.textContent = 'Este batch ya cuenta con confirmación de impresión previa.';
        }
    }
})();
</script>
@endsection
