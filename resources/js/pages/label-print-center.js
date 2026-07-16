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
    const blockProgress = document.getElementById('block-progress');
    const blockProgressSummary = document.getElementById('block-progress-summary');
    const blockCurrentMessage = document.getElementById('block-current-message');
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
    let activeBlockId = null;
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
            setAlignmentCanvasStatus('Fabric JS aun no esta disponible. Espera un momento y vuelve a abrir el modal.', true);
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
            setAlignmentCanvasStatus(`No hay elementos ${currentAlignmentType.toUpperCase()} cargados. Presiona Preparar impresion o Cargar elementos.`, true);
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

        canvas.add(new Text(`Etiqueta ${currentAlignmentType.toUpperCase()}  escala ${scale.toFixed(2)}x`, {
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
        setAlignmentCanvasStatus(`Elementos ${currentAlignmentType.toUpperCase()} cargados desde el ZPL de esta requisicion. Arrastra el QR o el grupo de textos para moverlos libremente.`);
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
        if (window.Swal?.fire) {
            window.Swal.fire(title, text, icon);
            return;
        }

        window.alert(`${title}: ${text}`);
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

    const preparePrint = async () => {
        try {
            if (!availablePrinters.length) {
                setStatus('Primero conecta impresoras.', true);
                showAlert('Impresora requerida', 'Conecta una impresora antes de preparar la impresion.', 'error');
                return;
            }

            await loadPreview();
            const documents = previewPayload?.documents || [];
            validatePrintersForDocuments(documents);

            const testDocs = documents.filter((doc) => doc?.test_zpl);
            if (!testDocs.length) {
                printPrepared = false;
                setStatus('No hay contenido de prueba para imprimir.', true);
                showAlert('Sin contenido', 'No se encontro contenido ZPL para la impresion de prueba.', 'warning');
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
            setStatus('Impresion de prueba enviada para el bloque activo. Si todo esta correcto, ya puedes presionar "Imprimir ahora".');
            showAlert('Preparacion completada', 'Se envio 1 etiqueta de prueba del bloque activo a la impresora seleccionada. Si esta correcta, ya puedes imprimir ese bloque.', 'success');
        } catch (error) {
            printPrepared = false;
            setStatus(`Error al preparar impresion: ${error.message}`, true);
            showAlert('Error de preparacion', error.message || 'No se pudo enviar la impresion de prueba.', 'error');
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
                printPrepared = !previewPayload?.batch_complete;
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

            printPrepared = false;
            const message = confirmedInBackend
                ? `Bloque confirmado, pero no se pudo cargar el siguiente bloque: ${error.message}`
                : `Impresion enviada, pero no se pudo confirmar en sistema: ${error.message}`;
            setStatus(message, true);
        }
    };

    connectButton?.addEventListener('click', connectPrinter);
    serialPrinterSelect?.addEventListener('change', (event) => {
        const selectedId = event.target.value;
        const device = availablePrinters.find((printer) => getPrinterId(printer) === selectedId) || null;
        setSelectedPrinter('serial', device);
        printPrepared = false;
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
        printPrepared = false;
        if (device) {
            setStatus('Impresora RATING seleccionada. Prepara impresion nuevamente para validar.');
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
        setStatus('Ajustes guardados. Vuelve a preparar impresion para validar posiciones.');
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
        setPrintBlocked('Este batch ya fue confirmado como impreso. El boton se bloqueo para evitar duplicidad.');
        if (confirmationBox) {
            confirmationBox.textContent = 'Este batch ya cuenta con confirmacion de impresion previa.';
        }
    }
})();

