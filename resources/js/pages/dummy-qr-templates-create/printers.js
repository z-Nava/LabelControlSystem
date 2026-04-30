export const createPrinterService = ({
    setStatus,
    connectionTypeInput,
    defaultPrinterNameInput,
    defaultPrinterIpInput,
    printerSelectInput,
    onMediaRead,
}) => {
    let availableUsbPrinters = [];

    const ensureBrowserPrint = () => {
        if (!window.BrowserPrint) {
            setStatus('BrowserPrint no está disponible en este navegador/equipo.', true);
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

    const listUsbPrinters = ({ silent = false, afterLoad = null } = {}) => {
        window.BrowserPrint.getLocalDevices((devices) => {
            availableUsbPrinters = (devices || []).filter((candidate) => candidate.deviceType === 'printer' && String(candidate.connection || '').toLowerCase().includes('usb'));
            const selected = pickSelectedPrinter();
            renderUsbPrinterOptions(selected ? getUsbPrinterId(selected) : '');
            if (!silent) setStatus(availableUsbPrinters.length ? `Se detectaron ${availableUsbPrinters.length} impresora(s) USB.` : 'No se detectaron impresoras USB disponibles.', !availableUsbPrinters.length);
            afterLoad?.();
        }, () => {
            availableUsbPrinters = [];
            renderUsbPrinterOptions('');
            if (!silent) setStatus('No fue posible listar impresoras USB.', true);
        }, 'printer');
    };

    const validateNetworkPrinter = (onSuccess, onError) => {
        const ip = (defaultPrinterIpInput?.value || '').trim();
        if (!ip) return onError('Captura IP de impresora para validar conexión por red.');
        const printer = new window.BrowserPrint.Device(ip, undefined, 'network');
        printer.read(() => onSuccess(printer, ip), () => onError(`No fue posible conectar a la impresora en ${ip}. Verifica IP y conectividad.`));
    };

    const resolveUsbPrinter = (onSuccess, onError) => {
        if (!availableUsbPrinters.length) {
            listUsbPrinters({ silent: true, afterLoad: () => resolveUsbPrinter(onSuccess, onError) });
            return;
        }
        const selected = pickSelectedPrinter();
        if (!selected) return onError('No se detectó impresora Zebra USB conectada.');
        if (defaultPrinterNameInput) defaultPrinterNameInput.value = selected.name || '';
        onSuccess(selected);
    };

    return { ensureBrowserPrint, listUsbPrinters, validateNetworkPrinter, resolveUsbPrinter, get connectionType() { return connectionTypeInput?.value || 'usb'; }, onMediaRead };
};
