import Swal from '../lib/sweetalert';
import { loadBrowserPrint } from '../lib/browser-print-loader';
import { buildDummyItemZpl } from './dummy-print-center/zpl';

const STORAGE_KEY = 'dummy_print_selected_printer';

const readPageConfig = () => {
    const configElement = document.getElementById('dummy-print-center-config');
    if (!configElement) return null;

    try {
        return JSON.parse(configElement.textContent || '{}');
    } catch (error) {
        console.error('No se pudo leer la configuración del centro de impresión dummy.', error);
        return null;
    }
};

const initializeDummyPrintCenter = () => {
    const root = document.getElementById('dummy-print-center');
    if (!root) return;

    const config = readPageConfig();
    const connectButton = document.getElementById('connect-printer');
    const prepareButton = document.getElementById('prepare-print');
    const printButton = document.getElementById('print-batch');
    const printerBox = document.getElementById('selected-printer');
    const printerSelect = document.getElementById('printer-select');
    const statusBox = document.getElementById('print-status');
    const progress = document.getElementById('print-progress');
    const progressBar = document.getElementById('print-progress-bar');
    const progressSummary = document.getElementById('print-progress-summary');
    const progressDetail = document.getElementById('print-progress-detail');

    if (!config) {
        statusBox.textContent = 'No se pudo cargar la configuración del centro de impresión.';
        statusBox.classList.add('text-red-700');
        printButton.disabled = true;
        return;
    }

    const items = Array.isArray(config.items) ? config.items : [];
    const templatesByType = config.templatesByType || {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let selectedDevice = null;
    let availablePrinters = [];
    let printPrepared = false;
    let printLocked = false;
    let isPrinting = false;

    const setStatus = (message, isError = false) => {
        statusBox.textContent = message;
        statusBox.classList.toggle('text-red-700', isError);
        statusBox.classList.toggle('text-slate-700', !isError);
    };

    const updatePrintProgress = (sent, total, state = 'idle') => {
        const safeTotal = Math.max(0, Number(total) || 0);
        const safeSent = Math.min(Math.max(0, Number(sent) || 0), safeTotal);
        const percentage = safeTotal > 0 ? Math.round((safeSent / safeTotal) * 100) : 0;
        const summaries = {
            idle: 'Pendiente',
            printing: 'Imprimiendo',
            sent: 'Envío completado',
            error: 'Envío interrumpido',
        };

        progress.setAttribute('aria-valuemax', String(safeTotal));
        progress.setAttribute('aria-valuenow', String(safeSent));
        progressBar.style.width = `${percentage}%`;
        progressBar.classList.toggle('bg-red-600', !['sent', 'error'].includes(state));
        progressBar.classList.toggle('bg-emerald-500', state === 'sent');
        progressBar.classList.toggle('bg-amber-500', state === 'error');
        progressSummary.textContent = summaries[state] || summaries.idle;
        progressDetail.textContent = `${safeSent.toLocaleString()} de ${safeTotal.toLocaleString()} etiquetas enviadas (${percentage}%).`;
    };

    const getPrinterId = (device) => [device?.name || '', device?.uid || '', device?.connection || ''].join('::');

    const persistSelectedPrinter = (device) => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            name: device.name,
            uid: device.uid,
            connection: device.connection,
        }));
    };

    const setSelectedPrinter = (device) => {
        if (!device) {
            selectedDevice = null;
            printerBox.textContent = 'Sin conectar';
            return;
        }

        selectedDevice = device;
        persistSelectedPrinter(device);
        printerBox.textContent = `${device.name} (${device.connection || 'connection'})`;
    };

    const readStoredPrinter = () => {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;

        try {
            return JSON.parse(raw);
        } catch (_error) {
            localStorage.removeItem(STORAGE_KEY);
            return null;
        }
    };

    const restoreStoredPrinterLabel = () => {
        const storedPrinter = readStoredPrinter();
        if (storedPrinter) {
            printerBox.textContent = `${storedPrinter.name} (${storedPrinter.connection || 'connection'})`;
        }
    };

    const restorePreferredPrinter = () => {
        const storedPrinter = readStoredPrinter();
        if (!storedPrinter) return null;

        return availablePrinters.find((printer) => (
            (printer.name || '') === (storedPrinter.name || '')
            && (printer.uid || '') === (storedPrinter.uid || '')
            && (printer.connection || '') === (storedPrinter.connection || '')
        )) || null;
    };

    const renderPrinterOptions = (preferredPrinter) => {
        printerSelect.innerHTML = '';

        if (!availablePrinters.length) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No se detectaron impresoras';
            printerSelect.appendChild(option);
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecciona una impresora';
        printerSelect.appendChild(placeholder);

        availablePrinters.forEach((printer) => {
            const option = document.createElement('option');
            option.value = getPrinterId(printer);
            option.textContent = `${printer.name || 'Sin nombre'} (${printer.connection || 'connection'})`;
            printerSelect.appendChild(option);
        });

        if (preferredPrinter) {
            printerSelect.value = getPrinterId(preferredPrinter);
            setSelectedPrinter(preferredPrinter);
            return;
        }

        printerSelect.value = '';
        setSelectedPrinter(null);
    };

    const connectPrinter = async () => {
        let browserPrint;

        try {
            browserPrint = await loadBrowserPrint(config.browserPrintUrl);
        } catch (error) {
            setStatus(error.message || 'No se encontró Browser Print. Instala o abre Zebra Browser Print.', true);
            return;
        }

        setStatus('Buscando impresoras Zebra...');

        browserPrint.getLocalDevices((devices) => {
            availablePrinters = (devices || []).filter((candidate) => candidate.deviceType === 'printer');

            if (!availablePrinters.length) {
                renderPrinterOptions(null);
                setStatus('No se detectaron impresoras locales.', true);
                return;
            }

            const preferredPrinter = restorePreferredPrinter();
            renderPrinterOptions(preferredPrinter);

            if (availablePrinters.length > 1 && !selectedDevice) {
                setStatus(`Se detectaron ${availablePrinters.length} impresoras. Elige una para continuar.`);
                return;
            }

            if (!selectedDevice && availablePrinters.length === 1) {
                setSelectedPrinter(availablePrinters[0]);
                printerSelect.value = getPrinterId(availablePrinters[0]);
            }

            setStatus(`Se detectaron ${availablePrinters.length} impresora(s). ${selectedDevice ? 'Lista para imprimir.' : 'Selecciona una para habilitar impresión.'}`);
        }, (error) => {
            setStatus(`Error al conectar impresora: ${error}`, true);
        }, 'printer');
    };

    const buildItemZpl = (item) => buildDummyItemZpl({
        item,
        templatesByType,
        jobNumber: config.jobNumber,
        fgCode: config.fgCode,
    });

    const sendToPrinter = (zplChunk) => new Promise((resolve, reject) => {
        selectedDevice.send(zplChunk, resolve, (error) => reject(new Error(error)));
    });

    const showAlert = (title, text, icon = 'error') => {
        void Swal.fire(title, text, icon);
    };

    const setPrintBlocked = (message) => {
        printLocked = true;
        printButton.disabled = true;
        printButton.title = message;
        setStatus(message);
    };

    const confirmPrinted = async () => {
        const response = await fetch(config.confirmUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
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
        let sentCount = 0;
        let totalCount = 0;

        try {
            if (printLocked || isPrinting || printButton.disabled) return;

            if (!selectedDevice) {
                setStatus('Primero conecta una impresora.', true);
                return;
            }

            if (!printPrepared) {
                showAlert('Preparación requerida', 'Debes presionar "Preparar impresión" antes de imprimir.');
                setStatus('Debes preparar la impresión primero para liberar el botón de imprimir.', true);
                return;
            }

            if (!items.length) {
                setStatus('No hay dummys para imprimir en este batch.', true);
                return;
            }

            const queue = items.flatMap((item) => {
                const zpl = buildItemZpl(item);
                const copies = Math.max(1, Number(item.copies || 1));
                return Array.from({ length: copies }, () => zpl);
            });

            totalCount = queue.length;
            isPrinting = true;
            printButton.disabled = true;
            updatePrintProgress(0, totalCount, 'printing');
            setStatus(`Enviando ${totalCount} etiqueta(s) a la impresora...`);

            for (const chunk of queue) {
                await sendToPrinter(chunk);
                sentCount += 1;
                updatePrintProgress(sentCount, totalCount, 'printing');
            }

            updatePrintProgress(sentCount, totalCount, 'sent');
            const result = await confirmPrinted();
            setPrintBlocked(result.message || 'Batch confirmado como impreso. Botón bloqueado para evitar duplicidad.');
        } catch (error) {
            if (totalCount > 0) {
                updatePrintProgress(sentCount, totalCount, sentCount === totalCount ? 'sent' : 'error');
            }
            setStatus(`Error en impresión: ${error.message}`, true);
        } finally {
            isPrinting = false;
            if (!printLocked) printButton.disabled = false;
        }
    };

    const preparePrint = async () => {
        try {
            if (printButton.disabled) return;

            if (!selectedDevice) {
                setStatus('Primero conecta una impresora.', true);
                showAlert('Impresora requerida', 'Conecta una impresora antes de preparar la impresión.');
                return;
            }

            if (!items.length) {
                setStatus('No hay dummys para preparar en este batch.', true);
                return;
            }

            setStatus('Enviando dummy de prueba para validación...');
            await sendToPrinter(buildItemZpl(items[0]));

            printPrepared = true;
            setStatus('Dummy de prueba enviado. Si el template está centrado, ya puedes presionar "Imprimir".');
            showAlert('Preparación completada', 'Se imprimió el primer dummy de prueba. Verifica centrado y luego imprime el batch completo.', 'success');
        } catch (error) {
            printPrepared = false;
            setStatus(`Error al preparar impresión: ${error.message}`, true);
            showAlert('Error de preparación', error.message || 'No se pudo enviar el dummy de prueba.');
        }
    };

    connectButton.addEventListener('click', connectPrinter);
    printerSelect.addEventListener('change', (event) => {
        const selectedId = event.target.value;
        if (!selectedId) {
            setSelectedPrinter(null);
            setStatus('Selecciona una impresora para continuar.', true);
            return;
        }

        const foundPrinter = availablePrinters.find((printer) => getPrinterId(printer) === selectedId);
        if (!foundPrinter) {
            setSelectedPrinter(null);
            setStatus('No se pudo recuperar la impresora seleccionada. Vuelve a detectar impresoras.', true);
            return;
        }

        setSelectedPrinter(foundPrinter);
        setStatus('Impresora seleccionada. Ya puedes preparar e imprimir.');
    });
    prepareButton.addEventListener('click', preparePrint);
    printButton.addEventListener('click', printBatch);

    restoreStoredPrinterLabel();
    updatePrintProgress(0, items.reduce((total, item) => total + Math.max(1, Number(item.copies || 1)), 0));

    if (config.alreadyPrinted) {
        setPrintBlocked('Este batch ya fue confirmado como impreso. El botón se bloqueó para evitar duplicidad.');
    }
};

initializeDummyPrintCenter();
