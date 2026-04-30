import { buildZpl } from './dummy-qr-templates-create/zpl';
import { createLayoutPreview } from './dummy-qr-templates-create/layout-preview';
import { createPrinterService } from './dummy-qr-templates-create/printers';
import { createStatusSetter, getElement } from './dummy-qr-templates-create/dom';
import { attachFabricToWindow } from '../lib/fabric-setup';

attachFabricToWindow();

const formRoot = document.getElementById('dummy-template-form');
if (!formRoot) {
    // not in this page
} else {
    const statusEl = getElement('printer-test-status');
    const previewEl = getElement('zpl-preview');
    const setStatus = createStatusSetter(statusEl);

    const connectionTypeInput = getElement('connection_type');
    const printerSelectInput = getElement('usb_printer_select');
    const refreshPrintersButton = getElement('refresh-printers');
    const defaultPrinterNameInput = getElement('default_printer_name');
    const defaultPrinterIpInput = getElement('default_printer_ip');
    const layoutPreviewStage = getElement('layout-preview-stage');

    const layoutPreview = createLayoutPreview({ canvas: layoutPreviewStage });

    const printerService = createPrinterService({
        setStatus,
        connectionTypeInput,
        defaultPrinterNameInput,
        defaultPrinterIpInput,
        printerSelectInput,
    });

    getElement('preview-zpl')?.addEventListener('click', () => {
        previewEl.textContent = buildZpl();
        previewEl.classList.remove('hidden');
        setStatus('ZPL de prueba generado.');
    });

    getElement('test-printer-connection')?.addEventListener('click', () => {
        if (!printerService.ensureBrowserPrint()) return;
        if (printerService.connectionType === 'network') {
            setStatus('Validando impresora por red...');
            printerService.validateNetworkPrinter((_, ip) => setStatus(`Conexión por red OK: ${ip}`), (error) => setStatus(error, true));
            return;
        }
        setStatus('Buscando impresora USB...');
        printerService.resolveUsbPrinter((printer) => printer.read(() => setStatus(`Conexión USB OK: ${printer.name}`), () => setStatus(`No se pudo validar conexión con ${printer.name}.`, true)), (error) => setStatus(error, true));
    });

    getElement('test-print')?.addEventListener('click', () => {
        if (!printerService.ensureBrowserPrint()) return;
        const zpl = buildZpl();
        if (printerService.connectionType === 'network') {
            printerService.validateNetworkPrinter((printer, ip) => printer.send(zpl, () => setStatus(`Impresión de prueba enviada por red a ${ip}.`), (error) => setStatus(`Error de impresión por red: ${error}`, true)), (error) => setStatus(error, true));
            return;
        }
        printerService.resolveUsbPrinter((printer) => printer.send(zpl, () => setStatus(`Impresión de prueba enviada a ${printer.name}.`), (error) => setStatus(`Error de impresión USB: ${error}`, true)), (error) => setStatus(error, true));
    });

    refreshPrintersButton?.addEventListener('click', () => {
        if (!printerService.ensureBrowserPrint()) return;
        setStatus('Buscando impresoras USB disponibles...');
        printerService.listUsbPrinters();
    });

    [
        'dummy_type', 'qr_x', 'qr_y', 'qr_magnification', 'fg_x', 'fg_y', 'fg_font_size',
        'job_x', 'job_y', 'job_font_size', 'consecutive_x', 'consecutive_y', 'consecutive_font_size',
        'title_x', 'title_y', 'title_font_size',
    ].forEach((fieldId) => {
        const field = getElement(fieldId);
        field?.addEventListener('input', layoutPreview.render);
        field?.addEventListener('change', layoutPreview.render);
    });

    layoutPreview.setPreviewStageSize(820, 400);
    layoutPreview.render();
    layoutPreview.bindDrag();

    if (connectionTypeInput) {
        connectionTypeInput.dispatchEvent(new Event('change'));
    }

    if (printerService.ensureBrowserPrint()) {
        printerService.listUsbPrinters({ silent: true });
    }
}
