@csrf
@if ($errors->any())
    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <p class="font-semibold">No se pudo guardar el template.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div id="dummy-template-form" class="space-y-5">
    <section class="rounded-2xl border border-slate-200 p-4">
        <h2 class="text-base font-semibold text-slate-900">Datos generales</h2>
        <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Nombre template</label>
                <input name="name" value="{{ old('name', $template->name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tipo dummy</label>
                <select name="dummy_type" id="dummy_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    <option value="rmt" @selected(old('dummy_type', $template->dummy_type ?? 'rmt') === 'rmt')>RMT Dummy QR</option>
                    <option value="rw" @selected(old('dummy_type', $template->dummy_type ?? '') === 'rw')>RW Dummy QR</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">DPI</label>
                <select name="dpi" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    @foreach([203,300] as $dpi)
                        <option value="{{ $dpi }}" @selected((int) old('dpi', $template->dpi ?? 203) === $dpi)>{{ $dpi }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Orientación QR</label>
                <select name="qr_orientation" id="qr_orientation" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    @foreach(['N','R','I','B'] as $orientation)
                        <option value="{{ $orientation }}" @selected(old('qr_orientation', $template->qr_orientation ?? 'N') === $orientation)>{{ $orientation }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Ancho (mm)</label>
                <input type="number" step="0.01" name="width_mm" value="{{ old('width_mm', $template->width_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Alto (mm)</label>
                <input type="number" step="0.01" name="height_mm" value="{{ old('height_mm', $template->height_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
            </div>
        </div>
        <div class="mt-4">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}> Template activo
            </label>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 p-4">
        <h2 class="text-base font-semibold text-slate-900">Configuración de impresora</h2>
        <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">Conexión</label>
                <select name="connection_type" id="connection_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    <option value="usb" @selected(old('connection_type', $template->connection_type ?? 'usb') === 'usb')>USB</option>
                    <option value="network" @selected(old('connection_type', $template->connection_type ?? '') === 'network')>Red (IP)</option>
                </select>
            </div>
            <div>
                <div class="flex items-end justify-between gap-2">
                    <label class="block text-sm font-medium text-slate-700">Impresoras USB detectadas</label>
                    <button id="refresh-printers" type="button" class="rounded-lg border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100">Actualizar</button>
                </div>
                <select id="usb_printer_select" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <option value="">Detecta impresoras para seleccionar...</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">Si hay varias impresoras conectadas, elige aquí cuál usar.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Printer name (opcional)</label>
                <input name="default_printer_name" id="default_printer_name" value="{{ old('default_printer_name', $template->default_printer_name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Printer IP (si red)</label>
                <input name="default_printer_ip" id="default_printer_ip" value="{{ old('default_printer_ip', $template->default_printer_ip ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 p-4">
        <h2 class="text-base font-semibold text-slate-900">Layout (posiciones)</h2>
        <p class="mt-1 text-xs text-slate-600">Sección por responsabilidad para facilitar el ajuste del template.</p>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 p-3">
                <h3 class="text-sm font-semibold text-slate-900">Posiciones QR</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach([['qr_x','QR X',30],['qr_y','QR Y',65],['qr_magnification','QR Magnificación',4]] as [$field,$label,$default])
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input type="number" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, data_get($template, $field, $default)) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-3">
                <h3 class="text-sm font-semibold text-slate-900">Posiciones FG</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach([['fg_x','FG X',360],['fg_y','FG Y',70],['fg_font_size','FG Font',40]] as [$field,$label,$default])
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input type="number" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, data_get($template, $field, $default)) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-3">
                <h3 class="text-sm font-semibold text-slate-900">Posiciones JOB</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach([['job_x','JOB X',360],['job_y','JOB Y',130],['job_font_size','JOB Font',34]] as [$field,$label,$default])
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input type="number" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, data_get($template, $field, $default)) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-3">
                <h3 class="text-sm font-semibold text-slate-900">Posiciones Consecutivo</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach([['consecutive_x','Consecutivo X',380],['consecutive_y','Consecutivo Y',250],['consecutive_font_size','Consecutivo Font',58]] as [$field,$label,$default])
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input type="number" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, data_get($template, $field, $default)) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-3 lg:col-span-2">
                <h3 class="text-sm font-semibold text-slate-900">Posiciones Título</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach([['title_x','Título X',20],['title_y','Título Y',20],['title_font_size','Título Font',44]] as [$field,$label,$default])
                        <div>
                            <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input type="number" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, data_get($template, $field, $default)) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <h3 class="font-semibold text-slate-900">Pruebas Zebra Browser Print</h3>
        <p class="mt-1 text-xs text-slate-600">Valida conexión real y genera ZPL de prueba con datos dummy para revisar posiciones.</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <button id="test-printer-connection" type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">Validar conexión actual</button>
            <button id="preview-zpl" type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">Ver ZPL generado</button>
            <button id="test-print" type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-sm text-white">Impresión de prueba</button>
        </div>
        <pre id="zpl-preview" class="mt-3 hidden max-h-56 overflow-auto rounded bg-slate-900 p-3 text-xs text-emerald-200"></pre>
        <div id="printer-test-status" class="mt-2 text-sm text-slate-700">Sin pruebas ejecutadas.</div>
    </div>
</div>

<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
<script>
(() => {
    const statusEl = document.getElementById('printer-test-status');
    const previewEl = document.getElementById('zpl-preview');
    const defaultPrinterNameInput = document.getElementById('default_printer_name');
    const defaultPrinterIpInput = document.getElementById('default_printer_ip');
    const connectionTypeInput = document.getElementById('connection_type');
    const printerSelectInput = document.getElementById('usb_printer_select');
    const refreshPrintersButton = document.getElementById('refresh-printers');
    let availableUsbPrinters = [];

    const getValue = (id, fallback = '') => document.getElementById(id)?.value ?? fallback;
    const setStatus = (message, isError = false) => {
        statusEl.textContent = message;
        statusEl.classList.toggle('text-red-700', isError);
        statusEl.classList.toggle('text-slate-700', !isError);
    };

    const buildZpl = () => {
        const dummyType = getValue('dummy_type', 'rmt');
        const title = dummyType === 'rw' ? 'RW Dummy QR' : 'RMT Dummy QR';

        const qrPayload = '^DM^479124001^QB479124001UN-A01-OP21PSE^0000000014^';
        const qrPayloadHex = String(qrPayload)
            .replaceAll('\\', '\\5C')
            .replaceAll('^', '\\5E')
            .replaceAll('~', '\\7E');

        return [
            '^XA',
            '^CI28',
            '^PW820',
            '^LL400',
            '^LH0,0',
            `^FO${getValue('title_x',20)},${getValue('title_y',20)}^A0N,${getValue('title_font_size',44)},${getValue('title_font_size',44)}^FD${title}^FS`,
            `^FO${getValue('qr_x',30)},${getValue('qr_y',65)}^BQ${getValue('qr_orientation','N')},2,${getValue('qr_magnification',4)}`,
            `^FH\\^FDLA,${qrPayloadHex}^FS`,
            `^FO${getValue('fg_x',360)},${getValue('fg_y',70)}^A0N,${getValue('fg_font_size',40)},${getValue('fg_font_size',40)}^FD479124001^FS`,
            `^FO${getValue('job_x',360)},${getValue('job_y',130)}^A0N,${getValue('job_font_size',34)},${getValue('job_font_size',34)}^FD999999^FS`,
            `^FO${getValue('consecutive_x',380)},${getValue('consecutive_y',250)}^A0N,${getValue('consecutive_font_size',58)},${getValue('consecutive_font_size',58)}^FD0000000014^FS`,
            '^XZ'
        ].join('\n');
    };

    const ensureBrowserPrint = () => {
        if (!window.BrowserPrint) {
            setStatus('BrowserPrint no está disponible en este navegador/equipo.', true);
            return false;
        }

        return true;
    };

    const getUsbPrinterId = (printer) => {
        return [printer?.name || '', printer?.uid || '', printer?.connection || 'usb'].join('::');
    };

    const renderUsbPrinterOptions = (selectedId = '') => {
        if (!printerSelectInput) {
            return;
        }

        printerSelectInput.innerHTML = '';

        if (!availableUsbPrinters.length) {
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'No se detectaron impresoras USB';
            printerSelectInput.appendChild(placeholder);
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

        if (selectedId) {
            printerSelectInput.value = selectedId;
        }
    };

    const pickSelectedPrinter = () => {
        if (!availableUsbPrinters.length) {
            return null;
        }

        const selectedId = printerSelectInput?.value || '';
        if (selectedId) {
            const selected = availableUsbPrinters.find((printer) => getUsbPrinterId(printer) === selectedId);
            if (selected) {
                return selected;
            }
        }

        const currentName = (defaultPrinterNameInput?.value || '').trim();
        if (currentName) {
            const sameName = availableUsbPrinters.find((printer) => (printer.name || '').trim() === currentName);
            if (sameName) {
                if (printerSelectInput) {
                    printerSelectInput.value = getUsbPrinterId(sameName);
                }
                return sameName;
            }
        }

        return availableUsbPrinters[0];
    };

    const listUsbPrinters = ({ silent = false, afterLoad = null } = {}) => {
        window.BrowserPrint.getDefaultDevice('printer',
            function (defaultPrinter) {
                window.BrowserPrint.getLocalDevices((devices) => {
                    const usbPrinters = (devices || []).filter((candidate) => {
                        return candidate.deviceType === 'printer'
                            && String(candidate.connection || '').toLowerCase().includes('usb');
                    });

                    if (
                        defaultPrinter
                        && defaultPrinter.deviceType === 'printer'
                        && String(defaultPrinter.connection || '').toLowerCase().includes('usb')
                    ) {
                        const exists = usbPrinters.some((printer) => getUsbPrinterId(printer) === getUsbPrinterId(defaultPrinter));
                        if (!exists) {
                            usbPrinters.unshift(defaultPrinter);
                        }
                    }

                    availableUsbPrinters = usbPrinters;
                    const selected = pickSelectedPrinter();
                    renderUsbPrinterOptions(selected ? getUsbPrinterId(selected) : '');

                    if (selected && defaultPrinterNameInput && !defaultPrinterNameInput.value.trim()) {
                        defaultPrinterNameInput.value = selected.name || '';
                    }

                    if (!silent) {
                        if (!availableUsbPrinters.length) {
                            setStatus('No se detectaron impresoras USB disponibles.', true);
                        } else {
                            setStatus(`Se detectaron ${availableUsbPrinters.length} impresora(s) USB.`);
                        }
                    }

                    afterLoad?.();
                }, () => {
                    availableUsbPrinters = [];
                    renderUsbPrinterOptions('');
                    if (!silent) {
                        setStatus('No fue posible listar impresoras USB.', true);
                    }
                }, 'printer');
            },
            () => {
                window.BrowserPrint.getLocalDevices((devices) => {
                    availableUsbPrinters = (devices || []).filter((candidate) => {
                        return candidate.deviceType === 'printer'
                            && String(candidate.connection || '').toLowerCase().includes('usb');
                    });
                    const selected = pickSelectedPrinter();
                    renderUsbPrinterOptions(selected ? getUsbPrinterId(selected) : '');
                    afterLoad?.();
                }, () => {
                    availableUsbPrinters = [];
                    renderUsbPrinterOptions('');
                    if (!silent) {
                        setStatus('No fue posible listar impresoras USB.', true);
                    }
                }, 'printer');
            }
        );
    };

    const validateUsbPrinter = (printer, onSuccess, onError) => {
        if (!printer) {
            onError('Selecciona una impresora USB para validar conexión.');
            return;
        }

        printer.read(
            () => onSuccess(printer),
            () => onError(`No se pudo validar conexión con ${printer.name || 'impresora USB seleccionada'}.`)
        );
    };

    const resolveUsbPrinter = (onSuccess, onError) => {
        if (!availableUsbPrinters.length) {
            listUsbPrinters({
                silent: true,
                afterLoad: () => {
                    const selected = pickSelectedPrinter();
                    if (!selected) {
                        onError('No se detectó impresora Zebra USB conectada.');
                        return;
                    }

                    if (defaultPrinterNameInput) {
                        defaultPrinterNameInput.value = selected.name || '';
                    }

                    onSuccess(selected);
                },
            });
            return;
        }

        const selected = pickSelectedPrinter();
        if (!selected) {
            onError('No se detectó impresora Zebra USB conectada.');
            return;
        }

        if (defaultPrinterNameInput) {
            defaultPrinterNameInput.value = selected.name || '';
        }

        onSuccess(selected);
    };

    const validateNetworkPrinter = (onSuccess, onError) => {
        const ip = (defaultPrinterIpInput?.value || '').trim();
        if (!ip) {
            onError('Captura IP de impresora para validar conexión por red.');
            return;
        }

        const printer = new window.BrowserPrint.Device(ip, undefined, 'network');
        printer.read(
            () => onSuccess(printer, ip),
            () => onError(`No fue posible conectar a la impresora en ${ip}. Verifica IP y conectividad.`)
        );
    };

    document.getElementById('preview-zpl')?.addEventListener('click', () => {
        previewEl.textContent = buildZpl();
        previewEl.classList.remove('hidden');
        setStatus('ZPL de prueba generado.');
    });

    document.getElementById('test-printer-connection')?.addEventListener('click', () => {
        if (!ensureBrowserPrint()) {
            return;
        }

        const connectionType = connectionTypeInput?.value || 'usb';
        if (connectionType === 'network') {
            setStatus('Validando impresora por red...');
            validateNetworkPrinter(
                (_, ip) => setStatus(`Conexión por red OK: ${ip}`),
                (error) => setStatus(error, true)
            );
            return;
        }

        setStatus('Buscando impresora USB...');
        resolveUsbPrinter(
            (printer) => {
                validateUsbPrinter(
                    printer,
                    (checkedPrinter) => setStatus(`Conexión USB OK: ${checkedPrinter.name}`),
                    (error) => setStatus(error, true)
                );
            },
            (error) => setStatus(error, true)
        );
    });

    document.getElementById('test-print')?.addEventListener('click', () => {
        const zpl = buildZpl();

        if (!ensureBrowserPrint()) {
            return;
        }

        const connectionType = connectionTypeInput?.value || 'usb';
        if (connectionType === 'network') {
            setStatus('Verificando impresora de red antes de imprimir...');
            validateNetworkPrinter(
                (printer, ip) => {
                    printer.send(zpl,
                        () => setStatus(`Impresión de prueba enviada por red a ${ip}.`),
                        (error) => setStatus(`Error de impresión por red: ${error}`, true)
                    );
                },
                (error) => setStatus(error, true)
            );
            return;
        }

        setStatus('Verificando impresora USB antes de imprimir...');
        resolveUsbPrinter(
            (printer) => {
                printer.send(zpl,
                    () => setStatus(`Impresión de prueba enviada a ${printer.name}.`),
                    (error) => setStatus(`Error de impresión USB: ${error}`, true)
                );
            },
            (error) => setStatus(error, true)
        );
    });

    connectionTypeInput?.addEventListener('change', () => {
        const isUsb = (connectionTypeInput.value || 'usb') === 'usb';
        if (printerSelectInput) {
            printerSelectInput.disabled = !isUsb;
        }
        if (refreshPrintersButton) {
            refreshPrintersButton.disabled = !isUsb;
        }
    });

    refreshPrintersButton?.addEventListener('click', () => {
        if (!ensureBrowserPrint()) {
            return;
        }

        setStatus('Buscando impresoras USB disponibles...');
        listUsbPrinters();
    });

    printerSelectInput?.addEventListener('change', () => {
        const selected = pickSelectedPrinter();
        if (!selected) {
            return;
        }

        if (defaultPrinterNameInput) {
            defaultPrinterNameInput.value = selected.name || '';
        }

        setStatus(`Impresora seleccionada: ${selected.name || 'Sin nombre'}.`);
    });

    if (connectionTypeInput) {
        connectionTypeInput.dispatchEvent(new Event('change'));
    }

    if (ensureBrowserPrint()) {
        listUsbPrinters({ silent: true });
    }
})();
</script>
