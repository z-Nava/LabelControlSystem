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

<div id="dummy-template-form" class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
        <label class="block text-sm font-medium text-slate-700">Conexión</label>
        <select name="connection_type" id="connection_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            <option value="usb" @selected(old('connection_type', $template->connection_type ?? 'usb') === 'usb')>USB</option>
            <option value="network" @selected(old('connection_type', $template->connection_type ?? '') === 'network')>Red (IP)</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Printer name (opcional)</label>
        <input name="default_printer_name" id="default_printer_name" value="{{ old('default_printer_name', $template->default_printer_name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Printer IP (si red)</label>
        <input name="default_printer_ip" id="default_printer_ip" value="{{ old('default_printer_ip', $template->default_printer_ip ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    </div>

    <div class="md:col-span-2 mt-2 border-t pt-4">
        <h2 class="font-semibold text-slate-900">Layout (posiciones)</h2>
        <p class="text-xs text-slate-600 mt-1">Configura posición de FG, JOB, consecutivo y QR para RMT/RW.</p>
    </div>

    @foreach([
        ['qr_x','QR X',30],['qr_y','QR Y',65],['qr_magnification','QR Magnificación',4],['fg_x','FG X',360],['fg_y','FG Y',70],['fg_font_size','FG Font',40],
        ['job_x','JOB X',360],['job_y','JOB Y',130],['job_font_size','JOB Font',34],['consecutive_x','Consecutivo X',380],['consecutive_y','Consecutivo Y',250],['consecutive_font_size','Consecutivo Font',58],
        ['title_x','Título X',20],['title_y','Título Y',20],['title_font_size','Título Font',44]
    ] as [$field,$label,$default])
        <div>
            <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
            <input type="number" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, data_get($template, $field, $default)) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
        </div>
    @endforeach

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

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}> Template activo
        </label>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
        <h3 class="font-semibold text-slate-900">Pruebas Zebra Browser Print</h3>
        <p class="mt-1 text-xs text-slate-600">Genera ZPL de prueba con datos dummy y envía a impresora para validar posiciones.</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <button id="test-usb-connection" type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">Probar conexión USB</button>
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

    const getValue = (id, fallback = '') => document.getElementById(id)?.value ?? fallback;

    const buildZpl = () => {
        const dummyType = getValue('dummy_type', 'rmt');
        const title = dummyType === 'rw' ? 'RW Dummy QR' : 'RMT Dummy QR';

        const qrPayload = '^DM^479124001^QB479124001UN-A01-OP21PSE^0000000014^';

        return [
            '^XA',
            '^CI28',
            '^PW820',
            '^LL400',
            '^LH0,0',
            `^FO${getValue('title_x',20)},${getValue('title_y',20)}^A0N,${getValue('title_font_size',44)},${getValue('title_font_size',44)}^FD${title}^FS`,
            `^FO${getValue('qr_x',30)},${getValue('qr_y',65)}^BQN,2,${getValue('qr_magnification',4)}`,
            `^FD${getValue('qr_orientation','N')},A${qrPayload}^FS`,
            `^FO${getValue('fg_x',360)},${getValue('fg_y',70)}^A0N,${getValue('fg_font_size',40)},${getValue('fg_font_size',40)}^FD479124001^FS`,
            `^FO${getValue('job_x',360)},${getValue('job_y',130)}^A0N,${getValue('job_font_size',34)},${getValue('job_font_size',34)}^FDQB479124001UN-A01-OP21PSE^FS`,
            `^FO${getValue('consecutive_x',380)},${getValue('consecutive_y',250)}^A0N,${getValue('consecutive_font_size',58)},${getValue('consecutive_font_size',58)}^FD0000000014^FS`,
            '^XZ'
        ].join('\n');
    };

    document.getElementById('preview-zpl')?.addEventListener('click', () => {
        previewEl.textContent = buildZpl();
        previewEl.classList.remove('hidden');
        statusEl.textContent = 'ZPL de prueba generado.';
    });

    document.getElementById('test-usb-connection')?.addEventListener('click', () => {
        if (!window.BrowserPrint) {
            statusEl.textContent = 'BrowserPrint no está disponible en este navegador/equipo.';
            return;
        }

        window.BrowserPrint.getDefaultDevice('printer',
            function (printer) {
                if (!printer) {
                    statusEl.textContent = 'No se detectó impresora Zebra por USB.';
                    return;
                }
                statusEl.textContent = `Conexión USB OK: ${printer.name}`;
                const printerInput = document.getElementById('default_printer_name');
                if (printerInput && !printerInput.value) printerInput.value = printer.name;
            },
            function (error) {
                statusEl.textContent = `Error de conexión USB: ${error}`;
            }
        );
    });

    document.getElementById('test-print')?.addEventListener('click', () => {
        const zpl = buildZpl();
        const connectionType = getValue('connection_type', 'usb');

        if (!window.BrowserPrint) {
            statusEl.textContent = 'BrowserPrint no está disponible en este navegador/equipo.';
            return;
        }

        if (connectionType === 'network') {
            const ip = getValue('default_printer_ip', '');
            if (!ip) {
                statusEl.textContent = 'Captura IP de impresora para prueba por red.';
                return;
            }

            const printer = new window.BrowserPrint.Device(ip, undefined, 'network');
            printer.send(zpl,
                () => statusEl.textContent = `Impresión de prueba enviada por red a ${ip}.`,
                (error) => statusEl.textContent = `Error de impresión por red: ${error}`
            );
            return;
        }

        window.BrowserPrint.getDefaultDevice('printer',
            function (printer) {
                if (!printer) {
                    statusEl.textContent = 'No se detectó impresora USB para impresión de prueba.';
                    return;
                }

                printer.send(zpl,
                    () => statusEl.textContent = `Impresión de prueba enviada a ${printer.name}.`,
                    (error) => statusEl.textContent = `Error de impresión USB: ${error}`
                );
            },
            function (error) {
                statusEl.textContent = `Error de conexión USB: ${error}`;
            }
        );
    });
})();
</script>
