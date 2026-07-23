import { Canvas, Group, Rect, Text } from '../../lib/fabric-setup';
import {
    buildDummyItemZpl,
    DUMMY_ALIGNMENT_ELEMENTS,
    emptyDummyAlignment,
    normalizeDummyAlignment,
    parseDummyZplElements,
} from './zpl';

const STORAGE_KEY = 'dummy_print_alignment_offsets_v1';
const STORE_VERSION = 1;

const ELEMENT_LABELS = {
    title: 'Título',
    qr: 'Código QR',
    fg: 'FG',
    job: 'Job',
    consecutive: 'Consecutivo',
};

const createEmptyController = () => ({
    getAlignment: () => emptyDummyAlignment(),
    setPrinter: () => {},
});

const printerKey = (printer) => {
    if (!printer) return null;

    return [printer.name || '', printer.uid || '', printer.connection || ''].join('::');
};

const readStore = () => {
    try {
        const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');

        return stored?.version === STORE_VERSION && stored?.printers
            ? stored
            : { version: STORE_VERSION, printers: {} };
    } catch (_error) {
        return { version: STORE_VERSION, printers: {} };
    }
};

const writeStore = (store) => {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
        return true;
    } catch (_error) {
        return false;
    }
};

const savedAlignment = (printer, dummyType) => {
    const key = printerKey(printer);
    if (!key) return emptyDummyAlignment();

    return normalizeDummyAlignment(readStore().printers?.[key]?.[dummyType]);
};

export const createDummyAlignmentController = ({
    items,
    templatesByType,
    jobNumber,
    fgCode,
    getSelectedPrinter,
    sendToPrinter,
    setStatus,
    showAlert,
    onAlignmentChanged,
}) => {
    const modal = document.getElementById('dummy-alignment-modal');
    const openButton = document.getElementById('open-dummy-alignment');
    const closeButton = document.getElementById('close-dummy-alignment');
    const cancelButton = document.getElementById('cancel-dummy-alignment');
    const saveButton = document.getElementById('save-dummy-alignment');
    const saveTestButton = document.getElementById('save-test-dummy-alignment');
    const resetTypeButton = document.getElementById('reset-dummy-alignment-type');
    const resetElementButton = document.getElementById('reset-dummy-alignment-element');
    const undoButton = document.getElementById('undo-dummy-alignment');
    const canvasElement = document.getElementById('dummy-alignment-fabric-canvas');
    const canvasViewport = document.getElementById('dummy-alignment-canvas-viewport');
    const canvasStatus = document.getElementById('dummy-alignment-canvas-status');
    const currentTypeBadge = document.getElementById('dummy-alignment-current-type');
    const sizeSummary = document.getElementById('dummy-alignment-size-summary');
    const unsavedBadge = document.getElementById('dummy-alignment-unsaved-badge');
    const printerNote = document.getElementById('dummy-alignment-printer-note');
    const selectedElementTitle = document.getElementById('dummy-alignment-selected-element');
    const horizontalInput = document.getElementById('dummy-alignment-horizontal');
    const verticalInput = document.getElementById('dummy-alignment-vertical');
    const horizontalSummary = document.getElementById('dummy-alignment-horizontal-summary');
    const verticalSummary = document.getElementById('dummy-alignment-vertical-summary');
    const stepCenter = document.getElementById('dummy-alignment-step-center');
    const typeButtons = [...document.querySelectorAll('[data-dummy-alignment-type]')];
    const elementButtons = [...document.querySelectorAll('[data-dummy-alignment-element]')];
    const moveButtons = [...document.querySelectorAll('[data-dummy-alignment-move]')];
    const stepButtons = [...document.querySelectorAll('[data-dummy-alignment-step]')];

    if (!modal || !openButton || !canvasElement) {
        return createEmptyController();
    }

    const availableTypes = [...new Set(items
        .map((item) => String(item.dummyType || '').toLowerCase())
        .filter((dummyType) => ['rmt', 'rw'].includes(dummyType) && templatesByType[dummyType]))];
    let currentType = availableTypes[0] || 'rmt';
    let currentElement = null;
    let movementStep = 5;
    let fabricCanvas = null;
    let workingAlignments = {};
    let dirtyTypes = new Set();
    let historyByType = {};
    let dragSnapshot = null;
    let inputSnapshot = null;
    let syncingFromCanvas = false;
    let renderingCanvas = false;

    const sampleForType = (dummyType) => items.find(
        (item) => String(item.dummyType || '').toLowerCase() === dummyType,
    );

    const getWorkingAlignment = (dummyType = currentType) => (
        workingAlignments[dummyType] || emptyDummyAlignment()
    );

    const setWorkingAlignment = (alignment, dummyType = currentType) => {
        workingAlignments[dummyType] = normalizeDummyAlignment(alignment);
    };

    const loadWorkingAlignments = () => {
        const printer = getSelectedPrinter();
        workingAlignments = {};
        historyByType = {};

        availableTypes.forEach((dummyType) => {
            workingAlignments[dummyType] = savedAlignment(printer, dummyType);
            historyByType[dummyType] = [];
        });

        dirtyTypes = new Set();
    };

    const selectedFields = () => currentElement
        ? {
            x: `${currentElement}_x`,
            y: `${currentElement}_y`,
        }
        : null;

    const describeHorizontalOffset = (value) => {
        if (value === 0) return 'sin ajuste';
        return value > 0 ? `${value} puntos a la derecha` : `${Math.abs(value)} puntos a la izquierda`;
    };

    const describeVerticalOffset = (value) => {
        if (value === 0) return 'sin ajuste';
        return value > 0 ? `${value} puntos hacia abajo` : `${Math.abs(value)} puntos hacia arriba`;
    };

    const updateDirtyBadge = () => {
        unsavedBadge?.classList.toggle('hidden', dirtyTypes.size === 0);
    };

    const markDirty = (dummyType = currentType) => {
        dirtyTypes.add(dummyType);
        updateDirtyBadge();
        onAlignmentChanged();
    };

    const clearDirty = () => {
        dirtyTypes = new Set();
        updateDirtyBadge();
    };

    const pushHistory = (snapshot = getWorkingAlignment()) => {
        historyByType[currentType] = [...(historyByType[currentType] || []), { ...snapshot }].slice(-30);
    };

    const setCanvasStatus = (message, isError = false) => {
        if (!canvasStatus) return;

        canvasStatus.textContent = message;
        canvasStatus.className = isError
            ? 'mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800'
            : 'mt-3 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-900';
    };

    const updatePrinterNote = () => {
        if (!printerNote) return;

        const printer = getSelectedPrinter();
        if (!printer) {
            printerNote.className = 'rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900';
            printerNote.textContent = 'Conecta y selecciona la impresora para guardar ajustes exclusivos para ese equipo.';
            return;
        }

        printerNote.className = 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900';
        printerNote.textContent = `Los ajustes se guardarán localmente para ${printer.name || 'la impresora seleccionada'}.`;
    };

    const updateTypeButtons = () => {
        typeButtons.forEach((button) => {
            const isAvailable = availableTypes.includes(button.dataset.dummyAlignmentType);
            const isActive = button.dataset.dummyAlignmentType === currentType;
            button.classList.toggle('hidden', !isAvailable);
            button.classList.toggle('border-slate-900', isActive);
            button.classList.toggle('bg-slate-900', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('border-slate-300', !isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('text-slate-700', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (currentTypeBadge) {
            currentTypeBadge.textContent = `Dummy ${currentType.toUpperCase()}`;
        }
    };

    const updateElementButtons = () => {
        elementButtons.forEach((button) => {
            const isActive = button.dataset.dummyAlignmentElement === currentElement;
            button.classList.toggle('border-blue-600', isActive);
            button.classList.toggle('bg-blue-50', isActive);
            button.classList.toggle('ring-2', isActive);
            button.classList.toggle('ring-blue-100', isActive);
            button.classList.toggle('border-slate-200', !isActive);
            button.classList.toggle('bg-white', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    const updateMovementControls = () => {
        const alignment = getWorkingAlignment();
        const fields = selectedFields();
        const horizontal = fields ? Number(alignment[fields.x] || 0) : 0;
        const vertical = fields ? Number(alignment[fields.y] || 0) : 0;
        const history = historyByType[currentType] || [];
        const hasSelection = Boolean(currentElement);

        if (selectedElementTitle) {
            selectedElementTitle.textContent = hasSelection
                ? ELEMENT_LABELS[currentElement]
                : 'un elemento';
        }
        if (horizontalInput) {
            horizontalInput.value = hasSelection ? horizontal : '';
            horizontalInput.disabled = !hasSelection;
        }
        if (verticalInput) {
            verticalInput.value = hasSelection ? vertical : '';
            verticalInput.disabled = !hasSelection;
        }
        if (horizontalSummary) {
            horizontalSummary.textContent = hasSelection
                ? `Horizontal: ${describeHorizontalOffset(horizontal)}`
                : 'Horizontal: selecciona un elemento';
        }
        if (verticalSummary) {
            verticalSummary.textContent = hasSelection
                ? `Vertical: ${describeVerticalOffset(vertical)}`
                : 'Vertical: selecciona un elemento';
        }
        if (stepCenter) stepCenter.textContent = `${movementStep} ${movementStep === 1 ? 'punto' : 'puntos'}`;
        moveButtons.forEach((button) => {
            button.disabled = !hasSelection;
            button.classList.toggle('opacity-50', !hasSelection);
        });
        if (resetElementButton) {
            resetElementButton.disabled = !hasSelection;
            resetElementButton.classList.toggle('opacity-50', !hasSelection);
        }
        if (undoButton) {
            undoButton.disabled = history.length === 0;
            undoButton.classList.toggle('opacity-50', history.length === 0);
        }

        stepButtons.forEach((button) => {
            const isActive = Number(button.dataset.dummyAlignmentStep) === movementStep;
            button.classList.toggle('border-blue-600', isActive);
            button.classList.toggle('bg-blue-50', isActive);
            button.classList.toggle('text-blue-800', isActive);
            button.classList.toggle('border-slate-300', !isActive);
            button.classList.toggle('text-slate-700', !isActive);
        });
    };

    const selectElement = (elementType, shouldRender = true) => {
        if (!DUMMY_ALIGNMENT_ELEMENTS.includes(elementType)) return;

        currentElement = elementType;
        updateElementButtons();
        updateMovementControls();

        if (shouldRender) renderCanvas();
    };

    const clearElementSelection = (shouldRender = true) => {
        currentElement = null;
        updateElementButtons();
        updateMovementControls();

        if (shouldRender) {
            renderCanvas();
            return;
        }

        fabricCanvas?.requestRenderAll();
    };

    const ensureCanvas = () => {
        if (fabricCanvas) return fabricCanvas;

        fabricCanvas = new Canvas(canvasElement, {
            backgroundColor: '#e2e8f0',
            preserveObjectStacking: true,
            selection: false,
        });

        fabricCanvas.on('before:transform', () => {
            dragSnapshot = { ...getWorkingAlignment() };
        });
        fabricCanvas.on('object:moving', (event) => {
            const object = event.target;
            if (syncingFromCanvas || !object?.data?.basePoint || !object?.data?.scale) return;

            currentElement = object.data.elementType;
            const alignment = { ...getWorkingAlignment() };
            alignment[`${currentElement}_x`] = Math.round(
                (object.left - object.data.basePoint.left) / object.data.scale,
            );
            alignment[`${currentElement}_y`] = Math.round(
                (object.top - object.data.basePoint.top) / object.data.scale,
            );
            setWorkingAlignment(alignment);
            markDirty();
            updateElementButtons();
            updateMovementControls();
        });
        fabricCanvas.on('object:modified', () => {
            if (dragSnapshot) pushHistory(dragSnapshot);
            dragSnapshot = null;
            window.requestAnimationFrame(() => renderCanvas());
        });
        fabricCanvas.on('selection:created', (event) => {
            const elementType = event.selected?.[0]?.data?.elementType;
            if (elementType) selectElement(elementType, false);
        });
        fabricCanvas.on('selection:updated', (event) => {
            const elementType = event.selected?.[0]?.data?.elementType;
            if (elementType) selectElement(elementType, false);
        });
        fabricCanvas.on('selection:cleared', () => {
            if (!renderingCanvas) clearElementSelection(false);
        });

        return fabricCanvas;
    };

    const resolveMetrics = (zpl, elements, canvas, alignment) => {
        const canvasPadding = 18;
        const configuredWidth = Number(String(zpl).match(/\^PW(\d+)/i)?.[1] || 0);
        const configuredHeight = Number(String(zpl).match(/\^LL(\d+)/i)?.[1] || 0);
        const contentWidth = Math.max(...elements.map((element) => element.x + element.width), 320) + 24;
        const contentHeight = Math.max(...elements.map((element) => element.y + element.height), 180) + 24;
        const labelWidth = configuredWidth || contentWidth;
        const labelHeight = configuredHeight || contentHeight;
        const horizontalPositions = [0, labelWidth];
        const verticalPositions = [0, labelHeight];

        elements.forEach((element) => {
            const offsetX = Number(alignment[`${element.type}_x`] || 0);
            const offsetY = Number(alignment[`${element.type}_y`] || 0);
            horizontalPositions.push(
                element.x,
                element.x + element.width,
                element.x + offsetX,
                element.x + offsetX + element.width,
            );
            verticalPositions.push(
                element.y,
                element.y + element.height,
                element.y + offsetY,
                element.y + offsetY + element.height,
            );
        });

        const movementMargin = Math.max(60, Math.round(Math.min(labelWidth, labelHeight) * 0.12));
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
        const viewOriginX = (canvas.getWidth() - (viewWidth * scale)) / 2;
        const viewOriginY = (canvas.getHeight() - (viewHeight * scale)) / 2;

        return {
            labelWidth,
            labelHeight,
            configuredWidth,
            configuredHeight,
            scale,
            originX: viewOriginX - (viewMinX * scale),
            originY: viewOriginY - (viewMinY * scale),
        };
    };

    const createQrObject = (width, height, ghost) => new Group([
        new Rect({
            left: 0,
            top: 0,
            originX: 'left',
            originY: 'top',
            width,
            height,
            fill: ghost ? 'rgba(255,255,255,0.65)' : '#dbeafe',
            stroke: ghost ? '#64748b' : '#2563eb',
            strokeWidth: ghost ? 1 : 2,
            strokeDashArray: ghost ? [5, 4] : null,
        }),
        new Text('QR', {
            left: width / 2,
            top: height / 2,
            originX: 'center',
            originY: 'center',
            fontSize: Math.max(12, Math.min(24, width * 0.2)),
            fontFamily: 'Arial',
            fontWeight: 'bold',
            fill: ghost ? '#64748b' : '#1d4ed8',
        }),
    ], {
        originX: 'left',
        originY: 'top',
        selectable: false,
        evented: false,
    });

    const createElementObject = (element, metrics, alignment, ghost = false) => {
        const offsetX = ghost ? 0 : Number(alignment[`${element.type}_x`] || 0);
        const offsetY = ghost ? 0 : Number(alignment[`${element.type}_y`] || 0);
        const basePoint = {
            left: metrics.originX + (element.x * metrics.scale),
            top: metrics.originY + (element.y * metrics.scale),
        };
        const common = {
            left: basePoint.left + (offsetX * metrics.scale),
            top: basePoint.top + (offsetY * metrics.scale),
            originX: 'left',
            originY: 'top',
            selectable: !ghost,
            evented: !ghost,
            hasControls: false,
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true,
            hoverCursor: ghost ? 'default' : 'move',
            opacity: ghost ? 0.45 : 1,
            borderColor: '#2563eb',
            borderDashArray: [6, 4],
            padding: 4,
            data: {
                elementType: element.type,
                basePoint,
                scale: metrics.scale,
            },
        };

        if (element.type === 'qr') {
            const object = createQrObject(
                element.width * metrics.scale,
                element.height * metrics.scale,
                ghost,
            );
            object.set(common);
            return object;
        }

        return new Text(element.label || ELEMENT_LABELS[element.type], {
            ...common,
            fontSize: Math.max(11, element.fontSize * metrics.scale),
            fontFamily: 'Arial',
            fill: ghost ? '#64748b' : '#0f172a',
            backgroundColor: ghost ? 'rgba(255,255,255,0.7)' : 'rgba(254,243,199,0.94)',
            fontWeight: element.type === 'title' ? 'bold' : 'normal',
        });
    };

    function renderCanvas() {
        const canvas = ensureCanvas();
        const availableWidth = Math.floor((canvasViewport?.clientWidth || 784) - 26);
        const canvasWidth = Math.max(280, Math.min(920, availableWidth));
        const canvasHeight = Math.max(400, Math.min(600, Math.round(canvasWidth * 0.68)));
        if (canvas.getWidth() !== canvasWidth || canvas.getHeight() !== canvasHeight) {
            canvas.setDimensions({ width: canvasWidth, height: canvasHeight });
        }

        renderingCanvas = true;
        canvas.clear();
        canvas.backgroundColor = '#ffffff';
        updateTypeButtons();
        updateElementButtons();
        updateMovementControls();
        updatePrinterNote();

        const item = sampleForType(currentType);
        if (!item || !templatesByType[currentType]) {
            setCanvasStatus(`No hay una muestra disponible para Dummy ${currentType.toUpperCase()}.`, true);
            canvas.renderAll();
            renderingCanvas = false;
            return;
        }

        try {
            const baseZpl = buildDummyItemZpl({
                item,
                templatesByType,
                jobNumber,
                fgCode,
            });
            const elements = parseDummyZplElements(baseZpl);
            if (!elements.length) {
                throw new Error('El template no contiene elementos posicionables.');
            }

            const alignment = getWorkingAlignment();
            const metrics = resolveMetrics(baseZpl, elements, canvas, alignment);
            canvas.add(new Rect({
                left: metrics.originX,
                top: metrics.originY,
                width: metrics.labelWidth * metrics.scale,
                height: metrics.labelHeight * metrics.scale,
                fill: '#f8fafc',
                stroke: '#f59e0b',
                strokeWidth: 2,
                strokeDashArray: [8, 5],
                originX: 'left',
                originY: 'top',
                selectable: false,
                evented: false,
                shadow: '0 8px 18px rgba(15, 23, 42, 0.15)',
            }));

            elements.forEach((element) => {
                const isMoved = alignment[`${element.type}_x`] !== 0
                    || alignment[`${element.type}_y`] !== 0;
                if (isMoved) {
                    canvas.add(createElementObject(element, metrics, alignment, true));
                }
            });

            const activeObjects = {};
            elements.forEach((element) => {
                activeObjects[element.type] = createElementObject(element, metrics, alignment);
                canvas.add(activeObjects[element.type]);
            });

            const selectedObject = currentElement ? activeObjects[currentElement] : null;
            syncingFromCanvas = true;
            if (selectedObject) {
                canvas.setActiveObject(selectedObject);
            }
            syncingFromCanvas = false;
            canvas.requestRenderAll();

            if (sizeSummary) {
                sizeSummary.textContent = `${metrics.configuredWidth || metrics.labelWidth} × ${metrics.configuredHeight || metrics.labelHeight} puntos`;
            }
            setCanvasStatus('Arrastra cualquier elemento o usa los controles para ajustar su posición temporal.');
        } catch (error) {
            setCanvasStatus(`No se pudo construir la vista previa: ${error.message}`, true);
            canvas.renderAll();
        } finally {
            renderingCanvas = false;
        }
    }

    const switchType = (dummyType) => {
        if (!availableTypes.includes(dummyType)) return;

        currentType = dummyType;
        currentElement = null;
        renderCanvas();
    };

    const moveSelectedElement = (direction) => {
        if (!currentElement) return;

        const deltas = {
            up: [0, -movementStep],
            down: [0, movementStep],
            left: [-movementStep, 0],
            right: [movementStep, 0],
        };
        const delta = deltas[direction];
        if (!delta) return;

        const previous = { ...getWorkingAlignment() };
        const fields = selectedFields();
        const next = { ...previous };
        next[fields.x] += delta[0];
        next[fields.y] += delta[1];
        pushHistory(previous);
        setWorkingAlignment(next);
        markDirty();
        renderCanvas();
    };

    const resetSelectedElement = () => {
        if (!currentElement) return;

        const previous = { ...getWorkingAlignment() };
        const fields = selectedFields();
        if (previous[fields.x] === 0 && previous[fields.y] === 0) return;

        const next = { ...previous, [fields.x]: 0, [fields.y]: 0 };
        pushHistory(previous);
        setWorkingAlignment(next);
        markDirty();
        renderCanvas();
    };

    const undo = () => {
        const history = historyByType[currentType] || [];
        const previous = history.pop();
        if (!previous) return;

        historyByType[currentType] = history;
        setWorkingAlignment(previous);
        markDirty();
        renderCanvas();
    };

    const persistWorkingAlignments = () => {
        const printer = getSelectedPrinter();
        const key = printerKey(printer);
        if (!key) {
            showAlert(
                'Selecciona una impresora',
                'Conecta y selecciona la impresora antes de guardar los ajustes.',
                'warning',
            );
            updatePrinterNote();
            return false;
        }

        const store = readStore();
        store.printers[key] = store.printers[key] || {};
        dirtyTypes.forEach((dummyType) => {
            store.printers[key][dummyType] = normalizeDummyAlignment(workingAlignments[dummyType]);
        });

        if (!writeStore(store)) {
            showAlert(
                'No se pudo guardar',
                'El navegador bloqueó el almacenamiento local de los ajustes.',
                'error',
            );
            return false;
        }

        clearDirty();
        historyByType = Object.fromEntries(availableTypes.map((dummyType) => [dummyType, []]));
        updateMovementControls();
        return true;
    };

    const hideModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        fabricCanvas?.discardActiveObject();
        fabricCanvas?.requestRenderAll();
        openButton.focus();
    };

    const requestClose = async () => {
        if (dirtyTypes.size > 0) {
            const result = await showAlert({
                title: '¿Cerrar sin guardar?',
                text: 'Los movimientos realizados en este modal se descartarán.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Descartar cambios',
                cancelButtonText: 'Seguir ajustando',
                confirmButtonColor: '#dc2626',
                reverseButtons: true,
            });
            if (!result?.isConfirmed) return;
        }

        clearDirty();
        loadWorkingAlignments();
        hideModal();
    };

    const openModal = async () => {
        loadWorkingAlignments();
        currentType = availableTypes.includes(currentType) ? currentType : (availableTypes[0] || 'rmt');
        currentElement = null;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        setCanvasStatus('Cargando los elementos del dummy…');

        await new Promise((resolve) => window.requestAnimationFrame(resolve));
        renderCanvas();
        closeButton?.focus();
    };

    const saveAndClose = () => {
        if (dirtyTypes.size > 0 && !persistWorkingAlignments()) return;

        hideModal();
        setStatus('Ajustes temporales guardados. Prepara la impresión para validarlos.');
    };

    const saveAndTest = async () => {
        if (dirtyTypes.size > 0 && !persistWorkingAlignments()) return;
        if (!getSelectedPrinter()) {
            persistWorkingAlignments();
            return;
        }

        const item = sampleForType(currentType);
        if (!item) {
            showAlert('Sin muestra', `No hay un Dummy ${currentType.toUpperCase()} disponible para imprimir una prueba.`, 'warning');
            return;
        }

        const originalText = saveTestButton?.textContent || '';
        if (saveTestButton) {
            saveTestButton.disabled = true;
            saveTestButton.textContent = 'Enviando prueba…';
        }

        try {
            setStatus(`Enviando una prueba Dummy ${currentType.toUpperCase()}...`);
            await sendToPrinter(buildDummyItemZpl({
                item,
                templatesByType,
                jobNumber,
                fgCode,
                alignment: getWorkingAlignment(),
            }));

            const result = await showAlert({
                title: 'Revisa el dummy de prueba',
                text: '¿Los elementos quedaron en la posición correcta?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, quedó bien',
                cancelButtonText: 'Seguir ajustando',
                confirmButtonColor: '#2563eb',
                reverseButtons: true,
                allowOutsideClick: false,
            });

            if (result?.isConfirmed) {
                hideModal();
                setStatus(`Ajuste Dummy ${currentType.toUpperCase()} validado. Ya puedes preparar el lote.`);
            } else {
                setStatus('Continúa ajustando la posición y vuelve a imprimir una prueba.');
            }
        } catch (error) {
            setStatus(`No se pudo imprimir la prueba: ${error.message}`, true);
            showAlert('No se pudo imprimir la prueba', error.message, 'error');
        } finally {
            if (saveTestButton) {
                saveTestButton.disabled = false;
                saveTestButton.textContent = originalText;
            }
        }
    };

    openButton.addEventListener('click', () => void openModal());
    closeButton?.addEventListener('click', () => void requestClose());
    cancelButton?.addEventListener('click', () => void requestClose());
    modal.addEventListener('click', (event) => {
        if (event.target === modal) void requestClose();
    });
    saveButton?.addEventListener('click', saveAndClose);
    saveTestButton?.addEventListener('click', () => void saveAndTest());
    resetElementButton?.addEventListener('click', resetSelectedElement);
    undoButton?.addEventListener('click', undo);
    resetTypeButton?.addEventListener('click', async () => {
        const result = await showAlert({
            title: `¿Restablecer Dummy ${currentType.toUpperCase()}?`,
            text: 'Todos sus elementos volverán a la posición original del template.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Restablecer',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            reverseButtons: true,
        });
        if (!result?.isConfirmed) return;

        pushHistory();
        setWorkingAlignment(emptyDummyAlignment());
        markDirty();
        renderCanvas();
    });
    typeButtons.forEach((button) => {
        button.addEventListener('click', () => switchType(button.dataset.dummyAlignmentType));
    });
    elementButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const elementType = button.dataset.dummyAlignmentElement;
            if (currentElement === elementType) {
                clearElementSelection();
                return;
            }

            selectElement(elementType);
        });
    });
    moveButtons.forEach((button) => {
        button.addEventListener('click', () => moveSelectedElement(button.dataset.dummyAlignmentMove));
    });
    stepButtons.forEach((button) => {
        button.addEventListener('click', () => {
            movementStep = Math.max(1, Number(button.dataset.dummyAlignmentStep) || 1);
            updateMovementControls();
        });
    });
    [horizontalInput, verticalInput].forEach((input) => {
        input?.addEventListener('focus', () => {
            inputSnapshot = { ...getWorkingAlignment() };
        });
        input?.addEventListener('input', () => {
            const fields = selectedFields();
            if (!fields) return;

            const next = { ...getWorkingAlignment() };
            next[input === horizontalInput ? fields.x : fields.y] = Number(input.value || 0);
            setWorkingAlignment(next);
            markDirty();
            renderCanvas();
        });
        input?.addEventListener('change', () => {
            if (inputSnapshot) pushHistory(inputSnapshot);
            inputSnapshot = null;
            updateMovementControls();
        });
    });
    document.addEventListener('keydown', (event) => {
        if (modal.classList.contains('hidden')) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            void requestClose();
            return;
        }

        const directionByKey = {
            ArrowUp: 'up',
            ArrowDown: 'down',
            ArrowLeft: 'left',
            ArrowRight: 'right',
        };
        const direction = directionByKey[event.key];
        if (!direction || event.target instanceof HTMLInputElement) return;

        event.preventDefault();
        moveSelectedElement(direction);
    });
    window.addEventListener('resize', () => {
        if (!modal.classList.contains('hidden')) renderCanvas();
    });

    typeButtons.forEach((button) => {
        button.classList.toggle('hidden', !availableTypes.includes(button.dataset.dummyAlignmentType));
    });
    openButton.disabled = availableTypes.length === 0;
    if (availableTypes.length === 0) {
        openButton.title = 'No hay templates activos disponibles para ajustar.';
    }

    return {
        getAlignment: (dummyType) => savedAlignment(getSelectedPrinter(), dummyType),
        setPrinter: () => {
            if (!modal.classList.contains('hidden')) {
                loadWorkingAlignments();
                renderCanvas();
            }
        },
    };
};
