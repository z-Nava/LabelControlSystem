import { loadBrowserPrint } from '../lib/browser-print-loader';

const readConfig = () => {
    const element = document.getElementById('kiosk-requisition-print-config');

    if (!element) return null;

    try {
        return JSON.parse(element.textContent || '{}');
    } catch (error) {
        console.error('No se pudo leer la configuración de impresión del Kiosk.', error);
        return null;
    }
};

const initializeRequisitionPrint = () => {
    const root = document.getElementById('kiosk-requisition-print');
    const config = readConfig();

    if (!root || !config) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const statusBox = document.getElementById('kiosk-requisition-print-status');
    const printerBox = document.getElementById('kiosk-requisition-printer');
    const retryButton = document.getElementById('kiosk-requisition-print-retry');
    const tokenStorageKey = `kiosk_requisition_print_${config.token}`;
    const printerStorageKey = 'kiosk_requisition_preferred_printer';
    let running = false;

    const describeError = (error, fallback = 'No fue posible imprimir la etiqueta.') => {
        if (typeof error === 'string' && error.trim()) return error.trim();
        if (error?.message) return String(error.message);
        if (error?.error) return String(error.error);

        return fallback;
    };

    const setStatus = (message, state = 'pending') => {
        statusBox.textContent = message;
        statusBox.className = 'mt-2 text-sm font-semibold';
        statusBox.classList.add({
            success: 'text-emerald-700',
            error: 'text-red-700',
            warning: 'text-amber-700',
            pending: 'text-slate-700',
        }[state] || 'text-slate-700');
    };

    const readStorage = (key) => {
        try {
            const value = localStorage.getItem(key);
            return value ? JSON.parse(value) : null;
        } catch (_error) {
            return null;
        }
    };

    const writeStorage = (key, value) => {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (_error) {
            // La impresión puede continuar aunque el navegador bloquee localStorage.
        }
    };

    const removeStorage = (key) => {
        try {
            localStorage.removeItem(key);
        } catch (_error) {
            // Sin acción: el backend también bloquea duplicados confirmados.
        }
    };

    const postJson = async (url, body) => {
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(payload.message || `La operación falló con HTTP ${response.status}.`);
            error.status = response.status;
            error.payload = payload;
            throw error;
        }

        return payload;
    };

    const getLocalPrinters = (browserPrint) => new Promise((resolve, reject) => {
        browserPrint.getLocalDevices(
            (devices) => resolve((devices || []).filter((device) => device.deviceType === 'printer')),
            (error) => reject(new Error(describeError(error, 'No se pudieron consultar las impresoras Zebra.'))),
            'printer',
        );
    });

    const getDefaultPrinter = (browserPrint) => new Promise((resolve) => {
        browserPrint.getDefaultDevice('printer', resolve, () => resolve(null));
    });

    const samePrinter = (printer, descriptor) => {
        if (!printer || !descriptor) return false;

        if (descriptor.uid && printer.uid) {
            return descriptor.uid === printer.uid;
        }

        return String(descriptor.name || '').toLocaleLowerCase()
            === String(printer.name || '').toLocaleLowerCase();
    };

    const resolvePrinter = async (browserPrint) => {
        const printers = await getLocalPrinters(browserPrint);

        if (!printers.length) {
            throw new Error('No se detectó una impresora Zebra. Verifica conexión y Zebra Browser Print.');
        }

        const configuredName = String(config.defaultPrinterName || '').trim().toLocaleLowerCase();
        const configuredPrinter = configuredName
            ? printers.find((printer) => String(printer.name || '').trim().toLocaleLowerCase() === configuredName)
            : null;
        const storedPrinter = readStorage(printerStorageKey);
        const rememberedPrinter = printers.find((printer) => samePrinter(printer, storedPrinter));
        const defaultPrinter = await getDefaultPrinter(browserPrint);
        const selectedPrinter = configuredPrinter
            || rememberedPrinter
            || (printers.length === 1 ? printers[0] : null)
            || defaultPrinter;

        if (!selectedPrinter) {
            throw new Error('Hay varias impresoras Zebra y no se pudo determinar la predeterminada.');
        }

        writeStorage(printerStorageKey, {
            name: selectedPrinter.name,
            uid: selectedPrinter.uid,
            connection: selectedPrinter.connection,
        });

        return selectedPrinter;
    };

    const sendToPrinter = (printer, zpl) => new Promise((resolve, reject) => {
        printer.send(
            zpl,
            resolve,
            (error) => reject(new Error(describeError(error, 'La impresora rechazó el envío ZPL.'))),
        );
    });

    const confirmSentPrint = async (printerName) => {
        setStatus('La etiqueta fue enviada; confirmando el registro de impresión…');
        const result = await postJson(config.confirmUrl, {
            token: config.token,
            printer_name: printerName || null,
        });
        removeStorage(tokenStorageKey);
        setStatus(result.message || 'Etiqueta de requisición impresa correctamente.', 'success');
        retryButton.classList.add('hidden');
        root.classList.remove('border-amber-200', 'bg-amber-50');
        root.classList.add('border-emerald-200', 'bg-emerald-50');
    };

    const reportFailure = async (message, printerName = null) => {
        try {
            await postJson(config.failUrl, {
                token: config.token,
                error: message,
                printer_name: printerName,
            });
        } catch (_error) {
            // El mensaje principal de impresión es más útil que una segunda falla de auditoría.
        }
    };

    const print = async () => {
        if (running) return;

        running = true;
        retryButton.disabled = true;
        retryButton.classList.add('opacity-60', 'cursor-wait');
        let printer = null;
        let printClaimed = false;
        let sentToPrinter = false;

        try {
            const previousSend = readStorage(tokenStorageKey);

            if (previousSend?.state === 'sent') {
                printerBox.textContent = previousSend.printerName || 'Impresora Zebra';
                await confirmSentPrint(previousSend.printerName || null);
                return;
            }

            setStatus('Conectando con Zebra Browser Print…');
            const browserPrint = await loadBrowserPrint(config.browserPrintUrl);
            printer = await resolvePrinter(browserPrint);
            printerBox.textContent = `${printer.name || 'Impresora Zebra'} (${printer.connection || 'local'})`;

            setStatus('Preparando la etiqueta de requisición…');
            const claim = await postJson(config.claimUrl, { token: config.token });

            if (claim.status === 'printed') {
                removeStorage(tokenStorageKey);
                setStatus(claim.message || 'Esta etiqueta ya fue impresa.', 'success');
                retryButton.classList.add('hidden');
                return;
            }

            printClaimed = true;

            if (!claim.zpl) {
                throw new Error('El servidor no devolvió contenido ZPL para la etiqueta.');
            }

            setStatus(`Enviando etiqueta ${config.labelSize || '10 × 10 cm'} a la impresora…`);
            await sendToPrinter(printer, claim.zpl);
            sentToPrinter = true;
            writeStorage(tokenStorageKey, {
                state: 'sent',
                printerName: printer.name || null,
                sentAt: new Date().toISOString(),
            });
            await confirmSentPrint(printer.name || null);
        } catch (error) {
            const message = describeError(error);

            if (printClaimed && !sentToPrinter) {
                removeStorage(tokenStorageKey);
                await reportFailure(message, printer?.name || null);
            }

            setStatus(
                sentToPrinter
                    ? `La etiqueta fue enviada, pero falta confirmar en el sistema: ${message}`
                    : `${message} La requisición permanece guardada.`,
                error?.status === 409 ? 'warning' : 'error',
            );
            retryButton.textContent = sentToPrinter ? 'Confirmar envío' : 'Reintentar impresión';
            retryButton.classList.remove('hidden');
        } finally {
            running = false;
            retryButton.disabled = false;
            retryButton.classList.remove('opacity-60', 'cursor-wait');
        }
    };

    retryButton.addEventListener('click', () => void print());
    window.setTimeout(() => void print(), 100);
};

initializeRequisitionPrint();
