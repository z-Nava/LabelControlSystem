@csrf
@php
    $layout = old('serial_layout', $configuration->template->meta['serial_layout'] ?? []);
    $textLayout = $layout['text'] ?? $layout;
    $qrLayout = $layout['qr'] ?? [];
    $skuLayout = $layout['sku'] ?? [];
    $snLayout = $layout['sn'] ?? [];
    $settings = old('profile_settings', $configuration->settings ?? []);
    $connectionType = old('connection_type', $settings['connection_type'] ?? ($configuration->default_printer_ip ? 'network' : 'usb'));
    $selectedLabelType = old('label_type', $configuration->label_type ?? $configuration->template?->label_type ?? 'serial');
@endphp
<div class="grid grid-cols-1 gap-4 md:grid-cols-2" id="sku-template-configuration-form">
    <div>
        <label class="block text-sm font-medium text-slate-700">SKU</label>
        <select name="label_sku_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            @foreach($labelSkus as $sku)
                <option value="{{ $sku->id }}" data-sku-code="{{ $sku->sku }}" @selected((string) old('label_sku_id', $configuration->label_sku_id ?? '') === (string) $sku->id)>
                    {{ $sku->sku }} · {{ $sku->label_part_number }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Solo se listan SKU con serial format activo.</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Tipo de etiqueta</label>
        <select name="label_type" id="label_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            @foreach(['serial', 'rating', 'shipping'] as $type)
                <option value="{{ $type }}" @selected($selectedLabelType === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-1 border-t pt-3 md:col-span-2">
        <h2 class="font-semibold text-slate-900">Template (ZPL generado automáticamente)</h2>
        <p class="mt-1 text-xs text-slate-500">Rating mantiene el texto simple del SN. Serial agrega QR + SKU + SN pequeño como en la referencia.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Nombre template</label>
        <input name="template_name" value="{{ old('template_name', $configuration->template->name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">DPI template</label>
        <select name="template_dpi" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            @foreach([203, 300] as $dpi)
                <option value="{{ $dpi }}" @selected((int) old('template_dpi', $configuration->template->dpi ?? 203) === $dpi)>{{ $dpi }}</option>
            @endforeach
        </select>
    </div>

    <div class="rounded-2xl border border-slate-200 p-4 md:col-span-2" data-layout-section="rating">
        <div class="mb-3">
            <h3 class="font-semibold text-slate-900">Configuración texto SN / Rating</h3>
            <p class="mt-1 text-xs text-slate-500">Este bloque se usa para Rating y como respaldo para layouts simples.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Posición serial X</label>
                <input type="number" name="serial_position_x" value="{{ old('serial_position_x', $textLayout['x'] ?? 40) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                @error('serial_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Posición serial Y</label>
                <input type="number" name="serial_position_y" value="{{ old('serial_position_y', $textLayout['y'] ?? 40) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                @error('serial_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tamaño de letra</label>
                <input type="number" name="serial_font_size" value="{{ old('serial_font_size', $textLayout['font_size'] ?? 40) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                @error('serial_font_size') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Orientación serial</label>
                <select name="serial_orientation" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('serial_orientation', $textLayout['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('serial_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-2" data-layout-section="serial">
        <div class="mb-3">
            <h3 class="font-semibold text-slate-900">Configuración etiqueta Serial con QR</h3>
            <p class="mt-1 text-xs text-slate-500">El QR codifica el serial completo; además se muestra el SKU grande y el SN en texto pequeño.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-slate-700">QR X</label>
                <input type="number" name="qr_position_x" value="{{ old('qr_position_x', $qrLayout['x'] ?? 30) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('qr_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">QR Y</label>
                <input type="number" name="qr_position_y" value="{{ old('qr_position_y', $qrLayout['y'] ?? 30) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('qr_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Magnificación QR</label>
                <input type="number" name="qr_magnification" min="1" max="10" value="{{ old('qr_magnification', $qrLayout['magnification'] ?? 4) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('qr_magnification') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">SKU X</label>
                <input type="number" name="sku_position_x" value="{{ old('sku_position_x', $skuLayout['x'] ?? 170) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('sku_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">SKU Y</label>
                <input type="number" name="sku_position_y" value="{{ old('sku_position_y', $skuLayout['y'] ?? 35) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('sku_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tamaño letra SKU</label>
                <input type="number" name="sku_font_size" value="{{ old('sku_font_size', $skuLayout['font_size'] ?? 44) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('sku_font_size') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Orientación SKU</label>
                <select name="sku_orientation" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('sku_orientation', $skuLayout['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('sku_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">SN pequeño X</label>
                <input type="number" name="sn_position_x" value="{{ old('sn_position_x', $snLayout['x'] ?? 170) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('sn_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">SN pequeño Y</label>
                <input type="number" name="sn_position_y" value="{{ old('sn_position_y', $snLayout['y'] ?? 95) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('sn_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tamaño letra SN pequeño</label>
                <input type="number" name="sn_font_size" value="{{ old('sn_font_size', $snLayout['font_size'] ?? 22) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('sn_font_size') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Orientación SN pequeño</label>
                <select name="sn_orientation" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('sn_orientation', $snLayout['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('sn_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Prefijo texto SN</label>
                <input name="sn_prefix" value="{{ old('sn_prefix', $snLayout['prefix'] ?? 'SN:') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('sn_prefix') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Ancho mm</label>
        <input name="template_width_mm" value="{{ old('template_width_mm', $configuration->template->width_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Alto mm</label>
        <input name="template_height_mm" value="{{ old('template_height_mm', $configuration->template->height_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    </div>

    <div class="mt-1 border-t pt-3 md:col-span-2">
        <h2 class="font-semibold text-slate-900">Print Profile</h2>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Nombre profile</label>
        <input name="profile_name" value="{{ old('profile_name', $configuration->name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">DPI profile</label>
        <select name="profile_dpi" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            @foreach([203, 300] as $dpi)
                <option value="{{ $dpi }}" @selected((int) old('profile_dpi', $configuration->dpi ?? 203) === $dpi)>{{ $dpi }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Printer name</label>
        <input name="default_printer_name" id="default_printer_name" value="{{ old('default_printer_name', $configuration->default_printer_name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Tipo de conexión</label>
        <select name="connection_type" id="connection_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            <option value="usb" @selected($connectionType === 'usb')>USB</option>
            <option value="network" @selected($connectionType === 'network')>Red (IP)</option>
        </select>
    </div>

    <div id="printer-ip-wrapper">
        <label class="block text-sm font-medium text-slate-700">Printer IP</label>
        <input name="default_printer_ip" id="default_printer_ip" value="{{ old('default_printer_ip', $configuration->default_printer_ip ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
        <h3 class="font-semibold text-slate-900">Pruebas de impresora</h3>
        <p class="mt-1 text-xs text-slate-600">Para USB, valida conexión antes de guardar y ejecuta impresión de prueba. La prueba cambia según el tipo: Serial imprime QR + SKU + SN; Rating imprime solo SN.</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <button id="test-usb-connection" type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">Probar conexión USB</button>
            <button id="test-print" type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-sm text-white">Impresión de prueba</button>
        </div>
        <input type="hidden" name="usb_connected" id="usb_connected" value="{{ old('usb_connected', '0') }}" />
        <div id="printer-test-status" class="mt-2 text-sm text-slate-700">Sin prueba de conexión.</div>
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="template_is_active" value="1" {{ old('template_is_active', $configuration->template->is_active ?? true) ? 'checked' : '' }}> Template activo
        </label>
        <label class="ml-6 inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="profile_is_active" value="1" {{ old('profile_is_active', $configuration->is_active ?? true) ? 'checked' : '' }}> Profile activo
        </label>
    </div>
</div>

<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
<script>
(() => {
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
    const serialSections = document.querySelectorAll('[data-layout-section="serial"]');
    const ratingSections = document.querySelectorAll('[data-layout-section="rating"]');

    let selectedDevice = null;

    const getSelectedSkuCode = () => skuSelect?.selectedOptions?.[0]?.dataset?.skuCode || '2978-OCUT';
    const getSerialValue = () => 'L36BH2606007A7';

    const normalizeOrientation = (value, fallback = 'N') => {
        const normalized = String(value || fallback).trim().toUpperCase();
        return ['N', 'R', 'I', 'B'].includes(normalized) ? normalized : fallback;
    };

    const readInt = (selector, fallback) => Number.parseInt(document.querySelector(selector)?.value || String(fallback), 10) || fallback;

    const setStatus = (message, isError = false) => {
        statusBox.textContent = message;
        statusBox.classList.toggle('text-red-700', isError);
        statusBox.classList.toggle('text-slate-700', !isError);
    };

    const toggleConnectionFields = () => {
        const isNetwork = connectionSelect.value === 'network';
        ipWrapper.style.display = isNetwork ? 'block' : 'none';
        ipInput.toggleAttribute('required', isNetwork);
        usbConnectedInput.value = isNetwork ? '1' : '0';
    };

    const toggleLayoutSections = () => {
        const isSerial = labelTypeSelect.value === 'serial';
        serialSections.forEach((section) => {
            section.style.display = isSerial ? 'block' : 'none';
        });
        ratingSections.forEach((section) => {
            section.querySelectorAll('input, select').forEach((field) => {
                if (field.name.startsWith('serial_')) {
                    field.required = true;
                }
            });
        });
        setStatus(isSerial
            ? 'Configurando etiqueta Serial con QR + SKU + SN pequeño.'
            : 'Configurando etiqueta simple sin QR; la prueba mostrará solo el SN.');
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
        BrowserPrint.getDefaultDevice('printer', (device) => {
            if (device && String(device.connection || '').toLowerCase().includes('usb')) {
                selectedDevice = device;
                usbConnectedInput.value = '1';
                printerNameInput.value = device.name || printerNameInput.value;
                setStatus(`Conexión USB OK: ${device.name}`);
                return;
            }

            BrowserPrint.getLocalDevices((devices) => {
                const usbPrinter = (devices || []).find((candidate) => {
                    return candidate.deviceType === 'printer' && String(candidate.connection || '').toLowerCase().includes('usb');
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
            BrowserPrint.getLocalDevices(() => {}, () => {
                usbConnectedInput.value = '0';
                setStatus('No fue posible obtener la impresora default.', true);
            }, 'printer');
        });
    };

    const buildTestSerialZpl = () => {
        const labelType = labelTypeSelect.value;
        const serial = getSerialValue();
        const sku = getSelectedSkuCode();

        if (labelType !== 'serial') {
            const x = readInt('[name="serial_position_x"]', 40);
            const y = readInt('[name="serial_position_y"]', 40);
            const fontSize = readInt('[name="serial_font_size"]', 40);
            const orientation = normalizeOrientation(document.querySelector('[name="serial_orientation"]')?.value, 'N');

            return [
                '^XA',
                '^CI28',
                `^FO${x},${y}`,
                `^A${orientation}N,${fontSize},${fontSize}`,
                `^FD${serial}^FS`,
                '^XZ',
            ].join('\n');
        }

        const qrX = readInt('[name="qr_position_x"]', 30);
        const qrY = readInt('[name="qr_position_y"]', 30);
        const qrMagnification = readInt('[name="qr_magnification"]', 4);
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
            `^BQN,2,${Math.min(Math.max(qrMagnification, 1), 10)}`,
            `^FDLA,${serial}^FS`,
            `^FO${skuX},${skuY}`,
            `^A${skuOrientation}N,${skuFontSize},${skuFontSize}`,
            `^FD${sku}^FS`,
            `^FO${snX},${snY}`,
            `^A${snOrientation}N,${snFontSize},${snFontSize}`,
            `^FD${snLine}^FS`,
            '^XZ',
        ].join('\n');
    };

    const runTestPrint = () => {
        const type = connectionSelect.value;

        if (type === 'network') {
            setStatus('Prueba de impresión para conexión de red lista (requiere flujo BrowserPrint de red en estación cliente).');
            return;
        }

        if (!selectedDevice) {
            setStatus('Primero ejecuta "Probar conexión USB".', true);
            return;
        }

        const zpl = buildTestSerialZpl();
        selectedDevice.send(zpl, () => {
            const labelType = labelTypeSelect.value;
            setStatus(labelType === 'serial'
                ? 'Impresión de prueba enviada por USB con QR, SKU y SN de referencia.'
                : 'Impresión de prueba enviada por USB con SN de referencia.');
        }, (error) => {
            setStatus(`Falló impresión de prueba: ${error}`, true);
        });
    };

    connectionSelect?.addEventListener('change', toggleConnectionFields);
    labelTypeSelect?.addEventListener('change', toggleLayoutSections);
    testUsbButton?.addEventListener('click', connectUsb);
    testPrintButton?.addEventListener('click', runTestPrint);

    toggleConnectionFields();
    toggleLayoutSections();
})();
</script>
