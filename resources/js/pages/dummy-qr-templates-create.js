import {
    buildZpl,
    LEGACY_LAYOUT_DEFAULTS,
    millimetersToDots,
    scaleLegacyLayout,
} from './dummy-qr-templates-create/zpl';
import { createLayoutPreview } from './dummy-qr-templates-create/layout-preview';
import { createPrinterService, dotsToMillimeters } from './dummy-qr-templates-create/printers';
import { createStatusSetter, getElement } from './dummy-qr-templates-create/dom';
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
    const readPrinterMediaButton = getElement('read-printer-media');
    const defaultPrinterNameInput = getElement('default_printer_name');
    const defaultPrinterIpInput = getElement('default_printer_ip');
    const printerPrintWidthEl = getElement('printer-print-width');
    const printerLabelLengthEl = getElement('printer-label-length');
    const printerMediaTypeEl = getElement('printer-media-type');
    const printerPrintToneEl = getElement('printer-print-tone');
    const printerSizeSummaryEl = getElement('printer-size-summary');
    const printerSizeMmEl = getElement('printer-size-mm');
    const printerResolutionEl = getElement('printer-resolution');
    const printerSizeComparisonEl = getElement('printer-size-comparison');
    const layoutPreviewStage = getElement('layout-preview-stage');
    const templateForm = formRoot.closest('form');
    const submitButton = templateForm?.querySelector('[data-dummy-template-submit]');
    const submitButtonLabel = submitButton?.querySelector('[data-dummy-template-submit-label]');
    const submitButtonSpinner = submitButton?.querySelector('[data-dummy-template-submit-spinner]');
    const originalSubmitLabel = submitButtonLabel?.textContent?.trim() || 'Guardar template Dummy QR';
    let isSubmitting = false;

    const setSubmitting = (submitting) => {
        isSubmitting = submitting;

        if (templateForm) {
            templateForm.setAttribute('aria-busy', submitting ? 'true' : 'false');
        }

        if (submitButton) {
            submitButton.disabled = submitting;
            submitButton.setAttribute('aria-disabled', submitting ? 'true' : 'false');
        }

        if (submitButtonLabel) {
            submitButtonLabel.textContent = submitting
                ? (submitButton.dataset.loadingLabel || 'Procesando template...')
                : originalSubmitLabel;
        }

        submitButtonSpinner?.classList.toggle('hidden', !submitting);
    };

    templateForm?.addEventListener('submit', (event) => {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }

        setSubmitting(true);
    });

    window.addEventListener('pageshow', () => setSubmitting(false));

    const layoutPreview = createLayoutPreview({ canvas: layoutPreviewStage });

    const printerService = createPrinterService({
        setStatus,
        connectionTypeInput,
        defaultPrinterNameInput,
        defaultPrinterIpInput,
        printerSelectInput,
    });

    const setPrinterMediaLoading = (loading) => {
        if (!readPrinterMediaButton) return;

        readPrinterMediaButton.disabled = loading;
        readPrinterMediaButton.setAttribute('aria-busy', loading ? 'true' : 'false');
        readPrinterMediaButton.classList.toggle('cursor-wait', loading);
        readPrinterMediaButton.classList.toggle('opacity-60', loading);
        readPrinterMediaButton.textContent = loading ? 'Consultando Zebra...' : 'Consultar tamaño/media';
    };

    const renderDotsSetting = (element, rawValue, dpi) => {
        if (!element) return;

        const millimeters = dotsToMillimeters(rawValue, dpi);
        element.textContent = millimeters === null
            ? (rawValue || 'No disponible')
            : `${rawValue} dots · ${millimeters.toFixed(2)} mm`;
    };

    const renderPrinterMedia = (settings) => {
        const reportedDpi = Number(settings.dpi);
        const templateDpi = Number(getElement('dpi')?.value);
        const dpi = Number.isFinite(reportedDpi) && reportedDpi > 0 ? reportedDpi : templateDpi;
        const widthMm = dotsToMillimeters(settings.printWidth, dpi);
        const heightMm = dotsToMillimeters(settings.labelLength, dpi);

        renderDotsSetting(printerPrintWidthEl, settings.printWidth, dpi);
        renderDotsSetting(printerLabelLengthEl, settings.labelLength, dpi);
        if (printerMediaTypeEl) printerMediaTypeEl.textContent = settings.mediaType || 'No disponible';
        if (printerPrintToneEl) printerPrintToneEl.textContent = settings.printTone || 'No disponible';

        if (printerResolutionEl) {
            printerResolutionEl.textContent = Number.isFinite(reportedDpi) && reportedDpi > 0
                ? `Resolución reportada por el cabezal: ${reportedDpi} dpi.`
                : `La impresora no reportó resolución; conversión calculada con ${templateDpi} dpi del template.`;
        }

        if (printerSizeMmEl) {
            printerSizeMmEl.textContent = widthMm !== null && heightMm !== null
                ? `${widthMm.toFixed(2)} × ${heightMm.toFixed(2)} mm`
                : 'No fue posible convertir el tamaño a milímetros.';
        }

        if (printerSizeComparisonEl) {
            const templateWidth = Number(getElement('width_mm')?.value);
            const templateHeight = Number(getElement('height_mm')?.value);
            const hasTemplateSize = Number.isFinite(templateWidth) && templateWidth > 0
                && Number.isFinite(templateHeight) && templateHeight > 0;

            printerSizeComparisonEl.textContent = hasTemplateSize
                ? `Template configurado: ${templateWidth.toFixed(2)} × ${templateHeight.toFixed(2)} mm.`
                : 'Captura ancho y alto del template para compararlos con Zebra.';
        }

        printerSizeSummaryEl?.classList.remove('hidden');
    };

    const applyLegacyLayoutScale = () => {
        const widthMm = Number(getElement('width_mm')?.value);
        const heightMm = Number(getElement('height_mm')?.value);
        const dpi = Number(getElement('dpi')?.value);

        if (!Number.isFinite(widthMm) || widthMm <= 0
            || !Number.isFinite(heightMm) || heightMm <= 0
            || !Number.isFinite(dpi) || dpi <= 0) {
            return false;
        }

        const currentLayout = Object.fromEntries(
            Object.entries(LEGACY_LAYOUT_DEFAULTS)
                .map(([field, fallback]) => [field, Number(getElement(field)?.value ?? fallback)]),
        );
        const scaledLayout = scaleLegacyLayout(
            currentLayout,
            millimetersToDots(widthMm, dpi, 820),
            millimetersToDots(heightMm, dpi, 400),
        );

        if (scaledLayout === currentLayout) return false;

        Object.entries(scaledLayout).forEach(([field, value]) => {
            const input = getElement(field);
            if (input) input.value = String(value);
        });

        return true;
    };

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

    readPrinterMediaButton?.addEventListener('click', () => {
        if (!printerService.ensureBrowserPrint()) return;

        setPrinterMediaLoading(true);
        setStatus('Consultando tamaño y configuración de media en Zebra...');
        printerService.readMediaSettings((settings, _, printerLabel) => {
            renderPrinterMedia(settings);
            setPrinterMediaLoading(false);
            setStatus(`Tamaño/media consultados correctamente en ${printerLabel}.`);
        }, (error) => {
            setPrinterMediaLoading(false);
            setStatus(error, true);
        });
    });

    ['dpi', 'width_mm', 'height_mm'].forEach((fieldId) => {
        getElement(fieldId)?.addEventListener('change', () => {
            if (applyLegacyLayoutScale()) layoutPreview.render();
        });
    });

    [
        'dummy_type', 'dpi', 'width_mm', 'height_mm', 'qr_orientation',
        'qr_x', 'qr_y', 'qr_magnification', 'fg_x', 'fg_y', 'fg_font_size',
        'job_x', 'job_y', 'job_font_size', 'consecutive_x', 'consecutive_y', 'consecutive_font_size',
        'title_x', 'title_y', 'title_font_size',
    ].forEach((fieldId) => {
        const field = getElement(fieldId);
        field?.addEventListener('input', layoutPreview.render);
        field?.addEventListener('change', layoutPreview.render);
    });

    applyLegacyLayoutScale();
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
