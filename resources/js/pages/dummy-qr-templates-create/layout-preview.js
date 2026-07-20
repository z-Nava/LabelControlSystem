import { getNumericValue, getValue } from './dom';

const GRID_STEP_DOTS = 50;
const DRAG_SNAP_DOTS = 5;

const ORIENTATION_LABELS = {
    N: 'Normal (N)',
    R: '90° (R)',
    I: '180° (I)',
    B: '270° (B)',
};

const ELEMENT_LABELS = {
    qr: 'A · QR',
    fg: 'B · FG',
    job: 'C · JOB',
    consecutive: 'D · Consecutivo',
    title: 'E · Título',
};

export const createLayoutPreview = ({ canvas }) => {
    const ctx = canvas?.getContext('2d');
    const selectedElementSummary = document.getElementById('layout-selected-element');
    const coordinateSummary = document.getElementById('layout-coordinate-summary');
    const orientationSummary = document.getElementById('layout-orientation-summary');
    const scaleSummary = document.getElementById('layout-scale-summary');
    const outOfBoundsWarning = document.getElementById('layout-out-of-bounds-warning');

    let fallbackWidthDots = 820;
    let fallbackHeightDots = 400;
    let previewElements = [];
    let selectedElementId = null;
    let draggingElementId = null;
    let dragOffsetX = 0;
    let dragOffsetY = 0;

    const getMetrics = () => {
        const canvasWidth = canvas?.width || 1120;
        const canvasHeight = canvas?.height || 520;
        const dpi = Math.max(1, getNumericValue('dpi', 203));
        const widthMm = Math.max(0, getNumericValue('width_mm', 0));
        const heightMm = Math.max(0, getNumericValue('height_mm', 0));
        const hasPhysicalSize = widthMm > 0 && heightMm > 0;
        const widthDots = hasPhysicalSize
            ? Math.max(1, Math.round((widthMm / 25.4) * dpi))
            : fallbackWidthDots;
        const heightDots = hasPhysicalSize
            ? Math.max(1, Math.round((heightMm / 25.4) * dpi))
            : fallbackHeightDots;
        const reservedTop = 70;
        const reservedBottom = 34;
        const reservedX = 38;
        const availableWidth = Math.max(120, canvasWidth - (reservedX * 2));
        const availableHeight = Math.max(120, canvasHeight - reservedTop - reservedBottom);
        const scale = Math.min(3, Math.max(0.05, Math.min(availableWidth / widthDots, availableHeight / heightDots)));
        const labelWidth = widthDots * scale;
        const labelHeight = heightDots * scale;
        const labelLeft = (canvasWidth - labelWidth) / 2;
        const labelTop = reservedTop + ((availableHeight - labelHeight) / 2);

        return {
            canvasWidth,
            canvasHeight,
            dpi,
            widthMm,
            heightMm,
            hasPhysicalSize,
            widthDots,
            heightDots,
            scale,
            labelWidth,
            labelHeight,
            labelLeft,
            labelTop,
        };
    };

    const toCanvasPoint = (x, y, metrics) => ({
        x: metrics.labelLeft + (x * metrics.scale),
        y: metrics.labelTop + (y * metrics.scale),
    });

    const drawWorkspace = (metrics) => {
        ctx.clearRect(0, 0, metrics.canvasWidth, metrics.canvasHeight);
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, metrics.canvasWidth, metrics.canvasHeight);

        ctx.fillStyle = '#475569';
        ctx.font = '700 14px sans-serif';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.fillText('Área física de la etiqueta Dummy QR', 18, 14);
        ctx.fillStyle = '#94a3b8';
        ctx.font = '12px sans-serif';
        ctx.fillText('El rectángulo respeta DPI, ancho y alto. Las coordenadas X/Y permanecen en dots reales.', 18, 36);

        ctx.save();
        ctx.shadowColor = 'rgba(15, 23, 42, 0.10)';
        ctx.shadowBlur = 14;
        ctx.shadowOffsetY = 4;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(metrics.labelLeft, metrics.labelTop, metrics.labelWidth, metrics.labelHeight);
        ctx.restore();

        ctx.save();
        ctx.beginPath();
        ctx.rect(metrics.labelLeft, metrics.labelTop, metrics.labelWidth, metrics.labelHeight);
        ctx.clip();

        const gridStep = metrics.widthDots > 1600 ? GRID_STEP_DOTS * 2 : GRID_STEP_DOTS;
        for (let x = gridStep; x < metrics.widthDots; x += gridStep) {
            const canvasX = metrics.labelLeft + (x * metrics.scale);
            ctx.strokeStyle = x % (gridStep * 2) === 0 ? '#cbd5e1' : '#e2e8f0';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(canvasX, metrics.labelTop);
            ctx.lineTo(canvasX, metrics.labelTop + metrics.labelHeight);
            ctx.stroke();
        }
        for (let y = gridStep; y < metrics.heightDots; y += gridStep) {
            const canvasY = metrics.labelTop + (y * metrics.scale);
            ctx.strokeStyle = y % (gridStep * 2) === 0 ? '#cbd5e1' : '#e2e8f0';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(metrics.labelLeft, canvasY);
            ctx.lineTo(metrics.labelLeft + metrics.labelWidth, canvasY);
            ctx.stroke();
        }
        ctx.restore();

        ctx.strokeStyle = '#94a3b8';
        ctx.lineWidth = 1;
        ctx.setLineDash([7, 5]);
        ctx.strokeRect(metrics.labelLeft, metrics.labelTop, metrics.labelWidth, metrics.labelHeight);
        ctx.setLineDash([]);

        ctx.fillStyle = '#dc2626';
        ctx.fillRect(metrics.labelLeft, metrics.labelTop, 24, 2);
        ctx.fillRect(metrics.labelLeft, metrics.labelTop, 2, 24);
        ctx.font = '700 10px sans-serif';
        ctx.fillText('0,0', metrics.labelLeft + 7, metrics.labelTop + 7);
    };

    const drawFinderPattern = (x, y, size) => {
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(x, y, size, size);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(x + (size * 0.18), y + (size * 0.18), size * 0.64, size * 0.64);
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(x + (size * 0.34), y + (size * 0.34), size * 0.32, size * 0.32);
    };

    const drawEditorBadge = (element) => {
        const badgeText = element.letter;
        const badgeX = Math.max(4, element.bounds.x - 9);
        const badgeY = Math.max(4, element.bounds.y - 9);

        ctx.beginPath();
        ctx.arc(badgeX, badgeY, 9, 0, Math.PI * 2);
        ctx.fillStyle = element.id === selectedElementId ? '#dc2626' : '#475569';
        ctx.fill();
        ctx.fillStyle = '#ffffff';
        ctx.font = '700 10px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(badgeText, badgeX, badgeY + 0.5);
    };

    const drawQr = (element) => {
        const { x, y, width, height } = element.bounds;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(x, y, width, height);
        ctx.strokeStyle = '#0f172a';
        ctx.lineWidth = 2;
        ctx.strokeRect(x, y, width, height);

        const inset = Math.max(3, width * 0.07);
        const finderSize = Math.max(7, width * 0.25);
        const cornersByOrientation = {
            N: ['top-left', 'top-right', 'bottom-left'],
            R: ['top-left', 'top-right', 'bottom-right'],
            I: ['top-right', 'bottom-left', 'bottom-right'],
            B: ['top-left', 'bottom-left', 'bottom-right'],
        };
        const corners = {
            'top-left': [x + inset, y + inset],
            'top-right': [x + width - inset - finderSize, y + inset],
            'bottom-left': [x + inset, y + height - inset - finderSize],
            'bottom-right': [x + width - inset - finderSize, y + height - inset - finderSize],
        };

        (cornersByOrientation[element.orientation] || cornersByOrientation.N).forEach((corner) => {
            drawFinderPattern(...corners[corner], finderSize);
        });

        ctx.fillStyle = '#0f172a';
        ctx.font = `700 ${Math.max(9, width * 0.11)}px sans-serif`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(element.orientation, x + (width * 0.67), y + (height * 0.68));
    };

    const drawElement = (element) => {
        if (element.type === 'qr') {
            drawQr(element);
        } else {
            ctx.fillStyle = element.color;
            ctx.font = `700 ${element.fontSize}px sans-serif`;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.fillText(element.text, element.bounds.x, element.bounds.y);
        }

        if (element.outOfBounds || element.id === selectedElementId) {
            ctx.strokeStyle = element.outOfBounds ? '#d97706' : '#dc2626';
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 4]);
            ctx.strokeRect(
                element.bounds.x - 4,
                element.bounds.y - 4,
                element.bounds.width + 8,
                element.bounds.height + 8,
            );
            ctx.setLineDash([]);
        }

        drawEditorBadge(element);
    };

    const buildElements = (metrics) => {
        const dummyType = getValue('dummy_type', 'rmt');
        const titleText = dummyType === 'rw' ? 'RW Dummy QR' : 'RMT Dummy QR';
        const qrOrientation = getValue('qr_orientation', 'N').toUpperCase();
        const qrSizeDots = Math.max(21, Math.max(1, getNumericValue('qr_magnification', 4)) * 21);
        const definitions = [
            { id: 'title', letter: 'E', type: 'text', text: titleText, x: getNumericValue('title_x', 20), y: getNumericValue('title_y', 20), fontSizeDots: getNumericValue('title_font_size', 44), color: '#b91c1c', fieldX: 'title_x', fieldY: 'title_y' },
            { id: 'qr', letter: 'A', type: 'qr', x: getNumericValue('qr_x', 30), y: getNumericValue('qr_y', 65), sizeDots: qrSizeDots, orientation: qrOrientation, fieldX: 'qr_x', fieldY: 'qr_y' },
            { id: 'fg', letter: 'B', type: 'text', text: 'FG: 479124001', x: getNumericValue('fg_x', 360), y: getNumericValue('fg_y', 70), fontSizeDots: getNumericValue('fg_font_size', 40), color: '#0f172a', fieldX: 'fg_x', fieldY: 'fg_y' },
            { id: 'job', letter: 'C', type: 'text', text: 'JOB: 999999', x: getNumericValue('job_x', 360), y: getNumericValue('job_y', 130), fontSizeDots: getNumericValue('job_font_size', 34), color: '#0f172a', fieldX: 'job_x', fieldY: 'job_y' },
            { id: 'consecutive', letter: 'D', type: 'text', text: '0000000014', x: getNumericValue('consecutive_x', 380), y: getNumericValue('consecutive_y', 250), fontSizeDots: getNumericValue('consecutive_font_size', 58), color: '#0f172a', fieldX: 'consecutive_x', fieldY: 'consecutive_y' },
        ];

        return definitions.map((definition) => {
            const point = toCanvasPoint(definition.x, definition.y, metrics);
            let width;
            let height;
            let fontSize = null;

            if (definition.type === 'qr') {
                width = Math.max(18, definition.sizeDots * metrics.scale);
                height = width;
            } else {
                fontSize = Math.max(10, definition.fontSizeDots * metrics.scale);
                ctx.font = `700 ${fontSize}px sans-serif`;
                width = ctx.measureText(definition.text).width;
                height = fontSize * 1.12;
            }

            const bounds = { x: point.x, y: point.y, width, height };
            const outOfBounds = bounds.x < metrics.labelLeft
                || bounds.y < metrics.labelTop
                || bounds.x + bounds.width > metrics.labelLeft + metrics.labelWidth
                || bounds.y + bounds.height > metrics.labelTop + metrics.labelHeight;

            return { ...definition, fontSize, bounds, outOfBounds };
        });
    };

    const updateSummaries = (metrics) => {
        const selected = previewElements.find((element) => element.id === selectedElementId);
        if (selectedElementSummary) {
            selectedElementSummary.textContent = selected
                ? `${ELEMENT_LABELS[selected.id]} seleccionado`
                : 'Selecciona un bloque en el canvas.';
        }
        if (coordinateSummary) {
            coordinateSummary.textContent = selected
                ? `X: ${selected.x} · Y: ${selected.y} dots`
                : 'X/Y: --';
        }

        const orientation = getValue('qr_orientation', 'N').toUpperCase();
        if (orientationSummary) {
            orientationSummary.textContent = `QR: ${ORIENTATION_LABELS[orientation] || ORIENTATION_LABELS.N}`;
        }

        if (scaleSummary) {
            const physicalSize = metrics.hasPhysicalSize
                ? `${metrics.widthMm} × ${metrics.heightMm} mm`
                : 'medida de referencia';
            scaleSummary.textContent = `Etiqueta: ${metrics.widthDots} × ${metrics.heightDots} dots · ${physicalSize} · ${metrics.dpi} DPI · escala ${metrics.scale.toFixed(2)}x`;
        }

        if (outOfBoundsWarning) {
            outOfBoundsWarning.classList.toggle('hidden', !selected?.outOfBounds);
        }
    };

    const render = () => {
        if (!canvas || !ctx) return;
        const metrics = getMetrics();
        drawWorkspace(metrics);
        previewElements = buildElements(metrics);
        previewElements.forEach(drawElement);
        updateSummaries(metrics);
    };

    const setPreviewStageSize = (widthDots, heightDots) => {
        fallbackWidthDots = Math.max(200, Number(widthDots) || 820);
        fallbackHeightDots = Math.max(120, Number(heightDots) || 400);
    };

    const getPointerPosition = (event) => {
        const rect = canvas.getBoundingClientRect();
        return {
            x: (event.clientX - rect.left) * (canvas.width / rect.width),
            y: (event.clientY - rect.top) * (canvas.height / rect.height),
        };
    };

    const hitTest = (x, y) => {
        for (let index = previewElements.length - 1; index >= 0; index -= 1) {
            const element = previewElements[index];
            const padding = 6;
            if (
                x >= element.bounds.x - padding
                && x <= element.bounds.x + element.bounds.width + padding
                && y >= element.bounds.y - padding
                && y <= element.bounds.y + element.bounds.height + padding
            ) {
                return element;
            }
        }

        return null;
    };

    const moveSelectedElement = (event) => {
        if (!draggingElementId) return;
        const element = previewElements.find((item) => item.id === draggingElementId);
        if (!element) return;

        const metrics = getMetrics();
        const pointer = getPointerPosition(event);
        const rawX = (pointer.x - dragOffsetX - metrics.labelLeft) / metrics.scale;
        const rawY = (pointer.y - dragOffsetY - metrics.labelTop) / metrics.scale;
        const x = Math.min(metrics.widthDots, Math.max(0, Math.round(rawX / DRAG_SNAP_DOTS) * DRAG_SNAP_DOTS));
        const y = Math.min(metrics.heightDots, Math.max(0, Math.round(rawY / DRAG_SNAP_DOTS) * DRAG_SNAP_DOTS));
        const xInput = document.getElementById(element.fieldX);
        const yInput = document.getElementById(element.fieldY);

        if (xInput) xInput.value = x;
        if (yInput) yInput.value = y;
        render();
    };

    const bindDrag = () => {
        if (!canvas) return;
        canvas.style.touchAction = 'none';

        canvas.addEventListener('pointerdown', (event) => {
            const pointer = getPointerPosition(event);
            const target = hitTest(pointer.x, pointer.y);
            selectedElementId = target?.id || null;
            draggingElementId = target?.id || null;

            if (target) {
                dragOffsetX = pointer.x - target.bounds.x;
                dragOffsetY = pointer.y - target.bounds.y;
                canvas.setPointerCapture?.(event.pointerId);
                canvas.style.cursor = 'grabbing';
            }
            render();
        });

        canvas.addEventListener('pointermove', (event) => {
            if (draggingElementId) {
                moveSelectedElement(event);
                return;
            }

            const pointer = getPointerPosition(event);
            canvas.style.cursor = hitTest(pointer.x, pointer.y) ? 'grab' : 'default';
        });

        const stopDragging = (event) => {
            draggingElementId = null;
            if (canvas.hasPointerCapture?.(event.pointerId)) {
                canvas.releasePointerCapture(event.pointerId);
            }
            canvas.style.cursor = 'default';
        };

        canvas.addEventListener('pointerup', stopDragging);
        canvas.addEventListener('pointercancel', stopDragging);
    };

    return { render, setPreviewStageSize, bindDrag };
};
