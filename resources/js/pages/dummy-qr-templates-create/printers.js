const SGD_RESPONSE_TERMINATOR = '\n';
const SGD_READ_RETRIES = 5;

export const normalizeSgdValue = (response) => String(response ?? '')
    .replace(/[\u0000-\u001f]+/g, ' ')
    .trim()
    .replace(/^"|"$/g, '')
    .trim();

export const dotsToMillimeters = (dots, dpi) => {
    if (dots === null || dots === undefined || String(dots).trim() === ''
        || dpi === null || dpi === undefined || String(dpi).trim() === '') {
        return null;
    }

    const parsedDots = Number(dots);
    const parsedDpi = Number(dpi);

    if (!Number.isFinite(parsedDots) || !Number.isFinite(parsedDpi) || parsedDpi <= 0) {
        return null;
    }

    return (parsedDots / parsedDpi) * 25.4;
};

const describeBrowserPrintError = (error, fallback) => {
    if (typeof error === 'string' && error.trim()) return error.trim();
    if (error?.message) return error.message;
    return fallback;
};

const readSgdValue = (printer, setting) => new Promise((resolve, reject) => {
    const command = `! U1 getvar "${setting}"\r\n`;
    const onSuccess = (response) => resolve(normalizeSgdValue(response));
    const onError = (error) => reject(new Error(describeBrowserPrintError(error, `No se pudo leer ${setting}.`)));

    if (typeof printer?.sendThenReadUntilStringReceived === 'function') {
        printer.sendThenReadUntilStringReceived(
            command,
            SGD_RESPONSE_TERMINATOR,
            onSuccess,
            onError,
            SGD_READ_RETRIES,
        );
        return;
    }

    if (typeof printer?.sendThenRead === 'function') {
        printer.sendThenRead(command, onSuccess, onError);
        return;
    }

    onError('La versión instalada de Zebra Browser Print no permite leer respuestas de la impresora.');
});

export const readZebraMediaSettings = async (printer) => {
    const settingNames = [
        'ezpl.print_width',
        'zpl.label_length',
        'ezpl.media_type',
        'print.tone',
        'head.resolution.in_dpi',
    ];
    const values = {};
    let successfulReads = 0;
    let lastError = null;

    // Las respuestas SGD comparten el mismo buffer. Se consultan en serie para
    // que cada valor permanezca asociado a su comando, también en USB lento.
    for (const setting of settingNames) {
        try {
            values[setting] = await readSgdValue(printer, setting);
            successfulReads += 1;
        } catch (error) {
            values[setting] = '';
            lastError = error;
        }
    }

    if (successfulReads === 0) {
        throw lastError || new Error('La impresora no respondió a las consultas SGD.');
    }

    return {
        printWidth: values['ezpl.print_width'],
        labelLength: values['zpl.label_length'],
        mediaType: values['ezpl.media_type'],
        printTone: values['print.tone'],
        dpi: values['head.resolution.in_dpi'],
    };
};

export const createPrinterService = ({
    setStatus,
    connectionTypeInput,
    defaultPrinterNameInput,
    defaultPrinterIpInput,
    printerSelectInput,
}) => {
    let availableUsbPrinters = [];

    const ensureBrowserPrint = () => {
        if (!window.BrowserPrint) {
            setStatus('Zebra Browser Print no está disponible. Confirma que la aplicación esté instalada y ejecutándose.', true);
            return false;
        }
        return true;
    };

    const getUsbPrinterId = (printer) => [printer?.name || '', printer?.uid || '', printer?.connection || 'usb'].join('::');
    const pickSelectedPrinter = () => {
        if (!availableUsbPrinters.length) return null;
        const selectedId = printerSelectInput?.value || '';
        if (selectedId) {
            const selected = availableUsbPrinters.find((printer) => getUsbPrinterId(printer) === selectedId);
            if (selected) return selected;
        }
        return availableUsbPrinters[0];
    };

    const renderUsbPrinterOptions = (selectedId = '') => {
        if (!printerSelectInput) return;
        printerSelectInput.innerHTML = '';
        if (!availableUsbPrinters.length) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No se detectaron impresoras USB';
            printerSelectInput.appendChild(option);
            return;
        }
        const hintOption = document.createElement('option');
        hintOption.value = '';
        hintOption.textContent = 'Selecciona una impresora USB...';
        printerSelectInput.appendChild(hintOption);
        availableUsbPrinters.forEach((printer) => {
            const option = document.createElement('option');
            option.value = getUsbPrinterId(printer);
            option.textContent = `${printer.name || 'Sin nombre'} (${printer.connection || 'usb'})`;
            printerSelectInput.appendChild(option);
        });
        if (selectedId) printerSelectInput.value = selectedId;
    };

    const listUsbPrinters = ({ silent = false, afterLoad = null, onError = null } = {}) => {
        window.BrowserPrint.getLocalDevices((devices) => {
            availableUsbPrinters = (devices || []).filter((candidate) => candidate.deviceType === 'printer' && String(candidate.connection || '').toLowerCase().includes('usb'));
            const selected = pickSelectedPrinter();
            renderUsbPrinterOptions(selected ? getUsbPrinterId(selected) : '');
            if (!silent) setStatus(availableUsbPrinters.length ? `Se detectaron ${availableUsbPrinters.length} impresora(s) USB.` : 'No se detectaron impresoras USB disponibles.', !availableUsbPrinters.length);
            afterLoad?.(availableUsbPrinters);
        }, (error) => {
            availableUsbPrinters = [];
            renderUsbPrinterOptions('');
            const message = describeBrowserPrintError(error, 'No fue posible listar impresoras USB.');
            if (!silent) setStatus(message, true);
            onError?.(message);
        }, 'printer');
    };

    const getNetworkPrinter = (onSuccess, onError) => {
        const ip = (defaultPrinterIpInput?.value || '').trim();
        if (!ip) return onError('Captura IP de impresora para validar conexión por red.');

        const printer = new window.BrowserPrint.Device({
            name: ip,
            uid: ip,
            connection: 'network',
            deviceType: 'printer',
            provider: 'network',
            manufacturer: 'Zebra',
        });

        onSuccess(printer, ip);
    };

    const validateNetworkPrinter = (onSuccess, onError) => {
        getNetworkPrinter((printer, ip) => {
            readSgdValue(printer, 'device.languages')
                .then(() => onSuccess(printer, ip))
                .catch(() => onError(`No fue posible conectar a la impresora en ${ip}. Verifica IP y conectividad.`));
        }, onError);
    };

    const resolveUsbPrinter = (onSuccess, onError) => {
        const useSelectedPrinter = () => {
            const selected = pickSelectedPrinter();
            if (!selected) {
                onError('No se detectó una impresora Zebra USB conectada.');
                return;
            }
            if (defaultPrinterNameInput) defaultPrinterNameInput.value = selected.name || '';
            onSuccess(selected, selected.name || 'impresora USB');
        };

        if (!availableUsbPrinters.length) {
            listUsbPrinters({
                silent: true,
                afterLoad: useSelectedPrinter,
                onError,
            });
            return;
        }

        useSelectedPrinter();
    };

    const resolveCurrentPrinter = (onSuccess, onError) => {
        if ((connectionTypeInput?.value || 'usb') === 'network') {
            getNetworkPrinter(onSuccess, onError);
            return;
        }

        resolveUsbPrinter(onSuccess, onError);
    };

    const readMediaSettings = (onSuccess, onError) => {
        resolveCurrentPrinter((printer, printerLabel) => {
            readZebraMediaSettings(printer)
                .then((settings) => onSuccess(settings, printer, printerLabel))
                .catch((error) => onError(describeBrowserPrintError(error, 'No fue posible leer la configuración de media.')));
        }, onError);
    };

    return {
        ensureBrowserPrint,
        listUsbPrinters,
        validateNetworkPrinter,
        resolveUsbPrinter,
        readMediaSettings,
        get connectionType() { return connectionTypeInput?.value || 'usb'; },
    };
};
