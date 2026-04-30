import { getNumericValue, getValue } from './dom';

export const createLayoutPreview = ({ canvas }) => {
    const ctx = canvas?.getContext('2d');
    let sourceWidth = 820;
    let sourceHeight = 400;
    let previewWidth = 451;
    let previewHeight = 220;
    let draggableElements = [];
    let draggingElement = null;
    let dragOffsetX = 0;
    let dragOffsetY = 0;

    const getScaleX = () => previewWidth / sourceWidth;
    const getScaleY = () => previewHeight / sourceHeight;

    const drawCanvasPreview = () => {
        if (!ctx || !canvas) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        draggableElements.forEach((element) => {
            if (element.type === 'qr') {
                ctx.fillStyle = '#f1f5f9';
                ctx.strokeStyle = '#0f172a';
                ctx.lineWidth = 2;
                ctx.fillRect(element.x, element.y, element.w, element.h);
                ctx.strokeRect(element.x, element.y, element.w, element.h);
                ctx.fillStyle = '#334155';
                ctx.font = `700 ${Math.max(9, element.h / 4)}px sans-serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('QR', element.x + (element.w / 2), element.y + (element.h / 2));
                return;
            }
            ctx.fillStyle = element.color;
            ctx.font = `700 ${element.fontSize}px sans-serif`;
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.fillText(element.text, element.x, element.y);
        });
    };

    const syncInputsFromCanvas = () => {
        const scaleX = getScaleX();
        const scaleY = getScaleY();
        draggableElements.forEach((element) => {
            const xInput = document.getElementById(element.fieldX);
            const yInput = document.getElementById(element.fieldY);
            if (xInput) xInput.value = Math.max(0, Math.round(element.x / scaleX));
            if (yInput) yInput.value = Math.max(0, Math.round(element.y / scaleY));
        });
    };

    const render = () => {
        if (!canvas || !ctx) return;
        const dummyType = getValue('dummy_type', 'rmt');
        const titleText = dummyType === 'rw' ? 'RW Dummy QR' : 'RMT Dummy QR';
        const scaleX = getScaleX();
        const scaleY = getScaleY();

        const qrSize = Math.max(26, Math.round(Math.max(1, getNumericValue('qr_magnification', 4)) * 18));
        draggableElements = [
            { id: 'title', type: 'text', text: titleText, x: getNumericValue('title_x', 20) * scaleX, y: getNumericValue('title_y', 20) * scaleY, fontSize: Math.max(10, getNumericValue('title_font_size', 44) * scaleY), color: '#b91c1c', fieldX: 'title_x', fieldY: 'title_y' },
            { id: 'qr', type: 'qr', x: getNumericValue('qr_x', 30) * scaleX, y: getNumericValue('qr_y', 65) * scaleY, w: Math.max(18, qrSize * scaleX), h: Math.max(18, qrSize * scaleY), fieldX: 'qr_x', fieldY: 'qr_y' },
            { id: 'fg', type: 'text', text: 'FG: 479124001', x: getNumericValue('fg_x', 360) * scaleX, y: getNumericValue('fg_y', 70) * scaleY, fontSize: Math.max(9, getNumericValue('fg_font_size', 40) * scaleY), color: '#0f172a', fieldX: 'fg_x', fieldY: 'fg_y' },
            { id: 'job', type: 'text', text: 'JOB: 999999', x: getNumericValue('job_x', 360) * scaleX, y: getNumericValue('job_y', 130) * scaleY, fontSize: Math.max(9, getNumericValue('job_font_size', 34) * scaleY), color: '#0f172a', fieldX: 'job_x', fieldY: 'job_y' },
            { id: 'consecutive', type: 'text', text: '0000000014', x: getNumericValue('consecutive_x', 380) * scaleX, y: getNumericValue('consecutive_y', 250) * scaleY, fontSize: Math.max(9, getNumericValue('consecutive_font_size', 58) * scaleY), color: '#0f172a', fieldX: 'consecutive_x', fieldY: 'consecutive_y' },
        ];
        drawCanvasPreview();
    };

    const setPreviewStageSize = (widthDots, heightDots) => {
        if (!canvas) return;
        sourceWidth = Math.max(200, Number(widthDots) || 820);
        sourceHeight = Math.max(120, Number(heightDots) || 400);
        const fitScale = Math.min(560 / sourceWidth, 260 / sourceHeight);
        previewWidth = Math.max(260, Math.round(sourceWidth * fitScale));
        previewHeight = Math.max(140, Math.round(sourceHeight * fitScale));
        canvas.width = previewWidth;
        canvas.height = previewHeight;
    };

    const getMousePosition = (event) => {
        const rect = canvas.getBoundingClientRect();
        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    };
    const hitTest = (x, y) => {
        for (let i = draggableElements.length - 1; i >= 0; i -= 1) {
            const element = draggableElements[i];
            if (element.type === 'qr') {
                if (x >= element.x && x <= element.x + element.w && y >= element.y && y <= element.y + element.h) return element;
                continue;
            }
            const measure = ctx.measureText(element.text);
            if (x >= element.x && x <= element.x + measure.width && y >= element.y && y <= element.y + element.fontSize) return element;
        }
        return null;
    };

    const bindDrag = () => {
        canvas?.addEventListener('mousedown', (event) => {
            const { x, y } = getMousePosition(event);
            const target = hitTest(x, y);
            if (!target) return;
            draggingElement = target;
            dragOffsetX = x - target.x;
            dragOffsetY = y - target.y;
        });
        window.addEventListener('mousemove', (event) => {
            if (!draggingElement) return;
            const { x, y } = getMousePosition(event);
            draggingElement.x = Math.max(0, x - dragOffsetX);
            draggingElement.y = Math.max(0, y - dragOffsetY);
            syncInputsFromCanvas();
            drawCanvasPreview();
        });
        window.addEventListener('mouseup', () => { draggingElement = null; });
    };

    return { render, setPreviewStageSize, bindDrag };
};
