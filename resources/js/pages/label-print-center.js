import Swal from '../lib/sweetalert';
import { Canvas, Group, Rect, Text } from '../lib/fabric-setup';

(() => {
    const root = document.getElementById('label-print-center');
    if (!root) return;

    const connectButton = document.getElementById('connect-printer');
    const previewButton = document.getElementById('preview-batch');
    const prepareLabelTypeSelect = document.getElementById('prepare-label-type');
    const printButton = document.getElementById('print-batch');
    const serialPrinterBox = document.getElementById('selected-printer-serial');
    const ratingPrinterBox = document.getElementById('selected-printer-rating');
    const serialPrinterSelect = document.getElementById('printer-select-serial');
    const ratingPrinterSelect = document.getElementById('printer-select-rating');
    const statusBox = document.getElementById('print-status');
    const confirmationBox = document.getElementById('print-confirmation');
    const previewSummary = document.getElementById('preview-summary');
    const blockProgress = document.getElementById('block-progress');
    const blockProgressSummary = document.getElementById('block-progress-summary');
    const blockCurrentMessage = document.getElementById('block-current-message');
    const alignmentModal = document.getElementById('alignment-modal');
    const openAlignmentModalButton = document.getElementById('open-alignment-modal');
    const closeAlignmentModalButton = document.getElementById('close-alignment-modal');
    const cancelAlignmentModalButton = document.getElementById('cancel-alignment-modal');
    const saveAlignmentButton = document.getElementById('save-alignment');
    const saveTestAlignmentButton = document.getElementById('save-test-alignment');
    const resetAlignmentButton = document.getElementById('reset-alignment');
    const resetAlignmentElementButton = document.getElementById('reset-alignment-element');
    const undoAlignmentButton = document.getElementById('undo-alignment');
    const alignmentCanvasElement = document.getElementById('alignment-fabric-canvas');
    const alignmentCanvasViewport = document.getElementById('alignment-canvas-viewport');
    const alignmentCanvasStatus = document.getElementById('alignment-canvas-status');
    const alignmentCurrentLabel = document.getElementById('alignment-current-label');
    const alignmentSizeSummary = document.getElementById('alignment-size-summary');
    const alignmentTypeSwitch = document.getElementById('alignment-type-switch');
    const alignmentUnsavedBadge = document.getElementById('alignment-unsaved-badge');
    const alignmentPrinterNote = document.getElementById('alignment-printer-note');
    const alignmentHorizontalSummary = document.getElementById('alignment-horizontal-summary');
    const alignmentVerticalSummary = document.getElementById('alignment-vertical-summary');
    const alignmentStepCenter = document.getElementById('alignment-step-center');
    const alignmentTextElementTitle = document.getElementById('alignment-text-element-title');
    const alignmentTextElementHelp = document.getElementById('alignment-text-element-help');
    const alignmentTypeButtons = document.querySelectorAll('[data-alignment-type]');
    const alignmentElementButtons = document.querySelectorAll('[data-alignment-element]');
    const alignmentMoveButtons = document.querySelectorAll('[data-alignment-move]');
    const alignmentStepButtons = document.querySelectorAll('[data-alignment-step]');
    const alignmentPanels = document.querySelectorAll('[data-alignment-panel]');
    const storageKeys = {
        serial: 'label_print_selected_printer_serial',
        rating: 'label_print_selected_printer_rating',
        alignmentLegacy: 'label_print_alignment_offsets',
        alignmentByPrinter: 'label_print_alignment_offsets_v2',
    };

    let availablePrinters = [];
    let selectedPrinters = { serial: null, rating: null };
    let previewPayload = null;
    let activeBlockId = null;
    let printPrepared = false;
    const preparedLabelTypes = new Set();
    let alignmentFabricCanvas = null;
    let currentAlignmentType = 'serial';
    let currentAlignmentElement = 'text';
    let alignmentStep = 5;
    let availableAlignmentElements = ['text'];
    let alignmentHistory = [];
    let alignmentDirty = false;
    const dirtyAlignmentTypes = new Set();
    let alignmentDragSnapshot = null;
    let syncingCanvasFromInputs = false;

    const defaultAlignment = {
        serial_text_x: 0, serial_text_y: 0, serial_qr_x: 0, serial_qr_y: 0,
        rating_text_x: 0, rating_text_y: 0, rating_qr_x: 0, rating_qr_y: 0,
    };

    const parseStoredJson = (key, fallback = {}) => {
        try {
            return JSON.parse(localStorage.getItem(key) || JSON.stringify(fallback));
        } catch (_error) {
            return fallback;
        }
    };

    const parseStoredJsonFromValue = (value) => {
        if (!value) return null;
        try {
            return JSON.parse(value);
        } catch (_error) {
            return null;
        }
    };

    const alignmentPrinterKey = (labelType) => {
        const device = selectedPrinters[labelType];
        if (!device) return null;

        return [device.name || '', device.uid || '', device.connection || ''].join('::');
    };

    const getAlignment = () => {
        const values = { ...defaultAlignment };
        const legacy = parseStoredJson(storageKeys.alignmentLegacy);
        const store = parseStoredJson(storageKeys.alignmentByPrinter, { version: 2, printers: {} });

        ['serial', 'rating'].forEach((labelType) => {
            const printerKey = alignmentPrinterKey(labelType);
            const saved = printerKey ? store?.printers?.[printerKey]?.[labelType] : null;

            ['text_x', 'text_y', 'qr_x', 'qr_y'].forEach((field) => {
                const fullField = `${labelType}_${field}`;
                values[fullField] = Number(saved?.[field] ?? legacy?.[fullField] ?? 0);
            });
        });

        return values;
    };

    const persistAlignmentForCurrentPrinter = (values, labelType = currentAlignmentType) => {
        const printerKey = alignmentPrinterKey(labelType);
        if (!printerKey) return false;

        const store = parseStoredJson(storageKeys.alignmentByPrinter, { version: 2, printers: {} });
        store.version = 2;
        store.printers = store.printers || {};
        store.printers[printerKey] = store.printers[printerKey] || {};
        store.printers[printerKey][labelType] = {
            text_x: Number(values[`${labelType}_text_x`] || 0),
            text_y: Number(values[`${labelType}_text_y`] || 0),
            qr_x: Number(values[`${labelType}_qr_x`] || 0),
            qr_y: Number(values[`${labelType}_qr_y`] || 0),
        };
        localStorage.setItem(storageKeys.alignmentByPrinter, JSON.stringify(store));

        return true;
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
        alignmentCanvasStatus.className = isError
            ? 'mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800'
            : 'mt-3 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-900';
    };

    const setAlignmentDirty = (isDirty = true, labelType = currentAlignmentType) => {
        if (isDirty && labelType) {
            dirtyAlignmentTypes.add(labelType);
        } else if (labelType === null) {
            dirtyAlignmentTypes.clear();
        } else if (labelType) {
            dirtyAlignmentTypes.delete(labelType);
        }

        alignmentDirty = dirtyAlignmentTypes.size > 0;
        alignmentUnsavedBadge?.classList.toggle('hidden', !alignmentDirty);
    };

    const setActiveAlignmentElement = (elementType) => {
        currentAlignmentElement = availableAlignmentElements.includes(elementType)
            ? elementType
            : (availableAlignmentElements[0] || 'text');

        alignmentElementButtons.forEach((button) => {
            const isAvailable = availableAlignmentElements.includes(button.dataset.alignmentElement);
            const isActive = button.dataset.alignmentElement === currentAlignmentElement;
            button.classList.toggle('hidden', !isAvailable);
            button.classList.toggle('border-blue-600', isActive);
            button.classList.toggle('bg-blue-50', isActive);
            button.classList.toggle('ring-2', isActive);
            button.classList.toggle('ring-blue-100', isActive);
            button.classList.toggle('border-slate-200', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    const updateAlignmentPrinterNote = () => {
        if (!alignmentPrinterNote) return;

        const printer = selectedPrinters[currentAlignmentType];
        if (!printer) {
            alignmentPrinterNote.className = 'rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900';
            alignmentPrinterNote.textContent = 'Conecta y selecciona la impresora para guardar un ajuste exclusivo para ese equipo.';
            return;
        }

        alignmentPrinterNote.className = 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900';
        alignmentPrinterNote.textContent = `Este ajuste se guardará solamente para ${printer.name || 'la impresora seleccionada'}.`;
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

        alignmentPanels.forEach((panel) => {
            const isActive = panel.dataset.alignmentPanel === currentAlignmentType;
            panel.classList.toggle('hidden', !isActive);
            panel.classList.toggle('grid', isActive);
        });

        if (alignmentCurrentLabel) {
            alignmentCurrentLabel.textContent = `Etiqueta ${currentAlignmentType.toUpperCase()}`;
        }
        if (alignmentTextElementTitle) {
            alignmentTextElementTitle.textContent = currentAlignmentType === 'rating'
                ? 'Texto del Rating'
                : 'Serial y SKU';
        }
        if (alignmentTextElementHelp) {
            alignmentTextElementHelp.textContent = currentAlignmentType === 'rating'
                ? 'Mueve el número serial del Rating'
                : 'Mueve los textos juntos';
        }
        updateAlignmentPrinterNote();
    };

    const ensureAlignmentCanvas = () => {
        if (alignmentFabricCanvas || !alignmentCanvasElement) {
            return alignmentFabricCanvas;
        }

        alignmentFabricCanvas = new Canvas(alignmentCanvasElement, {
            backgroundColor: '#e2e8f0',
            preserveObjectStacking: true,
            selection: false,
        });

        alignmentFabricCanvas.on('before:transform', () => {
            alignmentDragSnapshot = readAlignmentInputs();
        });
        alignmentFabricCanvas.on('object:moving', (event) => syncAlignmentFromCanvasObject(event.target));
        alignmentFabricCanvas.on('object:modified', (event) => {
            if (alignmentDragSnapshot) {
                alignmentHistory.push(alignmentDragSnapshot);
                alignmentHistory = alignmentHistory.slice(-30);
            }
            alignmentDragSnapshot = null;
            syncAlignmentFromCanvasObject(event.target);
            window.requestAnimationFrame(() => renderAlignmentCanvas(currentAlignmentType));
        });
        alignmentFabricCanvas.on('selection:created', (event) => {
            setActiveAlignmentElement(event.selected?.[0]?.data?.kind || currentAlignmentElement);
            updateAlignmentMovementSummary();
        });
        alignmentFabricCanvas.on('selection:updated', (event) => {
            setActiveAlignmentElement(event.selected?.[0]?.data?.kind || currentAlignmentElement);
            updateAlignmentMovementSummary();
        });

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
            const isQr = /\^BQ[A-Z]?,/i.test(block);
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

    const getAlignmentDocuments = () => previewPayload?.alignment_documents?.length
        ? previewPayload.alignment_documents
        : (previewPayload?.documents || []);

    const getActiveLabelType = () => String(previewPayload?.current_block?.label_type || '').toLowerCase();

    const syncPrintPrepared = () => {
        const activeLabelType = getActiveLabelType();
        printPrepared = Boolean(activeLabelType && preparedLabelTypes.has(activeLabelType));
    };

    const markLabelTypePrepared = (labelType) => {
        if (['serial', 'rating'].includes(labelType)) {
            preparedLabelTypes.add(labelType);
        }
        syncPrintPrepared();
    };

    const invalidatePreparedLabelType = (labelType) => {
        preparedLabelTypes.delete(labelType);
        syncPrintPrepared();
    };

    const updatePrepareLabelTypeOptions = () => {
        if (!prepareLabelTypeSelect) return;

        const previousValue = prepareLabelTypeSelect.value;
        const availableTypes = [...new Set(getAlignmentDocuments()
            .map((document) => String(document.label_type || '').toLowerCase())
            .filter((labelType) => ['serial', 'rating'].includes(labelType)))];
        const choices = [];
        if (availableTypes.length > 1) {
            choices.push({ value: 'all', label: 'Serial y Rating' });
        }
        if (availableTypes.includes('serial')) {
            choices.push({ value: 'serial', label: 'Solo Serial' });
        }
        if (availableTypes.includes('rating')) {
            choices.push({ value: 'rating', label: 'Solo Rating' });
        }

        prepareLabelTypeSelect.innerHTML = '';
        choices.forEach((choice) => {
            const option = document.createElement('option');
            option.value = choice.value;
            option.textContent = choice.label;
            prepareLabelTypeSelect.appendChild(option);
        });
        prepareLabelTypeSelect.disabled = choices.length === 0;
        prepareLabelTypeSelect.value = choices.some((choice) => choice.value === previousValue)
            ? previousValue
            : (choices[0]?.value || '');
    };

    const getDocumentForAlignmentType = (labelType) => getAlignmentDocuments()
        .find((doc) => String(doc.label_type || '').toLowerCase() === labelType);

    const pushAlignmentHistory = (values = readAlignmentInputs()) => {
        alignmentHistory.push({ ...values });
        alignmentHistory = alignmentHistory.slice(-30);
    };

    const selectedAlignmentFields = () => ({
        x: `${currentAlignmentType}_${currentAlignmentElement}_x`,
        y: `${currentAlignmentType}_${currentAlignmentElement}_y`,
    });

    const describeHorizontalOffset = (value) => {
        if (value === 0) return 'sin ajuste';
        return value > 0 ? `${value} puntos a la derecha` : `${Math.abs(value)} puntos a la izquierda`;
    };

    const describeVerticalOffset = (value) => {
        if (value === 0) return 'sin ajuste';
        return value > 0 ? `${value} puntos hacia abajo` : `${Math.abs(value)} puntos hacia arriba`;
    };

    const updateAlignmentMovementSummary = () => {
        const values = readAlignmentInputs();
        const fields = selectedAlignmentFields();
        if (alignmentHorizontalSummary) {
            alignmentHorizontalSummary.textContent = `Horizontal: ${describeHorizontalOffset(Number(values[fields.x] || 0))}`;
        }
        if (alignmentVerticalSummary) {
            alignmentVerticalSummary.textContent = `Vertical: ${describeVerticalOffset(Number(values[fields.y] || 0))}`;
        }
        if (undoAlignmentButton) {
            undoAlignmentButton.disabled = alignmentHistory.length === 0;
            undoAlignmentButton.classList.toggle('opacity-50', alignmentHistory.length === 0);
        }
    };

    const getAlignmentFieldForObject = (object) => {
        const objectType = object?.data?.kind === 'qr' ? 'qr' : 'text';
        return {
            x: `${object.data.labelType}_${objectType}_x`,
            y: `${object.data.labelType}_${objectType}_y`,
        };
    };

    const syncAlignmentFromCanvasObject = (object) => {
        if (syncingCanvasFromInputs || !object?.data?.basePoint || !object?.data?.scale) return;

        setActiveAlignmentElement(object.data.kind);
        const fields = getAlignmentFieldForObject(object);
        const nextX = Math.round((object.left - object.data.basePoint.left) / object.data.scale);
        const nextY = Math.round((object.top - object.data.basePoint.top) / object.data.scale);
        const current = readAlignmentInputs();

        current[fields.x] = nextX;
        current[fields.y] = nextY;
        setAlignmentInputs(current);
        setAlignmentDirty();
        updateAlignmentMovementSummary();
    };

    const createAlignmentGroup = (elements, kind, labelType, metrics, offsets, ghost = false) => {
        if (!elements.length) return null;

        const minX = Math.min(...elements.map((element) => element.x));
        const minY = Math.min(...elements.map((element) => element.y));
        const offsetX = ghost ? 0 : Number(offsets[`${labelType}_${kind}_x`] || 0);
        const offsetY = ghost ? 0 : Number(offsets[`${labelType}_${kind}_y`] || 0);
        const basePoint = {
            left: metrics.originX + (minX * metrics.scale),
            top: metrics.originY + (minY * metrics.scale),
        };

        const objects = elements.map((element) => {
            if (kind === 'qr') {
                const width = element.width * metrics.scale;
                const height = element.height * metrics.scale;
                return new Group([
                    new Rect({
                        left: 0,
                        top: 0,
                        width,
                        height,
                        fill: ghost ? 'rgba(255,255,255,0.6)' : '#dbeafe',
                        stroke: ghost ? '#64748b' : '#2563eb',
                        strokeWidth: ghost ? 1 : 2,
                        strokeDashArray: ghost ? [5, 4] : null,
                    }),
                    new Text('QR', {
                        left: width / 2,
                        top: height / 2,
                        originX: 'center',
                        originY: 'center',
                        fontSize: Math.max(12, 15 * metrics.scale),
                        fontFamily: 'Arial',
                        fontWeight: 'bold',
                        fill: ghost ? '#64748b' : '#1d4ed8',
                    }),
                ], {
                    left: (element.x - minX) * metrics.scale,
                    top: (element.y - minY) * metrics.scale,
                    selectable: false,
                    evented: false,
                });
            }

            return new Text(element.label || 'Texto', {
                left: (element.x - minX) * metrics.scale,
                top: (element.y - minY) * metrics.scale,
                fontSize: Math.max(11, element.fontSize * metrics.scale),
                fontFamily: 'Arial',
                fill: ghost ? '#64748b' : '#0f172a',
                backgroundColor: ghost ? 'rgba(255,255,255,0.65)' : 'rgba(254, 243, 199, 0.92)',
                padding: 5,
                selectable: false,
                evented: false,
            });
        });

        return new Group(objects, {
            left: basePoint.left + (offsetX * metrics.scale),
            top: basePoint.top + (offsetY * metrics.scale),
            subTargetCheck: false,
            selectable: !ghost,
            evented: !ghost,
            hasControls: false,
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true,
            hoverCursor: ghost ? 'default' : 'move',
            opacity: ghost ? 0.5 : 1,
            borderColor: '#2563eb',
            borderDashArray: [6, 4],
            padding: 4,
            data: {
                labelType,
                kind,
                basePoint,
                scale: metrics.scale,
            },
        });
    };

    const resolveLabelMetrics = (documentForType, elements, canvas, labelType, offsets) => {
        const canvasPadding = 18;
        const zpl = String(documentForType?.test_zpl || '');
        const size = documentForType?.label_size || {};
        const zplWidth = Number(zpl.match(/\^PW(\d+)/i)?.[1] || 0);
        const zplHeight = Number(zpl.match(/\^LL(\d+)/i)?.[1] || 0);
        const contentWidth = Math.max(...elements.map((element) => element.x + element.width), 320) + 24;
        const contentHeight = Math.max(...elements.map((element) => element.y + element.height), 180) + 24;
        const configuredWidth = Number(size.width_dots || zplWidth || 0);
        const configuredHeight = Number(size.height_dots || zplHeight || 0);
        const labelWidth = configuredWidth || contentWidth;
        const labelHeight = configuredHeight || contentHeight;
        const horizontalPositions = [0, labelWidth];
        const verticalPositions = [0, labelHeight];

        elements.forEach((element) => {
            const offsetX = Number(offsets[`${labelType}_${element.kind}_x`] || 0);
            const offsetY = Number(offsets[`${labelType}_${element.kind}_y`] || 0);
            horizontalPositions.push(element.x, element.x + element.width, element.x + offsetX, element.x + offsetX + element.width);
            verticalPositions.push(element.y, element.y + element.height, element.y + offsetY, element.y + offsetY + element.height);
        });

        const movementMargin = Math.max(70, Math.round(Math.min(labelWidth, labelHeight) * 0.12));
        const viewMinX = Math.min(...horizontalPositions) - movementMargin;
        const viewMaxX = Math.max(...horizontalPositions) + movementMargin;
        const viewMinY = Math.min(...verticalPositions) - movementMargin;
        const viewMaxY = Math.max(...verticalPositions) + movementMargin;
        const viewWidth = Math.max(1, viewMaxX - viewMinX);
        const viewHeight = Math.max(1, viewMaxY - viewMinY);
        const scale = Math.min(
            (canvas.getWidth() - (canvasPadding * 2)) / viewWidth,
            (canvas.getHeight() - (canvasPadding * 2)) / viewHeight,
            2,
        );
        const renderedViewWidth = viewWidth * scale;
        const renderedViewHeight = viewHeight * scale;
        const viewOriginX = (canvas.getWidth() - renderedViewWidth) / 2;
        const viewOriginY = (canvas.getHeight() - renderedViewHeight) / 2;
        const originX = viewOriginX - (viewMinX * scale);
        const originY = viewOriginY - (viewMinY * scale);

        return {
            labelWidth,
            labelHeight,
            configuredWidth,
            configuredHeight,
            scale,
            originX,
            originY,
            size,
            expandedToContent: Math.min(...horizontalPositions) < 0
                || Math.min(...verticalPositions) < 0
                || Math.max(...horizontalPositions) > labelWidth
                || Math.max(...verticalPositions) > labelHeight,
        };
    };

    const updateAlignmentSizeSummary = (metrics) => {
        if (!alignmentSizeSummary) return;

        const widthMm = Number(metrics.size?.width_mm || 0);
        const heightMm = Number(metrics.size?.height_mm || 0);
        const dpi = Number(metrics.size?.dpi || 0);
        if (widthMm > 0 && heightMm > 0) {
            const expandedSummary = metrics.expandedToContent
                ? ' · Área de movimiento ampliada para mostrar los desplazamientos'
                : '';
            alignmentSizeSummary.textContent = `${widthMm.toFixed(2)} × ${heightMm.toFixed(2)} mm · ${metrics.configuredWidth} × ${metrics.configuredHeight} puntos${dpi ? ` · ${dpi} DPI` : ''}${expandedSummary}`;
            return;
        }

        alignmentSizeSummary.textContent = `Área estimada: ${metrics.labelWidth} × ${metrics.labelHeight} puntos${dpi ? ` · ${dpi} DPI` : ''}`;
    };

    const renderAlignmentCanvas = (labelType = currentAlignmentType) => {
        const canvas = ensureAlignmentCanvas();
        if (!canvas) return;

        const availableWidth = Math.floor((alignmentCanvasViewport?.clientWidth || 784) - 26);
        const canvasWidth = Math.max(280, Math.min(920, availableWidth));
        const canvasHeight = Math.max(440, Math.min(640, Math.round(canvasWidth * 0.82)));
        if (canvas.getWidth() !== canvasWidth || canvas.getHeight() !== canvasHeight) {
            canvas.setDimensions({ width: canvasWidth, height: canvasHeight });
        }

        setActiveAlignmentType(labelType);
        canvas.clear();
        canvas.backgroundColor = '#ffffff';

        const documentForType = getDocumentForAlignmentType(currentAlignmentType);
        if (!documentForType?.test_zpl) {
            setAlignmentCanvasStatus(`No hay elementos ${currentAlignmentType.toUpperCase()} cargados. Presiona Preparar impresion o Cargar elementos.`, true);
            canvas.renderAll();
            return;
        }

        const elements = parseZplElements(documentForType.test_zpl);
        if (!elements.length) {
            setAlignmentCanvasStatus(`No encontramos elementos que puedan moverse en la etiqueta ${currentAlignmentType.toUpperCase()}.`, true);
            canvas.renderAll();
            return;
        }

        availableAlignmentElements = ['text', 'qr'].filter((kind) => elements.some((element) => element.kind === kind));
        setActiveAlignmentElement(currentAlignmentElement);
        const offsets = readAlignmentInputs();
        const metrics = resolveLabelMetrics(documentForType, elements, canvas, currentAlignmentType, offsets);
        updateAlignmentSizeSummary(metrics);
        canvas.add(new Rect({
            left: metrics.originX,
            top: metrics.originY,
            width: metrics.labelWidth * metrics.scale,
            height: metrics.labelHeight * metrics.scale,
            fill: '#f8fafc',
            stroke: '#f59e0b',
            strokeWidth: 2,
            strokeDashArray: [8, 5],
            shadow: '0 8px 18px rgba(15, 23, 42, 0.15)',
            selectable: false,
            evented: false,
        }));

        canvas.add(new Text('ÁREA DE ETIQUETA CONFIGURADA', {
            left: metrics.originX + 8,
            top: metrics.originY + 8,
            fontSize: 11,
            fontWeight: 'bold',
            fill: '#b45309',
            backgroundColor: 'rgba(255, 251, 235, 0.9)',
            selectable: false,
            evented: false,
        }));

        syncingCanvasFromInputs = true;
        availableAlignmentElements.forEach((kind) => {
            const kindElements = elements.filter((element) => element.kind === kind);
            const ghost = createAlignmentGroup(kindElements, kind, currentAlignmentType, metrics, offsets, true);
            const object = createAlignmentGroup(kindElements, kind, currentAlignmentType, metrics, offsets);
            if (ghost) canvas.add(ghost);
            if (object) canvas.add(object);
            if (object && kind === currentAlignmentElement) canvas.setActiveObject(object);
        });
        syncingCanvasFromInputs = false;

        canvas.requestRenderAll();
        updateAlignmentMovementSummary();
        setAlignmentCanvasStatus('Toda el área blanca es de movimiento libre. La línea naranja es solo una guía y no limita los elementos.');
    };

    const refreshAlignmentPreview = async () => {
        try {
            if (!previewPayload?.documents?.length) {
                await loadPreview();
            }

            const availableTypes = getAlignmentDocuments().map((doc) => String(doc.label_type || '').toLowerCase());
            const nextType = availableTypes.includes(currentAlignmentType) ? currentAlignmentType : (availableTypes[0] || 'serial');
            alignmentTypeButtons.forEach((button) => {
                button.classList.toggle('hidden', !availableTypes.includes(button.dataset.alignmentType));
            });
            alignmentTypeSwitch?.classList.toggle('hidden', availableTypes.length <= 1);
            setAlignmentInputs(getAlignment());
            renderAlignmentCanvas(nextType);
        } catch (error) {
            setAlignmentCanvasStatus(`No pudimos cargar la etiqueta. ${error.message}`, true);
        }
    };

    const moveSelectedAlignmentElement = (direction) => {
        const deltaByDirection = {
            up: [0, -alignmentStep],
            down: [0, alignmentStep],
            left: [-alignmentStep, 0],
            right: [alignmentStep, 0],
        };
        const delta = deltaByDirection[direction];
        if (!delta) return;

        const values = readAlignmentInputs();
        const fields = selectedAlignmentFields();
        pushAlignmentHistory(values);
        values[fields.x] = Number(values[fields.x] || 0) + delta[0];
        values[fields.y] = Number(values[fields.y] || 0) + delta[1];
        setAlignmentInputs(values);
        setAlignmentDirty();
        renderAlignmentCanvas(currentAlignmentType);
    };

    const setAlignmentStep = (step) => {
        alignmentStep = [1, 5, 10].includes(Number(step)) ? Number(step) : 5;
        alignmentStepButtons.forEach((button) => {
            const isActive = Number(button.dataset.alignmentStep) === alignmentStep;
            button.classList.toggle('border-blue-600', isActive);
            button.classList.toggle('bg-blue-50', isActive);
            button.classList.toggle('text-blue-800', isActive);
            button.classList.toggle('border-slate-300', !isActive);
            button.classList.toggle('text-slate-700', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        if (alignmentStepCenter) {
            alignmentStepCenter.textContent = `${alignmentStep} ${alignmentStep === 1 ? 'punto' : 'puntos'}`;
        }
    };

    const resetSelectedAlignmentElement = () => {
        const values = readAlignmentInputs();
        const fields = selectedAlignmentFields();
        if (Number(values[fields.x] || 0) === 0 && Number(values[fields.y] || 0) === 0) return;

        pushAlignmentHistory(values);
        values[fields.x] = 0;
        values[fields.y] = 0;
        setAlignmentInputs(values);
        setAlignmentDirty();
        renderAlignmentCanvas(currentAlignmentType);
    };

    const resetCurrentAlignmentLabel = () => {
        const values = readAlignmentInputs();
        pushAlignmentHistory(values);
        ['text_x', 'text_y', 'qr_x', 'qr_y'].forEach((field) => {
            values[`${currentAlignmentType}_${field}`] = 0;
        });
        setAlignmentInputs(values);
        setAlignmentDirty();
        renderAlignmentCanvas(currentAlignmentType);
    };

    const undoAlignmentChange = () => {
        const previous = alignmentHistory.pop();
        if (!previous) return;

        setAlignmentInputs(previous);
        setAlignmentDirty();
        renderAlignmentCanvas(currentAlignmentType);
    };

    const saveCurrentAlignment = () => {
        if (!selectedPrinters[currentAlignmentType]) {
            showAlert(
                'Selecciona una impresora',
                `Antes de guardar, conecta y selecciona la impresora para ${currentAlignmentType.toUpperCase()}.`,
                'warning',
            );
            updateAlignmentPrinterNote();
            return false;
        }

        const saved = persistAlignmentForCurrentPrinter(readAlignmentInputs(), currentAlignmentType);
        if (!saved) return false;

        setAlignmentDirty(false, currentAlignmentType);
        alignmentHistory = [];
        updateAlignmentMovementSummary();
        invalidatePreparedLabelType(currentAlignmentType);

        return true;
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
    const browserPrintScriptUrl = root.dataset.browserPrintUrl || '';
    let browserPrintLoadPromise = null;

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
        void Swal.fire(title, text, icon);
    };

    const ensureBrowserPrint = () => {
        if (window.BrowserPrint) {
            return Promise.resolve(window.BrowserPrint);
        }

        if (!browserPrintScriptUrl) {
            return Promise.reject(new Error('No se encontro la ruta de Zebra Browser Print.'));
        }

        if (!browserPrintLoadPromise) {
            browserPrintLoadPromise = new Promise((resolve, reject) => {
                const resolveWhenReady = () => {
                    if (window.BrowserPrint) {
                        resolve(window.BrowserPrint);
                        return;
                    }

                    reject(new Error('Zebra Browser Print no quedo disponible en este navegador.'));
                };

                const existingScript = document.querySelector('script[data-browser-print-loader="1"]');
                if (existingScript) {
                    existingScript.addEventListener('load', resolveWhenReady);
                    existingScript.addEventListener('error', () => reject(new Error('No se pudo cargar Zebra Browser Print.')));
                    return;
                }

                const script = document.createElement('script');
                script.src = browserPrintScriptUrl;
                script.async = true;
                script.dataset.browserPrintLoader = '1';
                script.addEventListener('load', resolveWhenReady);
                script.addEventListener('error', () => reject(new Error('No se pudo cargar Zebra Browser Print.')));
                document.head.appendChild(script);
            });
        }

        return browserPrintLoadPromise.catch((error) => {
            browserPrintLoadPromise = null;
            throw error;
        });
    };

    const blockStatusMeta = (status) => {
        const normalized = String(status || 'pending').toLowerCase();

        if (normalized === 'confirmed') {
            return { text: 'Confirmado', shortText: 'OK', classes: 'border-emerald-200 bg-emerald-50 text-emerald-800', badgeClasses: 'bg-emerald-100 text-emerald-800' };
        }

        if (normalized === 'failed') {
            return { text: 'Fallido', shortText: 'Revisar', classes: 'border-red-200 bg-red-50 text-red-800', badgeClasses: 'bg-red-100 text-red-800' };
        }

        if (normalized === 'sent') {
            return { text: 'Enviado', shortText: 'Enviado', classes: 'border-blue-200 bg-blue-50 text-blue-800', badgeClasses: 'bg-blue-100 text-blue-800' };
        }

        return { text: 'Pendiente', shortText: 'Pendiente', classes: 'border-amber-200 bg-amber-50 text-amber-800', badgeClasses: 'bg-amber-100 text-amber-800' };
    };

    const renderBlockProgress = (blocks = [], currentBlock = null) => {
        if (!blockProgress) return;

        if (!blocks.length) {
            blockProgress.textContent = 'Aun no hay bloques preparados para este batch.';
            if (blockProgressSummary) {
                blockProgressSummary.textContent = 'Sin preparar';
            }
            if (blockCurrentMessage) {
                blockCurrentMessage.textContent = 'Prepara la impresion para ver el bloque activo.';
                blockCurrentMessage.className = 'mb-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700';
            }
            return;
        }

        const totals = blocks.reduce((carry, block) => {
            const normalized = String(block.status || 'pending').toLowerCase();
            carry[normalized] = (carry[normalized] || 0) + 1;
            return carry;
        }, {});
        const confirmed = totals.confirmed || 0;
        const failed = totals.failed || 0;
        const currentLabel = currentBlock
            ? `Actual: bloque ${currentBlock.sequence} ${String(currentBlock.label_type || '').toUpperCase()}`
            : 'Sin bloque activo';

        if (blockProgressSummary) {
            const failedText = failed ? ` - ${failed} con falla` : '';
            blockProgressSummary.textContent = `${confirmed}/${blocks.length} confirmados${failedText}`;
        }

        if (blockCurrentMessage) {
            blockCurrentMessage.className = currentBlock
                ? 'mb-3 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-900'
                : 'mb-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800';
            blockCurrentMessage.textContent = currentBlock
                ? `${currentLabel} - imprime este bloque y confirma para avanzar al siguiente.`
                : 'No hay bloques pendientes por imprimir.';
        }

        blockProgress.innerHTML = '';
        blocks.forEach((block, index) => {
            const status = blockStatusMeta(block.status);
            const isCurrent = currentBlock && Number(currentBlock.id) === Number(block.id);
            const labelCount = block.label_count || block.unit_count || 0;
            const item = document.createElement('div');
            item.className = `rounded-xl border p-3 ${status.classes} ${isCurrent ? 'ring-2 ring-blue-400 ring-offset-1' : ''}`;

            const topRow = document.createElement('div');
            topRow.className = 'flex items-start justify-between gap-2';

            const sequenceBox = document.createElement('div');
            sequenceBox.className = 'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/80 text-lg font-bold shadow-sm';
            sequenceBox.textContent = block.sequence || index + 1;

            const statusBadge = document.createElement('div');
            statusBadge.className = `rounded-full px-2 py-1 text-[11px] font-semibold uppercase tracking-wide ${status.badgeClasses}`;
            statusBadge.textContent = isCurrent ? 'Actual' : status.shortText;

            topRow.append(sequenceBox, statusBadge);

            const title = document.createElement('div');
            title.className = 'mt-3 text-xs font-semibold uppercase tracking-wide';
            title.textContent = `${String(block.label_type || '').toUpperCase()} - Bloque ${block.sequence || index + 1}`;

            const detail = document.createElement('div');
            detail.className = 'mt-1 text-base font-bold';
            detail.textContent = `${labelCount} etiqueta(s)`;

            const step = document.createElement('div');
            step.className = 'mt-1 text-xs opacity-80';
            step.textContent = `Paso ${index + 1} de ${blocks.length} - ${status.text}`;

            const attempts = document.createElement('div');
            attempts.className = 'mt-1 text-xs opacity-80';
            attempts.textContent = `Intentos: ${block.attempts || 0}`;

            item.append(topRow, title, detail, step, attempts);
            blockProgress.appendChild(item);
        });
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

        if (labelType === currentAlignmentType) {
            updateAlignmentPrinterNote();
            if (!alignmentDirty && alignmentModal && !alignmentModal.classList.contains('hidden')) {
                setAlignmentInputs(getAlignment());
                renderAlignmentCanvas(currentAlignmentType);
            }
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

    const connectPrinter = async () => {
        try {
            await ensureBrowserPrint();
        } catch (error) {
            setStatus(error.message || 'No se encontro BrowserPrint. Instala/abre Zebra Browser Print.', true);
            return;
        }

        setStatus('Buscando impresoras Zebra...');

        window.BrowserPrint.getLocalDevices((devices) => {
            availablePrinters = (devices || []).filter((candidate) => candidate.deviceType === 'printer');
            preparedLabelTypes.clear();
            printPrepared = false;
            previewPayload = null;
            activeBlockId = null;

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
            setStatus(`Se detectaron ${availablePrinters.length} impresora(s). Serial: ${serialReady ? 'OK' : 'pendiente'}  Rating: ${ratingReady ? 'OK' : 'pendiente'}.`);
        }, (error) => {
            setStatus(`Error al conectar impresora: ${error}`, true);
        }, 'printer');
    };

    const sendToPrinter = (device, zplChunk) => new Promise((resolve, reject) => {
        device.send(zplChunk, () => resolve(), (error) => reject(new Error(error)));
    });

    const sendAlignmentTestForCurrentType = async () => {
        try {
            if (!availablePrinters.length) {
                throw new Error('Primero conecta una impresora Zebra.');
            }

            if (!previewPayload?.documents?.length) {
                await loadPreview();
            }

            const documentForType = getDocumentForAlignmentType(currentAlignmentType);
            if (!documentForType?.test_zpl) {
                throw new Error(`No hay una muestra disponible para la etiqueta ${currentAlignmentType.toUpperCase()}.`);
            }

            const printer = resolveSelectedPrinter(currentAlignmentType);
            if (!printer) {
                throw new Error(`Selecciona la impresora para ${currentAlignmentType.toUpperCase()}.`);
            }

            setStatus(`Enviando una etiqueta de prueba ${currentAlignmentType.toUpperCase()}...`);
            const adjustedTestZpl = applyAlignmentToZpl(documentForType.test_zpl, currentAlignmentType);
            await sendToPrinter(printer, adjustedTestZpl);

            markLabelTypePrepared(currentAlignmentType);
            setStatus(`Prueba ${currentAlignmentType.toUpperCase()} enviada a ${printer.name || 'la impresora seleccionada'}.`);

            return true;
        } catch (error) {
            setStatus(`No se pudo imprimir la prueba: ${error.message}`, true);
            showAlert('No se pudo imprimir la prueba', error.message, 'error');
            return false;
        }
    };

    const loadPreview = async () => {
        setStatus('Preparando informacion de impresion...');

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
            throw new Error(errorText || 'No se pudo preparar la impresion.');
        }

        previewPayload = await response.json();
        activeBlockId = previewPayload.current_block?.id || previewPayload.block_id || null;
        updatePrepareLabelTypeOptions();
        syncPrintPrepared();
        renderBlockProgress(previewPayload.blocks || [], previewPayload.current_block || null);

        if (previewPayload.batch_complete) {
            previewSummary.textContent = 'Todos los bloques de este batch estan confirmados.';
            setPrintBlocked('Este batch ya fue confirmado por bloques. No hay pendientes por imprimir.');
            return;
        }

        const lines = (previewPayload.documents || []).map((doc) => {
            const block = previewPayload.current_block;
            const blockText = block ? `Bloque ${block.sequence} ${String(block.label_type || '').toUpperCase()} - ` : '';
            return `${blockText}Tipo de etiqueta: ${doc.label_type} - Cantidad: ${doc.units_count}`;
        });

        previewSummary.textContent = lines.join('\n') || 'No hay documentos para este lote.';
        setStatus('Preparacion completada. Revisa el bloque activo y presiona "Imprimir ahora".');
    };

    const preparePrint = async (showCompletionAlert = true) => {
        try {
            if (!availablePrinters.length) {
                setStatus('Primero conecta impresoras.', true);
                showAlert('Impresora requerida', 'Conecta una impresora antes de preparar la impresion.', 'error');
                return false;
            }

            await loadPreview();
            const requestedType = prepareLabelTypeSelect?.value || getActiveLabelType();
            const availableTestDocs = getAlignmentDocuments().filter((document) => document?.test_zpl);
            const testDocs = requestedType === 'all'
                ? availableTestDocs
                : availableTestDocs.filter((document) => String(document.label_type || '').toLowerCase() === requestedType);
            if (!testDocs.length) {
                setStatus('No hay contenido de prueba para imprimir.', true);
                showAlert('Sin contenido', 'No se encontró una muestra para el tipo de etiqueta seleccionado.', 'warning');
                return false;
            }
            validatePrintersForDocuments(testDocs);

            const requestedLabels = testDocs.map((document) => String(document.label_type || '').toUpperCase()).join(' y ');
            setStatus(`Enviando prueba de ${requestedLabels}...`);
            for (const doc of testDocs) {
                const labelType = String(doc.label_type || '').toLowerCase();
                const printer = resolveSelectedPrinter(labelType);
                if (!printer) {
                    throw new Error(`No hay impresora seleccionada para ${labelType.toUpperCase()}.`);
                }

                const adjustedTestZpl = applyAlignmentToZpl(doc.test_zpl, labelType);
                await sendToPrinter(printer, adjustedTestZpl);
                markLabelTypePrepared(labelType);
            }

            const activeLabelType = getActiveLabelType();
            const activeWasPrepared = preparedLabelTypes.has(activeLabelType);
            setStatus(activeWasPrepared
                ? `Prueba de ${requestedLabels} enviada. El bloque ${activeLabelType.toUpperCase()} está listo para imprimir.`
                : `Prueba de ${requestedLabels} enviada. Prepara también ${activeLabelType.toUpperCase()} para imprimir el bloque activo.`);
            if (showCompletionAlert) {
                showAlert(
                    'Preparación completada',
                    `Se envió una etiqueta de prueba de ${requestedLabels}. ${activeWasPrepared ? 'Ya puedes imprimir el bloque activo.' : `Aún debes preparar ${activeLabelType.toUpperCase()}.`}`,
                    'success',
                );
            }
            return true;
        } catch (error) {
            syncPrintPrepared();
            setStatus(`Error al preparar impresion: ${error.message}`, true);
            showAlert('Error de preparacion', error.message || 'No se pudo enviar la impresion de prueba.', 'error');
            return false;
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
            body: JSON.stringify({ printed_ok: true, block_id: activeBlockId }),
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || 'No se pudo confirmar impresion.');
        }

        return response.json();
    };

    const reportBlockFailure = async (message) => {
        if (!root.dataset.failUrl || !activeBlockId) {
            return null;
        }

        const response = await fetch(root.dataset.failUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ block_id: activeBlockId, message }),
        });

        if (!response.ok) {
            return null;
        }

        return response.json();
    };

    const printBatch = async () => {
        let sentAllDocuments = false;
        let confirmedInBackend = false;

        try {
            if (printButton?.disabled) {
                return;
            }

            if (!availablePrinters.length) {
                setStatus('Primero conecta impresoras.', true);
                return;
            }

            if (!printPrepared) {
                showAlert('Preparacion requerida', 'Debes presionar "Preparar impresion" antes de imprimir.', 'error');
                setStatus('Debes preparar la impresion primero para liberar el boton de imprimir.', true);
                return;
            }

            if (!previewPayload || !previewPayload.documents) {
                setStatus('No hay preparacion activa. Presiona "Preparar impresion" nuevamente.', true);
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

            sentAllDocuments = true;
            const result = await confirmPrinted();
            confirmedInBackend = true;
            renderBlockProgress(result.blocks || [], result.next_block || null);
            if (confirmationBox) {
                const printedAt = result.printed_at ? ` - Confirmado en: ${result.printed_at}` : '';
                confirmationBox.textContent = `${result.message || 'Bloque confirmado.'} Seriales actualizados: ${result.updated_serial_units}.${printedAt}`;
            }

            if (result.batch_complete) {
                setPrintBlocked(result.message || 'Impresion confirmada. Boton bloqueado para evitar duplicidad.');
            } else {
                await loadPreview();
                syncPrintPrepared();
            }
        } catch (error) {
            if (!sentAllDocuments) {
                await reportBlockFailure(error.message || 'Falla durante envio a impresora.');
                if (previewPayload) {
                    await loadPreview();
                }
                setStatus(`Error en impresion: ${error.message}`, true);
                return;
            }

            invalidatePreparedLabelType(getActiveLabelType());
            const message = confirmedInBackend
                ? `Bloque confirmado, pero no se pudo cargar el siguiente bloque: ${error.message}`
                : `Impresion enviada, pero no se pudo confirmar en sistema: ${error.message}`;
            setStatus(message, true);
        }
    };

    const hideAlignmentModal = () => {
        alignmentModal?.classList.add('hidden');
        alignmentModal?.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        alignmentFabricCanvas?.discardActiveObject();
        alignmentFabricCanvas?.requestRenderAll();
        openAlignmentModalButton?.focus();
    };

    const focusNextDirtyAlignmentType = () => {
        const nextType = [...dirtyAlignmentTypes][0];
        if (!nextType) return false;

        renderAlignmentCanvas(nextType);
        showAlert(
            'Falta guardar otra etiqueta',
            `Aún tienes cambios sin guardar en ${nextType.toUpperCase()}. Revísalos antes de cerrar.`,
            'info',
        );

        return true;
    };

    const requestCloseAlignmentModal = async () => {
        if (alignmentDirty) {
            const result = await Swal.fire({
                title: '¿Descartar los cambios?',
                text: 'Los movimientos que no guardaste se perderán.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, descartar',
                cancelButtonText: 'Seguir ajustando',
                confirmButtonColor: '#dc2626',
                reverseButtons: true,
            });
            if (!result.isConfirmed) return;
        }

        setAlignmentDirty(false, null);
        alignmentHistory = [];
        setAlignmentInputs(getAlignment());
        hideAlignmentModal();
    };

    const openAlignmentModal = async () => {
        alignmentHistory = [];
        setAlignmentDirty(false, null);
        setAlignmentInputs(getAlignment());
        setAlignmentStep(alignmentStep);
        setActiveAlignmentElement(currentAlignmentElement);
        alignmentModal?.classList.remove('hidden');
        alignmentModal?.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        setActiveAlignmentType(currentAlignmentType);
        setAlignmentCanvasStatus('Cargando la etiqueta actual…');

        await new Promise((resolve) => window.requestAnimationFrame(resolve));
        await refreshAlignmentPreview();
        closeAlignmentModalButton?.focus();
    };

    const saveAndTestAlignment = async () => {
        if (!saveCurrentAlignment()) return;

        const originalText = saveTestAlignmentButton?.textContent || '';
        if (saveTestAlignmentButton) {
            saveTestAlignmentButton.disabled = true;
            saveTestAlignmentButton.textContent = 'Enviando prueba…';
        }

        const sent = await sendAlignmentTestForCurrentType();

        if (saveTestAlignmentButton) {
            saveTestAlignmentButton.disabled = false;
            saveTestAlignmentButton.textContent = originalText;
        }
        if (!sent) return;

        const result = await Swal.fire({
            title: 'Revisa la etiqueta de prueba',
            text: '¿Los elementos quedaron en la posición correcta?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, quedó bien',
            cancelButtonText: 'Seguir ajustando',
            confirmButtonColor: '#2563eb',
            reverseButtons: true,
            allowOutsideClick: false,
        });

        if (result.isConfirmed) {
            if (focusNextDirtyAlignmentType()) return;

            hideAlignmentModal();
            const activeLabelType = String(previewPayload?.current_block?.label_type || '').toLowerCase();
            setStatus(activeLabelType === currentAlignmentType
                ? 'Ajuste validado con una etiqueta de prueba. Ya puedes imprimir el bloque activo.'
                : `Ajuste ${currentAlignmentType.toUpperCase()} validado y guardado. El bloque activo sigue siendo ${activeLabelType.toUpperCase()}.`);
            return;
        }

        setStatus('Continúa ajustando la posición y vuelve a imprimir una prueba.');
    };

    connectButton?.addEventListener('click', connectPrinter);
    serialPrinterSelect?.addEventListener('change', (event) => {
        const selectedId = event.target.value;
        const device = availablePrinters.find((printer) => getPrinterId(printer) === selectedId) || null;
        setSelectedPrinter('serial', device);
        invalidatePreparedLabelType('serial');
        if (device) {
            setStatus('Impresora SERIAL seleccionada. Prepara impresion nuevamente para validar.');
            return;
        }

        setStatus('Selecciona una impresora para SERIAL.', true);
    });
    ratingPrinterSelect?.addEventListener('change', (event) => {
        const selectedId = event.target.value;
        const device = availablePrinters.find((printer) => getPrinterId(printer) === selectedId) || null;
        setSelectedPrinter('rating', device);
        invalidatePreparedLabelType('rating');
        if (device) {
            setStatus('Impresora RATING seleccionada. Prepara impresion nuevamente para validar.');
            return;
        }

        setStatus('Selecciona una impresora para RATING.', true);
    });
    previewButton?.addEventListener('click', () => void preparePrint());
    openAlignmentModalButton?.addEventListener('click', () => void openAlignmentModal());
    closeAlignmentModalButton?.addEventListener('click', () => void requestCloseAlignmentModal());
    cancelAlignmentModalButton?.addEventListener('click', () => void requestCloseAlignmentModal());
    alignmentModal?.addEventListener('click', (event) => {
        if (event.target === alignmentModal) {
            void requestCloseAlignmentModal();
        }
    });
    saveAlignmentButton?.addEventListener('click', () => {
        if (!saveCurrentAlignment()) return;
        if (focusNextDirtyAlignmentType()) return;

        hideAlignmentModal();
        setStatus('Ajuste guardado para la impresora seleccionada. Prepara la impresión para validarlo.');
    });
    saveTestAlignmentButton?.addEventListener('click', () => void saveAndTestAlignment());
    resetAlignmentButton?.addEventListener('click', async () => {
        const result = await Swal.fire({
            title: `¿Restablecer etiqueta ${currentAlignmentType.toUpperCase()}?`,
            text: 'El texto y el código QR volverán a su posición original.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, restablecer',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            reverseButtons: true,
        });
        if (result.isConfirmed) resetCurrentAlignmentLabel();
    });
    resetAlignmentElementButton?.addEventListener('click', resetSelectedAlignmentElement);
    undoAlignmentButton?.addEventListener('click', undoAlignmentChange);
    alignmentTypeButtons.forEach((button) => {
        button.addEventListener('click', () => renderAlignmentCanvas(button.dataset.alignmentType));
    });
    alignmentElementButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setActiveAlignmentElement(button.dataset.alignmentElement);
            renderAlignmentCanvas(currentAlignmentType);
        });
    });
    alignmentMoveButtons.forEach((button) => {
        button.addEventListener('click', () => moveSelectedAlignmentElement(button.dataset.alignmentMove));
    });
    alignmentStepButtons.forEach((button) => {
        button.addEventListener('click', () => setAlignmentStep(button.dataset.alignmentStep));
    });
    document.querySelectorAll('[data-align]').forEach((input) => {
        input.addEventListener('focus', () => {
            input.dataset.alignmentSnapshot = JSON.stringify(readAlignmentInputs());
        });
        input.addEventListener('input', () => {
            setAlignmentDirty();
            renderAlignmentCanvas(currentAlignmentType);
        });
        input.addEventListener('change', () => {
            const snapshot = parseStoredJsonFromValue(input.dataset.alignmentSnapshot);
            if (snapshot) pushAlignmentHistory(snapshot);
            delete input.dataset.alignmentSnapshot;
            updateAlignmentMovementSummary();
        });
    });
    document.addEventListener('keydown', (event) => {
        if (!alignmentModal || alignmentModal.classList.contains('hidden')) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            void requestCloseAlignmentModal();
            return;
        }

        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement?.tagName)) return;
        const directionByKey = { ArrowUp: 'up', ArrowDown: 'down', ArrowLeft: 'left', ArrowRight: 'right' };
        const direction = directionByKey[event.key];
        if (!direction) return;

        event.preventDefault();
        moveSelectedAlignmentElement(direction);
    });
    printButton?.addEventListener('click', printBatch);

    setSelectedPrinter('serial', null);
    setSelectedPrinter('rating', null);
    setActiveAlignmentType('serial');
    setAlignmentStep(5);
    updateAlignmentMovementSummary();

    if (root.dataset.alreadyPrinted === '1') {
        setPrintBlocked('Este batch ya fue confirmado como impreso. El boton se bloqueo para evitar duplicidad.');
        if (confirmationBox) {
            confirmationBox.textContent = 'Este batch ya cuenta con confirmacion de impresion previa.';
        }
    }
})();

