import { attachFabricToWindow, Canvas, Rect, Text } from '../lib/fabric-setup';

attachFabricToWindow();

const ORIENTATIONS = ['N', 'R', 'I', 'B'];
const ORIENTATION_ANGLES = {
    N: 0,
    R: 90,
    I: 180,
    B: 270,
};
const ORIENTATION_LABELS = {
    N: 'Normal',
    R: 'Rotada 90°',
    I: 'Invertida 180°',
    B: 'Bottom-up 270°',
};

const normalizeOrientation = (value, fallback = 'N') => {
    const normalized = String(value || fallback).trim().toUpperCase();

    return ORIENTATIONS.includes(normalized) ? normalized : fallback;
};

const getOrientationAngle = (value) => ORIENTATION_ANGLES[normalizeOrientation(value)] ?? 0;
const getOrientationLabel = (value) => ORIENTATION_LABELS[normalizeOrientation(value)] ?? ORIENTATION_LABELS.N;

const readInt = (selector, fallback) => {
    const rawValue = document.querySelector(selector)?.value;
    const parsedValue = Number.parseInt(rawValue ?? '', 10);

    return Number.isFinite(parsedValue) ? parsedValue : fallback;
};

const readFloat = (selector, fallback) => {
    const rawValue = document.querySelector(selector)?.value;
    const parsedValue = Number.parseFloat(rawValue ?? '');

    return Number.isFinite(parsedValue) ? parsedValue : fallback;
};

const formatEmeaSerialForPrint = (serial) => {
    const compactSerial = String(serial || '').replace(/\s+/g, '').trim();
    const emeaPattern = /^(.{4})(.{2})(.{2})(.{6})(.{5})$/;
    const parts = compactSerial.match(emeaPattern);

    if (!parts) {
        return compactSerial;
    }

    return [parts[1], parts[2], parts[3], parts[4], parts[5]].join(' ');
};

const initSkuTemplateConfigurationsForm = () => {
    const form = document.getElementById('sku-template-configuration-form');

    if (!form) {
        return;
    }

    const connectionSelect = document.getElementById('connection_type');
    const labelTypeSelect = document.getElementById('label_type');
    const ipWrapper = document.getElementById('printer-ip-wrapper');
    const ipInput = document.getElementById('default_printer_ip');
    const usbConnectedInput = document.getElementById('usb_connected');
    const statusBox = document.getElementById('printer-test-status');
    const testUsbButton = document.getElementById('test-usb-connection');
    const testPrintButton = document.getElementById('test-print');
    const previewZplButton = document.getElementById('preview-zpl');
    const zplPreviewWrapper = document.getElementById('zpl-preview-wrapper');
    const zplPreviewOutput = document.getElementById('zpl-preview-output');
    const printerNameInput = document.getElementById('default_printer_name');
    const usbPrintersWrapper = document.getElementById('usb-printers-wrapper');
    const usbPrinterSelect = document.getElementById('usb_printer_select');
    const skuSelect = document.querySelector('[name="label_sku_id"]');
    const serialStandardInput = document.querySelector('[name="serial_standard"]');
    const serialStandardDisplay = document.getElementById('serial_standard_display');
    const ratingWithQrCheckbox = document.querySelector('[name="rating_with_qr"]');
    const ratingHideSkuCheckbox = document.querySelector('[name="rating_hide_sku"]');
    const ratingQrToggleWrapper = document.getElementById('rating-qr-toggle-wrapper');
    const qrSections = document.querySelectorAll('[data-layout-section="qr"]');
    const serialTextSections = document.querySelectorAll('[data-layout-section="serial-text"]');
    const ratingTextSections = document.querySelectorAll('[data-layout-section="rating"]');
    const qrLayoutTitle = document.getElementById('qr-layout-title');
    const qrLayoutDescription = document.getElementById('qr-layout-description');
    const snPrefixWrapper = document.getElementById('sn-prefix-wrapper');
    const qrContentModeSelect = document.getElementById('qr_content_mode');
    const qrCustomFieldsWrapper = document.getElementById('qr-custom-fields-wrapper');
    const qrSeparatorSelect = document.getElementById('qr_separator');
    const qrSerialStyleSelect = document.getElementById('qr_serial_style');
    const skuStandardFilterButtons = document.querySelectorAll('[data-sku-standard-filter]');
    const livePreviewQr = document.getElementById('live-preview-qr');
    const livePreviewSkuLine = document.getElementById('live-preview-sku-line');
    const livePreviewSnLine = document.getElementById('live-preview-sn-line');
    const livePreviewStandard = document.getElementById('live-preview-standard');
    const livePreviewLabelType = document.getElementById('live-preview-label-type');
    const livePreviewQrMode = document.getElementById('live-preview-qr-mode');
    const livePreviewSerialStyle = document.getElementById('live-preview-serial-style');
    const livePreviewQrPayload = document.getElementById('live-preview-qr-payload');
    const livePreviewWarning = document.getElementById('live-preview-warning');
    const layoutCanvasElement = document.getElementById('sku-layout-preview-canvas');
    const layoutContextTitle = document.getElementById('layout-context-title');
    const layoutContextDescription = document.getElementById('layout-context-description');
    const layoutActiveElements = document.getElementById('layout-active-elements');
    const layoutOrientationSummary = document.getElementById('layout-orientation-summary');
    const layoutScaleSummary = document.getElementById('layout-scale-summary');
    const layoutOutOfBoundsWarning = document.getElementById('layout-out-of-bounds-warning');
    const templateDotsSummary = document.getElementById('template-dots-summary');

    const defaultSerialUl = form.dataset.defaultSerialUl || 'L36BH2606007A7';
    const defaultSerialEmea = form.dataset.defaultSerialEmea || '50555401123456A1234';
    const defaultSerialAnz = form.dataset.defaultSerialAnz || 'AF02F2019 A 00001 A2026';
    const defaultSku = form.dataset.defaultSku || '2978-OCUT';
    const skuLayoutGroups = document.querySelectorAll('[data-layout-group="sku"]');

    let selectedDevice = null;
    const layoutCanvas = layoutCanvasElement ? new Canvas(layoutCanvasElement, {
        selection: false,
        preserveObjectStacking: true,
    }) : null;
    let previewObjects = null;

    const setStatus = (message, isError = false) => {
        if (!statusBox) {
            return;
        }

        statusBox.textContent = message;
        statusBox.classList.toggle('text-red-700', isError);
        statusBox.classList.toggle('text-slate-700', !isError);
    };

    const getSelectedSkuCode = () => skuSelect?.selectedOptions?.[0]?.dataset?.skuCode || defaultSku;
    const getSelectedSkuData = () => skuSelect?.selectedOptions?.[0]?.dataset || {};
    const getSelectedSkuStandard = () => String(skuSelect?.selectedOptions?.[0]?.dataset?.serialStandard || 'UL').toUpperCase();
    const getSelectedSkuExampleSerial = () => String(skuSelect?.selectedOptions?.[0]?.dataset?.exampleSerial || '').trim();
    const getSelectedAnzCustomerToolCode = () => String(skuSelect?.selectedOptions?.[0]?.dataset?.anzCustomerToolCode || '').trim().toUpperCase();
    const isRatingWithQrEnabled = () => labelTypeSelect?.value === 'rating' && Boolean(ratingWithQrCheckbox?.checked);
    const getSelectedSerialStandard = () => String(serialStandardInput?.value || getSelectedSkuStandard()).toUpperCase();
    const isEmeaOrAnzRatingWithQr = () => isRatingWithQrEnabled() && ['EMEA', 'ANZ'].includes(getSelectedSerialStandard());
    const hideSkuOnRatingWithQr = () => isRatingWithQrEnabled() && (isEmeaOrAnzRatingWithQr() || Boolean(ratingHideSkuCheckbox?.checked));
    const getQrContentMode = () => String(qrContentModeSelect?.value || 'auto').toLowerCase();
    const getQrSerialStyle = () => String(qrSerialStyleSelect?.value || 'as_is').toLowerCase();

    const syncSerialStandardFromSku = () => {
        const standard = getSelectedSkuStandard();

        if (serialStandardInput) {
            serialStandardInput.value = standard;
        }

        if (serialStandardDisplay) {
            serialStandardDisplay.value = standard;
        }
    };

    const setSkuStandardFilter = (standard) => {
        if (!skuSelect) {
            return;
        }

        const nextStandard = String(standard || 'UL').toUpperCase();
        const options = Array.from(skuSelect.options || []);
        let currentOptionStillVisible = false;

        options.forEach((option) => {
            const optionStandard = String(option.dataset.serialStandard || 'UL').toUpperCase();
            const visible = optionStandard === nextStandard;

            option.hidden = !visible;
            option.disabled = !visible;

            if (visible && option.selected) {
                currentOptionStillVisible = true;
            }
        });

        if (!currentOptionStillVisible) {
            const firstVisible = options.find((option) => !option.hidden && !option.disabled);

            if (firstVisible) {
                firstVisible.selected = true;
            }
        }

        skuStandardFilterButtons.forEach((button) => {
            const isActive = String(button.dataset.skuStandardFilter || '').toUpperCase() === nextStandard;

            button.classList.toggle('bg-slate-900', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('text-slate-600', !isActive);
            button.classList.toggle('hover:bg-slate-100', !isActive);
        });

        syncSerialStandardFromSku();
    };

    const toggleRatingQrControl = () => {
        const isRating = labelTypeSelect?.value === 'rating';

        if (ratingQrToggleWrapper) {
            ratingQrToggleWrapper.style.display = isRating ? 'block' : 'none';
        }
    };

    const renderUsbPrinterOptions = (devices = []) => {
        if (!usbPrinterSelect) {
            return;
        }

        usbPrinterSelect.innerHTML = '';

        if (!devices.length) {
            const option = document.createElement('option');

            option.value = '';
            option.textContent = 'No se detectaron impresoras USB';
            usbPrinterSelect.appendChild(option);

            return;
        }

        const currentName = String(printerNameInput?.value || '').trim();

        devices.forEach((device, index) => {
            const option = document.createElement('option');
            const name = device.name || `Impresora USB ${index + 1}`;

            option.value = name;
            option.textContent = `${name} (${device.uid || device.connection || 'USB'})`;
            option.selected = currentName ? currentName === name : index === 0;
            usbPrinterSelect.appendChild(option);
        });

        if (printerNameInput && usbPrinterSelect.value) {
            printerNameInput.value = usbPrinterSelect.value;
        }
    };

    const toggleConnectionFields = () => {
        const isNetwork = connectionSelect?.value === 'network';

        if (ipWrapper) {
            ipWrapper.style.display = isNetwork ? 'block' : 'none';
        }

        if (usbPrintersWrapper) {
            usbPrintersWrapper.style.display = isNetwork ? 'none' : 'block';
        }

        ipInput?.toggleAttribute('required', isNetwork);

        if (usbConnectedInput) {
            usbConnectedInput.value = isNetwork ? '1' : '0';
        }
    };

    const toggleLayoutSections = () => {
        toggleRatingQrControl();

        const isSerial = labelTypeSelect?.value === 'serial';
        const isRatingWithQr = labelTypeSelect?.value === 'rating' && Boolean(ratingWithQrCheckbox?.checked);
        const hideSkuLayout = hideSkuOnRatingWithQr();
        const requiresQr = isSerial || isRatingWithQr;
        const isCustomQrMode = getQrContentMode() === 'custom';

        qrSections.forEach((section) => {
            section.style.display = requiresQr ? 'block' : 'none';

            section.querySelectorAll('input, select').forEach((field) => {
                if (field.name.startsWith('qr_custom_field_')) {
                    field.required = requiresQr && isCustomQrMode;

                    return;
                }

                field.required = requiresQr;
            });
        });

        serialTextSections.forEach((section) => {
            section.style.display = isSerial ? 'block' : 'none';
        });

        ratingTextSections.forEach((section) => {
            section.style.display = isSerial ? 'none' : 'block';

            section.querySelectorAll('input, select').forEach((field) => {
                if (field.name.startsWith('serial_')) {
                    field.required = true;
                }
            });
        });

        skuLayoutGroups.forEach((group) => {
            group.style.display = hideSkuLayout ? 'none' : 'block';
        });

        if (snPrefixWrapper) {
            snPrefixWrapper.style.display = isSerial ? 'block' : 'none';
        }

        if (qrLayoutTitle) {
            qrLayoutTitle.textContent = isRatingWithQr
                ? 'Configuración etiqueta Rating con QR'
                : 'Configuración etiqueta Serial con QR';
        }

        if (qrLayoutDescription) {
            qrLayoutDescription.textContent = isRatingWithQr
                ? 'El QR codifica el serial completo para la etiqueta Rating. En EMEA/ANZ o cuando actives "Ocultar SKU", se imprime solo SN + QR del SN.'
                : 'El QR codifica el serial completo; además se muestra el SKU grande y el SN en texto pequeño.';
        }

        if (qrCustomFieldsWrapper) {
            qrCustomFieldsWrapper.style.display = getQrContentMode() === 'custom' ? 'block' : 'none';
        }

        setStatus(isSerial
            ? 'Configurando etiqueta Serial: usa B (QR) y C (SKU + SN pequeño).'
            : (isRatingWithQr
                ? 'Configurando etiqueta Rating con QR: usa A (texto principal) y B (QR), no C.'
                : 'Configurando etiqueta Rating simple: usa solo A (texto principal).'));
    };

    const ensureBrowserPrint = () => {
        if (!window.BrowserPrint) {
            setStatus('No se encontró BrowserPrint para pruebas de impresora.', true);

            return false;
        }

        return true;
    };

    const connectUsb = () => {
        if (!ensureBrowserPrint()) {
            return;
        }

        setStatus('Buscando impresoras USB...');

        window.BrowserPrint.getLocalDevices((devices) => {
            const usbPrinters = (devices || []).filter((candidate) => {
                return candidate.deviceType === 'printer'
                    && String(candidate.connection || '').toLowerCase().includes('usb');
            });

            renderUsbPrinterOptions(usbPrinters);

            if (!usbPrinters.length) {
                selectedDevice = null;
                usbConnectedInput.value = '0';
                setStatus('No se detectó impresora USB conectada.', true);

                return;
            }

            const selectedName = String(usbPrinterSelect?.value || '').trim();
            const matchedDevice = usbPrinters.find((device) => device.name === selectedName) || usbPrinters[0];

            selectedDevice = matchedDevice;
            usbConnectedInput.value = '1';
            printerNameInput.value = matchedDevice.name || printerNameInput.value;
            setStatus(`Conexión USB OK: ${usbPrinters.length} impresora(s) detectada(s). Usando: ${printerNameInput.value}`);
        }, (error) => {
            selectedDevice = null;
            usbConnectedInput.value = '0';
            renderUsbPrinterOptions([]);
            setStatus(`Error al detectar impresoras USB: ${error}`, true);
        }, 'printer');
    };

    const getQrSeparator = () => {
        const value = String(qrSeparatorSelect?.value || 'pipe').toLowerCase();

        if (value === 'space') {
            return ' ';
        }

        if (value === 'none') {
            return '';
        }

        return ' | ';
    };

    const resolveTokenValue = (token, serial, ratingSerial) => {
        const skuData = getSelectedSkuData();

        const values = {
            fixed_103: skuData.assemblyPartNumber || '103',
            serial_full: applySerialStyle(serial),
            rating_qr_code: applySerialStyle(ratingSerial),
            sku: getSelectedSkuCode(),
            label_part_number: skuData.labelPartNumber || '',
            console_sku: skuData.consoleSku || '',
            assembly_part_number: skuData.assemblyPartNumber || '',
            packaging_part_number: skuData.packagingPartNumber || '',
            emea_sku: skuData.emeaSku || '',
            anz_sku: skuData.anzSku || '',
            anz_customer_tool_code: getSelectedAnzCustomerToolCode(),
        };

        return values[token] || '';
    };

    const applySerialStyle = (value) => {
        const style = getQrSerialStyle();
        const compact = String(value || '').replace(/[\s|]+/g, '').trim().toUpperCase();

        if (style === 'compact') {
            return compact;
        }

        if (style === 'segmented' && compact.length === 19) {
            return `${compact.slice(0, 4)} ${compact.slice(4, 6)} ${compact.slice(6, 8)} ${compact.slice(8, 14)} ${compact.slice(14)}`;
        }

        return String(value || '').trim();
    };

    const resolveQrPayload = (labelType, serial) => {
        const mode = getQrContentMode();
        const ratingSerial = serial;

        if (mode === 'serial_full') {
            return applySerialStyle(serial);
        }

        if (mode === 'rating_qr') {
            return applySerialStyle(ratingSerial);
        }

        if (mode === 'anz_customer_tool_serial') {
            if (labelType !== 'rating') {
                return applySerialStyle(serial);
            }

            const customerToolCode = getSelectedAnzCustomerToolCode();
            const serialValue = applySerialStyle(ratingSerial);

            if (!customerToolCode) {
                return serialValue;
            }

            return `${customerToolCode}${getQrSeparator()}${serialValue}`;
        }

        if (mode === 'custom') {
            const tokens = [1, 2, 3]
                .map((index) => document.querySelector(`[name="qr_custom_field_${index}"]`)?.value || '')
                .filter((token) => token.length > 0)
                .map((token) => resolveTokenValue(token, serial, ratingSerial))
                .filter((value) => value.length > 0);

            if (tokens.length > 0) {
                return tokens.join(getQrSeparator());
            }
        }

        return labelType === 'rating' ? applySerialStyle(ratingSerial) : applySerialStyle(serial);
    };

    const renderLivePreviewQr = (payload, shouldRenderQr) => {
        if (!livePreviewQr) {
            return;
        }

        livePreviewQr.innerHTML = '';
        livePreviewQr.classList.remove('text-red-600');
        livePreviewQr.classList.add('text-slate-500');

        if (!shouldRenderQr) {
            livePreviewQr.textContent = 'Sin QR para la configuración actual.';

            return;
        }

        if (!payload) {
            livePreviewQr.textContent = 'No hay payload QR disponible.';

            return;
        }

        if (typeof window.QRCode !== 'function') {
            livePreviewQr.textContent = 'No se pudo renderizar QR (QRCode no disponible).';
            livePreviewQr.classList.remove('text-slate-500');
            livePreviewQr.classList.add('text-red-600');

            return;
        }

        // eslint-disable-next-line no-new
        new window.QRCode(livePreviewQr, {
            text: payload,
            width: 132,
            height: 132,
            correctLevel: window.QRCode.CorrectLevel.M,
        });
    };

    const updateLivePreview = () => {
        if (!livePreviewSnLine || !livePreviewSkuLine || !livePreviewQrPayload) {
            return;
        }

        const labelType = labelTypeSelect?.value || 'serial';
        const isRatingWithQr = isRatingWithQrEnabled();
        const serialStandard = getSelectedSerialStandard();
        const skuExampleSerial = getSelectedSkuExampleSerial();
        const defaultSerialByStandard = {
            UL: defaultSerialUl,
            EMEA: defaultSerialEmea,
            ANZ: defaultSerialAnz,
        };
        const serial = skuExampleSerial || defaultSerialByStandard[serialStandard] || defaultSerialUl;
        const serialPrint = serialStandard === 'EMEA' ? formatEmeaSerialForPrint(serial) : serial;
        const hideSku = hideSkuOnRatingWithQr();
        const shouldRenderQr = labelType === 'serial' || isRatingWithQr;
        const shouldRenderSku = shouldRenderQr && !hideSku;
        const snPrefix = (document.querySelector('[name="sn_prefix"]')?.value ?? 'SN:').trim();
        const snLine = labelType === 'rating'
            ? serialPrint
            : (snPrefix ? `${snPrefix} ${serialPrint}` : serialPrint);
        const qrPayload = shouldRenderQr ? resolveQrPayload(labelType, serial) : '';
        const qrMode = getQrContentMode();
        const serialStyle = getQrSerialStyle();

        livePreviewSkuLine.textContent = shouldRenderSku
            ? `SKU: ${getSelectedSkuCode()}`
            : 'SKU: (oculto para esta configuración)';
        livePreviewSnLine.textContent = `SN: ${snLine}`;
        livePreviewQrPayload.textContent = qrPayload || '(sin QR)';

        if (livePreviewStandard) {
            livePreviewStandard.textContent = serialStandard;
        }

        if (livePreviewLabelType) {
            livePreviewLabelType.textContent = labelType === 'rating' ? 'Rating' : 'Serial';
        }

        if (livePreviewQrMode) {
            livePreviewQrMode.textContent = shouldRenderQr ? qrMode : 'sin_qr';
        }

        if (livePreviewSerialStyle) {
            livePreviewSerialStyle.textContent = shouldRenderQr ? serialStyle : '(no aplica)';
        }

        if (livePreviewWarning) {
            const anzToolCodeMissing = qrMode === 'anz_customer_tool_serial'
                && serialStandard === 'ANZ'
                && !getSelectedAnzCustomerToolCode();

            if (anzToolCodeMissing) {
                livePreviewWarning.textContent = 'Falta customer_tool_code ANZ; se usará solo el serial en el QR.';
                livePreviewWarning.classList.remove('hidden');
            } else {
                livePreviewWarning.classList.add('hidden');
                livePreviewWarning.textContent = '';
            }
        }

        renderLivePreviewQr(qrPayload, shouldRenderQr);
        renderFabricLayoutPreview({ shouldRenderQr, shouldRenderSku, snLine });
    };

    const getOrientationFromField = (fieldName, fallback = 'N') => {
        return normalizeOrientation(document.querySelector(`[name="${fieldName}"]`)?.value, fallback);
    };

    const getPhysicalOrientationSummary = (usesRatingTextLayout = false, shouldRenderQr = false, shouldRenderSku = false) => {
        const textOrientation = usesRatingTextLayout
            ? getOrientationFromField('serial_orientation')
            : getOrientationFromField('sn_orientation');
        const skuOrientation = getOrientationFromField('sku_orientation');
        const qrOrientation = getOrientationFromField('qr_orientation');
        const parts = [];

        if (shouldRenderQr) {
            parts.push(`QR: ${getOrientationLabel(qrOrientation)}`);
        }

        if (shouldRenderSku) {
            parts.push(`SKU: ${getOrientationLabel(skuOrientation)}`);
        }

        parts.push(`${usesRatingTextLayout ? 'Rating' : 'SN'}: ${getOrientationLabel(textOrientation)}`);

        return parts.join(' · ');
    };

    const setObjectOrientation = (object, orientation) => {
        if (!object) {
            return;
        }

        object.set({
            angle: getOrientationAngle(orientation),
            originX: 'left',
            originY: 'top',
            centeredRotation: false,
        });

        object.setCoords?.();
    };

    const getTemplateMetrics = () => {
        const canvasWidth = layoutCanvasElement?.width || 900;
        const canvasHeight = layoutCanvasElement?.height || 420;
        const dpi = Math.max(readInt('#template_dpi', 203), 1);
        const widthMm = Math.max(readFloat('#template_width_mm', 0), 0);
        const heightMm = Math.max(readFloat('#template_height_mm', 0), 0);
        const hasPhysicalSize = widthMm > 0 && heightMm > 0;
        const widthDots = hasPhysicalSize ? Math.round((widthMm / 25.4) * dpi) : 900;
        const heightDots = hasPhysicalSize ? Math.round((heightMm / 25.4) * dpi) : 420;
        const reservedTop = 62;
        const reservedBottom = 24;
        const reservedX = 32;
        const availableWidth = Math.max(canvasWidth - (reservedX * 2), 120);
        const availableHeight = Math.max(canvasHeight - reservedTop - reservedBottom, 120);
        const rawScale = Math.min(availableWidth / Math.max(widthDots, 1), availableHeight / Math.max(heightDots, 1));
        const scale = Math.min(Math.max(rawScale, 0.05), 3);
        const labelWidthPx = Math.max(widthDots * scale, 1);
        const labelHeightPx = Math.max(heightDots * scale, 1);
        const labelLeft = Math.round((canvasWidth - labelWidthPx) / 2);
        const labelTop = Math.round(reservedTop + ((availableHeight - labelHeightPx) / 2));

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
            labelWidthPx,
            labelHeightPx,
            labelLeft,
            labelTop,
        };
    };

    const toCanvasPoint = (x, y, metrics = getTemplateMetrics()) => ({
        x: metrics.labelLeft + (x * metrics.scale),
        y: metrics.labelTop + (y * metrics.scale),
    });

    const updateTemplateDotsSummary = (metrics = getTemplateMetrics()) => {
        const sizeText = metrics.hasPhysicalSize
            ? `${metrics.widthMm} mm × ${metrics.heightMm} mm`
            : 'sin medidas físicas capturadas';
        const dotsText = `${metrics.widthDots} × ${metrics.heightDots} dots`;
        const scaleText = `escala visual ${metrics.scale.toFixed(2)}x`;

        if (templateDotsSummary) {
            templateDotsSummary.textContent = `Tamaño real calculado: ${dotsText} · ${sizeText} · ${metrics.dpi} DPI · ${scaleText}.`;
        }

        if (layoutScaleSummary) {
            layoutScaleSummary.textContent = `Etiqueta: ${dotsText} · ${scaleText}`;
        }
    };

    const getObjectCenter = (object) => {
        if (!object) {
            return { x: 0, y: 0 };
        }

        if (typeof object.getCenterPoint === 'function') {
            const point = object.getCenterPoint();

            return { x: point.x, y: point.y };
        }

        const width = (object.width || 0) * (object.scaleX || 1);
        const height = (object.height || 0) * (object.scaleY || 1);

        return {
            x: (object.left || 0) + (width / 2),
            y: (object.top || 0) + (height / 2),
        };
    };

    const updateQrLabelPosition = () => {
        if (!previewObjects?.qr || !previewObjects?.qrLabel) {
            return;
        }

        const center = getObjectCenter(previewObjects.qr);

        previewObjects.qrLabel.set({
            left: center.x,
            top: center.y,
            angle: previewObjects.qr.angle || 0,
            visible: previewObjects.qr.visible,
        });

        previewObjects.qrLabel.setCoords?.();
    };

    const updateLayoutContext = ({ labelType, usesRatingTextLayout, shouldRenderQr, shouldRenderSku }) => {
        if (layoutContextTitle) {
            layoutContextTitle.textContent = labelType === 'rating'
                ? 'Acomoda el layout físico de Rating'
                : 'Acomoda el layout físico de Serial';
        }

        if (layoutContextDescription) {
            layoutContextDescription.textContent = labelType === 'rating'
                ? 'Para Rating usa A como texto principal. Si activas QR, también se muestra B. La sección C queda oculta para evitar confusión.'
                : 'Para Serial usa B para QR y C para SKU + SN. Puedes arrastrar los elementos y después ajustar X/Y con precisión.';
        }

        if (layoutActiveElements) {
            const activeElements = [];

            if (usesRatingTextLayout) {
                activeElements.push('A · Texto Rating');
            } else {
                activeElements.push('C · SN pequeño');
            }

            if (shouldRenderQr) {
                activeElements.push('B · QR');
            }

            if (shouldRenderSku) {
                activeElements.push('C · SKU visible');
            }

            layoutActiveElements.textContent = `Activo ahora: ${activeElements.join(' + ')}`;
        }

        if (layoutOrientationSummary) {
            layoutOrientationSummary.textContent = getPhysicalOrientationSummary(usesRatingTextLayout, shouldRenderQr, shouldRenderSku);
        }
    };

    const createPreviewObjects = () => {
        if (!layoutCanvas) {
            return null;
        }

        const metrics = getTemplateMetrics();

        const canvasBackground = new Rect({
            left: 0,
            top: 0,
            width: metrics.canvasWidth,
            height: metrics.canvasHeight,
            fill: '#f8fafc',
            selectable: false,
            evented: false,
        });

        const labelArea = new Rect({
            left: metrics.labelLeft,
            top: metrics.labelTop,
            width: metrics.labelWidthPx,
            height: metrics.labelHeightPx,
            fill: '#ffffff',
            stroke: '#cbd5e1',
            strokeWidth: 1,
            strokeDashArray: [6, 4],
            selectable: false,
            evented: false,
            originX: 'left',
            originY: 'top',
        });

        const originMarkerX = new Rect({
            left: metrics.labelLeft,
            top: metrics.labelTop,
            width: 22,
            height: 2,
            fill: '#ef4444',
            selectable: false,
            evented: false,
        });

        const originMarkerY = new Rect({
            left: metrics.labelLeft,
            top: metrics.labelTop,
            width: 2,
            height: 22,
            fill: '#ef4444',
            selectable: false,
            evented: false,
        });

        const guideTitle = new Text('Área física de etiqueta', {
            left: 18,
            top: 14,
            fontSize: 13,
            fontWeight: 'bold',
            fill: '#64748b',
            selectable: false,
            evented: false,
        });

        const guideText = new Text('El rectángulo se escala con DPI, ancho y alto. X/Y siguen siendo dots reales.', {
            left: 18,
            top: 34,
            fontSize: 11,
            fill: '#94a3b8',
            selectable: false,
            evented: false,
        });

        const qr = new Rect({
            left: metrics.labelLeft + 30,
            top: metrics.labelTop + 30,
            width: 120,
            height: 120,
            fill: '#f1f5f9',
            stroke: '#0f172a',
            strokeWidth: 1,
            selectable: true,
            evented: true,
            hasControls: false,
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true,
            originX: 'left',
            originY: 'top',
        });

        qr.set({
            data: {
                fieldX: 'qr_position_x',
                fieldY: 'qr_position_y',
                previewType: 'qr',
            },
        });

        const qrLabel = new Text('QR', {
            left: metrics.labelLeft + 90,
            top: metrics.labelTop + 90,
            fontSize: 20,
            fontWeight: 'bold',
            fill: '#0f172a',
            selectable: false,
            evented: false,
            originX: 'center',
            originY: 'center',
        });

        const sku = new Text('SKU: —', {
            left: metrics.labelLeft + 170,
            top: metrics.labelTop + 70,
            fontSize: 30,
            fontWeight: 'bold',
            fill: '#0f172a',
            selectable: true,
            evented: true,
            hasControls: false,
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true,
            originX: 'left',
            originY: 'top',
        });

        sku.set({
            data: {
                fieldX: 'sku_position_x',
                fieldY: 'sku_position_y',
                previewType: 'sku',
            },
        });

        const sn = new Text('SN: —', {
            left: metrics.labelLeft + 170,
            top: metrics.labelTop + 120,
            fontSize: 22,
            fontFamily: 'monospace',
            fill: '#0f172a',
            selectable: true,
            evented: true,
            hasControls: false,
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true,
            originX: 'left',
            originY: 'top',
        });

        sn.set({
            data: {
                fieldX: 'sn_position_x',
                fieldY: 'sn_position_y',
                previewType: 'sn',
            },
        });

        layoutCanvas.add(
            canvasBackground,
            labelArea,
            originMarkerX,
            originMarkerY,
            guideTitle,
            guideText,
            qr,
            qrLabel,
            sku,
            sn,
        );

        layoutCanvas.sendObjectToBack(canvasBackground);
        labelArea.moveTo?.(1);
        originMarkerX.moveTo?.(2);
        originMarkerY.moveTo?.(3);

        return {
            canvasBackground,
            labelArea,
            originMarkerX,
            originMarkerY,
            guideTitle,
            guideText,
            qr,
            qrLabel,
            sku,
            sn,
        };
    };

    const syncInputsFromObject = (object) => {
        const fieldX = object?.data?.fieldX;
        const fieldY = object?.data?.fieldY;

        if (!fieldX || !fieldY) {
            return;
        }

        const metrics = getTemplateMetrics();
        const xInput = document.querySelector(`[name="${fieldX}"]`);
        const yInput = document.querySelector(`[name="${fieldY}"]`);
        const xValue = Math.max(0, Math.round(((object.left || 0) - metrics.labelLeft) / metrics.scale));
        const yValue = Math.max(0, Math.round(((object.top || 0) - metrics.labelTop) / metrics.scale));

        if (xInput) {
            xInput.value = String(xValue);
        }

        if (yInput) {
            yInput.value = String(yValue);
        }
    };

    const getPreviewTypeLabel = (previewType) => {
        const labels = {
            qr: 'QR',
            sku: 'SKU',
            sn: labelTypeSelect?.value === 'rating' ? 'Rating' : 'SN',
        };

        return labels[previewType] || previewType;
    };

    const isObjectOutsideLabel = (object, metrics) => {
        if (!object || object.visible === false || !object.data?.previewType) {
            return false;
        }

        object.setCoords?.();

        const bounds = typeof object.getBoundingRect === 'function'
            ? object.getBoundingRect()
            : {
                left: object.left || 0,
                top: object.top || 0,
                width: (object.width || 0) * (object.scaleX || 1),
                height: (object.height || 0) * (object.scaleY || 1),
            };

        const labelRight = metrics.labelLeft + metrics.labelWidthPx;
        const labelBottom = metrics.labelTop + metrics.labelHeightPx;
        const tolerance = 1;

        return bounds.left < metrics.labelLeft - tolerance
            || bounds.top < metrics.labelTop - tolerance
            || (bounds.left + bounds.width) > labelRight + tolerance
            || (bounds.top + bounds.height) > labelBottom + tolerance;
    };

    const updateOutOfBoundsWarning = (metrics = getTemplateMetrics()) => {
        if (!previewObjects) {
            return;
        }

        const objectsToValidate = [previewObjects.qr, previewObjects.sku, previewObjects.sn].filter(Boolean);
        const outsideObjects = objectsToValidate.filter((object) => isObjectOutsideLabel(object, metrics));
        const outsideLabels = outsideObjects.map((object) => getPreviewTypeLabel(object.data?.previewType));

        objectsToValidate.forEach((object) => {
            const isOutside = outsideObjects.includes(object);

            object.set({
                stroke: isOutside ? '#f59e0b' : '#0f172a',
                strokeWidth: isOutside ? 2 : 1,
            });
        });

        if (layoutOutOfBoundsWarning) {
            if (outsideLabels.length > 0) {
                layoutOutOfBoundsWarning.textContent = `Advertencia: este elemento está fuera del área física de la etiqueta. Revisa: ${outsideLabels.join(', ')}.`;
                layoutOutOfBoundsWarning.classList.remove('hidden');
            } else {
                layoutOutOfBoundsWarning.textContent = 'Advertencia: este elemento está fuera del área física de la etiqueta.';
                layoutOutOfBoundsWarning.classList.add('hidden');
            }
        }
    };

    const renderFabricLayoutPreview = ({ shouldRenderQr, shouldRenderSku, snLine }) => {
        if (!layoutCanvas) {
            return;
        }

        if (!previewObjects) {
            previewObjects = createPreviewObjects();
        }

        if (!previewObjects) {
            return;
        }

        const metrics = getTemplateMetrics();
        updateTemplateDotsSummary(metrics);

        const labelType = labelTypeSelect?.value || 'serial';
        const usesRatingTextLayout = labelType === 'rating';
        const qrX = readInt('[name="qr_position_x"]', 30);
        const qrY = readInt('[name="qr_position_y"]', 30);
        const snX = usesRatingTextLayout ? readInt('[name="serial_position_x"]', 40) : readInt('[name="sn_position_x"]', 170);
        const snY = usesRatingTextLayout ? readInt('[name="serial_position_y"]', 40) : readInt('[name="sn_position_y"]', 95);
        const skuX = readInt('[name="sku_position_x"]', 170);
        const skuY = readInt('[name="sku_position_y"]', 35);
        const skuFontSize = readInt('[name="sku_font_size"]', 42);
        const snFontSize = usesRatingTextLayout ? readInt('[name="serial_font_size"]', 40) : readInt('[name="sn_font_size"]', 22);
        const qrMagnification = Math.min(Math.max(readInt('[name="qr_magnification"]', 4), 1), 10);
        const qrSize = Math.max(72, qrMagnification * 34);
        const qrOrientation = getOrientationFromField('qr_orientation');
        const skuOrientation = getOrientationFromField('sku_orientation');
        const textOrientation = usesRatingTextLayout
            ? getOrientationFromField('serial_orientation')
            : getOrientationFromField('sn_orientation');
        const qrPoint = toCanvasPoint(qrX, qrY, metrics);
        const skuPoint = toCanvasPoint(skuX, skuY, metrics);
        const snPoint = toCanvasPoint(snX, snY, metrics);

        previewObjects.canvasBackground?.set({
            width: metrics.canvasWidth,
            height: metrics.canvasHeight,
        });

        previewObjects.labelArea?.set({
            left: metrics.labelLeft,
            top: metrics.labelTop,
            width: metrics.labelWidthPx,
            height: metrics.labelHeightPx,
        });

        previewObjects.originMarkerX?.set({
            left: metrics.labelLeft,
            top: metrics.labelTop,
            width: Math.min(22, metrics.labelWidthPx),
        });

        previewObjects.originMarkerY?.set({
            left: metrics.labelLeft,
            top: metrics.labelTop,
            height: Math.min(22, metrics.labelHeightPx),
        });

        previewObjects.guideText?.set({
            text: `Origen ^FO 0,0 · ${metrics.widthDots} x ${metrics.heightDots} dots · escala ${metrics.scale.toFixed(2)}x`,
        });

        previewObjects.sn.set({
            data: {
                fieldX: usesRatingTextLayout ? 'serial_position_x' : 'sn_position_x',
                fieldY: usesRatingTextLayout ? 'serial_position_y' : 'sn_position_y',
                previewType: 'sn',
            },
        });

        previewObjects.qr.set({
            left: qrPoint.x,
            top: qrPoint.y,
            width: qrSize * metrics.scale,
            height: qrSize * metrics.scale,
            visible: shouldRenderQr,
        });

        setObjectOrientation(previewObjects.qr, qrOrientation);
        updateQrLabelPosition();

        previewObjects.sku.set({
            left: skuPoint.x,
            top: skuPoint.y,
            fontSize: Math.max(7, Math.round(skuFontSize * metrics.scale)),
            text: shouldRenderSku ? `SKU: ${getSelectedSkuCode()}` : 'SKU: (oculto)',
            visible: shouldRenderSku,
        });

        setObjectOrientation(previewObjects.sku, skuOrientation);

        previewObjects.sn.set({
            left: snPoint.x,
            top: snPoint.y,
            fontSize: Math.max(7, Math.round(snFontSize * metrics.scale)),
            text: usesRatingTextLayout ? `Rating: ${snLine}` : `SN: ${snLine}`,
            visible: true,
        });

        setObjectOrientation(previewObjects.sn, textOrientation);

        updateLayoutContext({
            labelType,
            usesRatingTextLayout,
            shouldRenderQr,
            shouldRenderSku,
        });

        updateOutOfBoundsWarning(metrics);
        layoutCanvas.requestRenderAll();
    };

    if (layoutCanvas) {
        const handleLayoutObjectMove = (event) => {
            const movedObject = event.target;

            if (!movedObject?.data) {
                return;
            }

            movedObject.set({
                left: Math.max(0, movedObject.left || 0),
                top: Math.max(0, movedObject.top || 0),
            });

            movedObject.setCoords?.();

            if (movedObject?.data?.previewType === 'qr') {
                updateQrLabelPosition();
            }

            syncInputsFromObject(movedObject);
            updateOutOfBoundsWarning();
            layoutCanvas.requestRenderAll();
        };

        layoutCanvas.on('object:moving', handleLayoutObjectMove);
        layoutCanvas.on('object:modified', handleLayoutObjectMove);
    }

    const buildTestZpl = () => {
        const labelType = labelTypeSelect?.value;
        const isRatingWithQr = isRatingWithQrEnabled();
        const serialStandard = getSelectedSerialStandard();
        const skuExampleSerial = getSelectedSkuExampleSerial();

        const defaultSerialByStandard = {
            UL: defaultSerialUl,
            EMEA: defaultSerialEmea,
            ANZ: defaultSerialAnz,
        };

        const serial = skuExampleSerial || defaultSerialByStandard[serialStandard] || defaultSerialUl;
        const serialPrint = serialStandard === 'EMEA' ? formatEmeaSerialForPrint(serial) : serial;
        const hideSkuOnEmeaRating = hideSkuOnRatingWithQr();

        if (labelType !== 'serial' && !isRatingWithQr) {
            const x = readInt('[name="serial_position_x"]', 40);
            const y = readInt('[name="serial_position_y"]', 40);
            const fontSize = readInt('[name="serial_font_size"]', 40);
            const orientation = normalizeOrientation(document.querySelector('[name="serial_orientation"]')?.value, 'N');

            return [
                '^XA',
                '^CI28',
                `^FO${x},${y}`,
                `^A0${orientation},${fontSize},${fontSize}`,
                `^FD${serialPrint}^FS`,
                '^XZ',
            ].join('\n');
        }

        const qrX = readInt('[name="qr_position_x"]', 30);
        const qrY = readInt('[name="qr_position_y"]', 30);
        const qrOrientation = normalizeOrientation(document.querySelector('[name="qr_orientation"]')?.value, 'N');
        const qrMagnification = Math.min(Math.max(readInt('[name="qr_magnification"]', 4), 1), 10);
        const useRatingTextLayout = labelType === 'rating';
        const snX = useRatingTextLayout ? readInt('[name="serial_position_x"]', 40) : readInt('[name="sn_position_x"]', 170);
        const snY = useRatingTextLayout ? readInt('[name="serial_position_y"]', 40) : readInt('[name="sn_position_y"]', 95);
        const snFontSize = useRatingTextLayout ? readInt('[name="serial_font_size"]', 40) : readInt('[name="sn_font_size"]', 22);
        const snOrientation = useRatingTextLayout
            ? normalizeOrientation(document.querySelector('[name="serial_orientation"]')?.value, 'N')
            : normalizeOrientation(document.querySelector('[name="sn_orientation"]')?.value, 'N');
        const snPrefix = (document.querySelector('[name="sn_prefix"]')?.value ?? 'SN:').trim();
        const snLine = labelType === 'rating'
            ? serialPrint
            : (snPrefix ? `${snPrefix} ${serialPrint}` : serialPrint);
        const qrPayload = resolveQrPayload(labelType, serial);
        const serialBlockCount = Math.min(Math.max(readInt('[name="serial_block_count"]', 1), 1), 4);
        const serialBlockOffsetY = Math.max(readInt('[name="serial_block_offset_y"]', 180), 0);

        const zpl = [
            '^XA',
            '^CI28',
        ];

        const skuX = readInt('[name="sku_position_x"]', 170);
        const skuY = readInt('[name="sku_position_y"]', 35);
        const skuFontSize = readInt('[name="sku_font_size"]', 44);
        const skuOrientation = normalizeOrientation(document.querySelector('[name="sku_orientation"]')?.value, 'N');

        for (let blockIndex = 0; blockIndex < serialBlockCount; blockIndex += 1) {
            const yOffset = blockIndex * serialBlockOffsetY;

            zpl.push(`^FO${qrX},${qrY + yOffset}`);
            zpl.push(`^BQ${qrOrientation},2,${qrMagnification}`);
            zpl.push(`^FDLA,${qrPayload}^FS`);

            if (!hideSkuOnEmeaRating) {
                zpl.push(`^FO${skuX},${skuY + yOffset}`);
                zpl.push(`^A0${skuOrientation},${skuFontSize},${skuFontSize}`);
                zpl.push(`^FD${getSelectedSkuCode()}^FS`);
            }

            zpl.push(`^FO${snX},${snY + yOffset}`);
            zpl.push(`^A0${snOrientation},${snFontSize},${snFontSize}`);
            zpl.push(`^FD${snLine}^FS`);
        }

        zpl.push('^XZ');

        return zpl.join('\n');
    };

    const runTestPrint = () => {
        if (connectionSelect?.value === 'network') {
            setStatus('Prueba de impresión para conexión de red lista (requiere flujo BrowserPrint de red en estación cliente).');

            return;
        }

        if (!selectedDevice) {
            setStatus('Primero ejecuta "Probar conexión USB".', true);

            return;
        }

        selectedDevice.send(buildTestZpl(), () => {
            const isSerial = labelTypeSelect?.value === 'serial';
            const isRatingWithQr = isRatingWithQrEnabled();

            setStatus((isSerial || isRatingWithQr)
                ? (isEmeaOrAnzRatingWithQr()
                    ? 'Impresión de prueba enviada por USB con QR + SN EMEA/ANZ (sin SKU).'
                    : 'Impresión de prueba enviada por USB con QR, SKU y SN de referencia.')
                : 'Impresión de prueba enviada por USB con SN de referencia.');
        }, (error) => {
            setStatus(`Falló impresión de prueba: ${error}`, true);
        });
    };

    const showZplPreview = () => {
        const zpl = buildTestZpl();

        if (zplPreviewOutput) {
            zplPreviewOutput.value = zpl;
        }

        if (zplPreviewWrapper) {
            zplPreviewWrapper.classList.remove('hidden');
        }

        setStatus('Preview ZPL generado. Verifica que aparezcan dos bloques si configuraste count=2.');
    };

    connectionSelect?.addEventListener('change', toggleConnectionFields);

    labelTypeSelect?.addEventListener('change', toggleLayoutSections);
    labelTypeSelect?.addEventListener('change', updateLivePreview);

    ratingWithQrCheckbox?.addEventListener('change', toggleLayoutSections);
    ratingWithQrCheckbox?.addEventListener('change', updateLivePreview);

    ratingHideSkuCheckbox?.addEventListener('change', toggleLayoutSections);
    ratingHideSkuCheckbox?.addEventListener('change', updateLivePreview);

    qrContentModeSelect?.addEventListener('change', toggleLayoutSections);
    qrContentModeSelect?.addEventListener('change', updateLivePreview);

    qrSerialStyleSelect?.addEventListener('change', toggleLayoutSections);
    qrSerialStyleSelect?.addEventListener('change', updateLivePreview);

    skuStandardFilterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setSkuStandardFilter(button.dataset.skuStandardFilter || 'UL');
            toggleLayoutSections();
            updateLivePreview();
        });
    });

    skuSelect?.addEventListener('change', () => {
        syncSerialStandardFromSku();
        toggleLayoutSections();
        updateLivePreview();
    });

    qrSeparatorSelect?.addEventListener('change', updateLivePreview);

    [1, 2, 3].forEach((index) => {
        document.querySelector(`[name="qr_custom_field_${index}"]`)?.addEventListener('change', updateLivePreview);
    });

    document.querySelector('[name="sn_prefix"]')?.addEventListener('input', updateLivePreview);

    [
        'serial_position_x',
        'serial_position_y',
        'serial_font_size',
        'serial_orientation',
        'qr_position_x',
        'qr_position_y',
        'qr_magnification',
        'qr_orientation',
        'sku_position_x',
        'sku_position_y',
        'sku_font_size',
        'sku_orientation',
        'sn_position_x',
        'sn_position_y',
        'sn_font_size',
        'sn_orientation',
        'serial_block_count',
        'serial_block_offset_y',
        'template_dpi',
        'template_width_mm',
        'template_height_mm',
    ].forEach((fieldName) => {
        const field = document.querySelector(`[name="${fieldName}"]`);

        field?.addEventListener('input', updateLivePreview);
        field?.addEventListener('change', updateLivePreview);
    });

    usbPrinterSelect?.addEventListener('change', () => {
        const selectedName = String(usbPrinterSelect.value || '').trim();

        if (!selectedName || !printerNameInput) {
            return;
        }

        printerNameInput.value = selectedName;
        setStatus(`Impresora seleccionada: ${selectedName}`);
    });

    testUsbButton?.addEventListener('click', connectUsb);
    testPrintButton?.addEventListener('click', runTestPrint);
    previewZplButton?.addEventListener('click', showZplPreview);

    setSkuStandardFilter(serialStandardInput?.value || getSelectedSkuStandard());
    toggleConnectionFields();
    toggleLayoutSections();
    updateLivePreview();
};

initSkuTemplateConfigurationsForm();