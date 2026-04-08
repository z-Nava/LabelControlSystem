const ORIENTATIONS = ['N', 'R', 'I', 'B'];

const normalizeOrientation = (value, fallback = 'N') => {
    const normalized = String(value || fallback).trim().toUpperCase();

    return ORIENTATIONS.includes(normalized) ? normalized : fallback;
};

const readInt = (selector, fallback) => Number.parseInt(document.querySelector(selector)?.value || String(fallback), 10) || fallback;

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
    const printerNameInput = document.getElementById('default_printer_name');
    const skuSelect = document.querySelector('[name="label_sku_id"]');
    const serialStandardSelect = document.querySelector('[name="serial_standard"]');
    const ratingWithQrCheckbox = document.querySelector('[name="rating_with_qr"]');
    const serialSections = document.querySelectorAll('[data-layout-section="serial"]');
    const ratingSections = document.querySelectorAll('[data-layout-section="rating"]');
    const qrLayoutTitle = document.getElementById('qr-layout-title');
    const qrLayoutDescription = document.getElementById('qr-layout-description');

    const defaultSerial = form.dataset.defaultSerial || 'L36BH2606007A7';
    const defaultSku = form.dataset.defaultSku || '2978-OCUT';

    let selectedDevice = null;

    const setStatus = (message, isError = false) => {
        if (!statusBox) {
            return;
        }

        statusBox.textContent = message;
        statusBox.classList.toggle('text-red-700', isError);
        statusBox.classList.toggle('text-slate-700', !isError);
    };

    const getSelectedSkuCode = () => skuSelect?.selectedOptions?.[0]?.dataset?.skuCode || defaultSku;
    const isRatingWithQrEnabled = () => labelTypeSelect?.value === 'rating' && Boolean(ratingWithQrCheckbox?.checked);
    
    const toggleConnectionFields = () => {
        const isNetwork = connectionSelect?.value === 'network';

        if (ipWrapper) {
            ipWrapper.style.display = isNetwork ? 'block' : 'none';
        }

        ipInput?.toggleAttribute('required', isNetwork);

        if (usbConnectedInput) {
            usbConnectedInput.value = isNetwork ? '1' : '0';
        }
    };

    const toggleLayoutSections = () => {
        const isSerial = labelTypeSelect?.value === 'serial';
        const isRatingWithQr = labelTypeSelect?.value === 'rating' && Boolean(ratingWithQrCheckbox?.checked);
        const requiresQr = isSerial || isRatingWithQr;

        serialSections.forEach((section) => {
            section.style.display = requiresQr ? 'block' : 'none';

            section.querySelectorAll('input, select').forEach((field) => {
                field.required = requiresQr;
            });
        });

        ratingSections.forEach((section) => {
            section.querySelectorAll('input, select').forEach((field) => {
                if (field.name.startsWith('serial_')) {
                    field.required = true;
                }
            });
        });

        if (qrLayoutTitle) {
            qrLayoutTitle.textContent = isRatingWithQr
                ? 'Configuración etiqueta Rating con QR'
                : 'Configuración etiqueta Serial con QR';
        }

        if (qrLayoutDescription) {
            qrLayoutDescription.textContent = isRatingWithQr
                ? 'El QR codifica el serial completo para la etiqueta Rating; además se muestra el SKU grande y el SN en texto pequeño.'
                : 'El QR codifica el serial completo; además se muestra el SKU grande y el SN en texto pequeño.';
        }

        setStatus(isSerial
            ? 'Configurando etiqueta Serial con QR + SKU + SN pequeño.'
            : (isRatingWithQr
                ? 'Configurando etiqueta Rating con QR (EMEA).'
                : 'Configurando etiqueta simple sin QR; la prueba mostrará solo el SN.'));
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

        setStatus('Buscando impresora USB...');

        window.BrowserPrint.getDefaultDevice('printer', (device) => {
            if (device && String(device.connection || '').toLowerCase().includes('usb')) {
                selectedDevice = device;
                usbConnectedInput.value = '1';
                printerNameInput.value = device.name || printerNameInput.value;
                setStatus(`Conexión USB OK: ${device.name}`);

                return;
            }

            window.BrowserPrint.getLocalDevices((devices) => {
                const usbPrinter = (devices || []).find((candidate) => {
                    return candidate.deviceType === 'printer'
                        && String(candidate.connection || '').toLowerCase().includes('usb');
                });

                if (!usbPrinter) {
                    usbConnectedInput.value = '0';
                    setStatus('No se detectó impresora USB conectada.', true);

                    return;
                }

                selectedDevice = usbPrinter;
                usbConnectedInput.value = '1';
                printerNameInput.value = usbPrinter.name || printerNameInput.value;
                setStatus(`Conexión USB OK: ${usbPrinter.name}`);
            }, (error) => {
                usbConnectedInput.value = '0';
                setStatus(`Error al detectar impresora USB: ${error}`, true);
            }, 'printer');
        }, () => {
            window.BrowserPrint.getLocalDevices(() => {}, () => {
                usbConnectedInput.value = '0';
                setStatus('No fue posible obtener la impresora default.', true);
            }, 'printer');
        });
    };

    const buildTestZpl = () => {
        const labelType = labelTypeSelect?.value;
        const isRatingWithQr = isRatingWithQrEnabled();
        const serial = defaultSerial;

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
                `^FD${serial}^FS`,
                '^XZ',
            ].join('\n');
        }

        const qrX = readInt('[name="qr_position_x"]', 30);
        const qrY = readInt('[name="qr_position_y"]', 30);
        const qrOrientation = normalizeOrientation(document.querySelector('[name="qr_orientation"]')?.value, 'N');
        const qrMagnification = Math.min(Math.max(readInt('[name="qr_magnification"]', 4), 1), 10);
        const skuX = readInt('[name="sku_position_x"]', 170);
        const skuY = readInt('[name="sku_position_y"]', 35);
        const skuFontSize = readInt('[name="sku_font_size"]', 44);
        const skuOrientation = normalizeOrientation(document.querySelector('[name="sku_orientation"]')?.value, 'N');
        const snX = readInt('[name="sn_position_x"]', 170);
        const snY = readInt('[name="sn_position_y"]', 95);
        const snFontSize = readInt('[name="sn_font_size"]', 22);
        const snOrientation = normalizeOrientation(document.querySelector('[name="sn_orientation"]')?.value, 'N');
        const snPrefix = (document.querySelector('[name="sn_prefix"]')?.value || 'SN:').trim();
        const snLine = snPrefix ? `${snPrefix} ${serial}` : serial;

        return [
            '^XA',
            '^CI28',
            `^FO${qrX},${qrY}`,
            `^BQ${qrOrientation},2,${qrMagnification}`,
            `^FDLA,${serial}^FS`,
            `^FO${skuX},${skuY}`,
            `^A0${skuOrientation},${skuFontSize},${skuFontSize}`,
            `^FD${getSelectedSkuCode()}^FS`,
            `^FO${snX},${snY}`,
            `^A0${snOrientation},${snFontSize},${snFontSize}`,
            `^FD${snLine}^FS`,
            '^XZ',
        ].join('\n');
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
                ? 'Impresión de prueba enviada por USB con QR, SKU y SN de referencia.'
                : 'Impresión de prueba enviada por USB con SN de referencia.');
        }, (error) => {
            setStatus(`Falló impresión de prueba: ${error}`, true);
        });
    };

    connectionSelect?.addEventListener('change', toggleConnectionFields);
    labelTypeSelect?.addEventListener('change', toggleLayoutSections);
    ratingWithQrCheckbox?.addEventListener('change', toggleLayoutSections);
    skuSelect?.addEventListener('change', () => {
        const skuStandard = skuSelect.selectedOptions?.[0]?.dataset?.serialStandard;
        if (serialStandardSelect && skuStandard) {
            serialStandardSelect.value = skuStandard;
        }
    });
    testUsbButton?.addEventListener('click', connectUsb);
    testPrintButton?.addEventListener('click', runTestPrint);

    toggleConnectionFields();
    toggleLayoutSections();
};

initSkuTemplateConfigurationsForm();
