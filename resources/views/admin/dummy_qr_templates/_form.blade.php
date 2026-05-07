@csrf
@if ($errors->any())
    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                !
            </div>
            <div>
                <p class="font-semibold">No se pudo guardar el template.</p>
                <p class="mt-1 text-xs text-red-600">Revisa los campos marcados antes de continuar.</p>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

<div id="dummy-template-form" class="space-y-6">
    {{-- Header / guía rápida --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-red-700 bg-red-700 px-5 py-5 text-white md:px-6">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-100">Configuración Dummy QR</p>
            <h1 class="mt-2 text-2xl font-bold">Template de dummy QR</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-200">
                Completa la configuración en orden: define los datos base, ajusta las posiciones visuales y valida la impresora con Zebra Browser Print.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-3 bg-slate-50 p-4 md:grid-cols-3 md:p-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-600 text-sm font-bold text-white">1</div>
                <p class="mt-3 text-sm font-semibold text-slate-900">Template base</p>
                <p class="mt-1 text-xs text-slate-500">Nombre, tipo dummy, DPI y medidas.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">2</div>
                <p class="mt-3 text-sm font-semibold text-slate-900">Layout ZPL</p>
                <p class="mt-1 text-xs text-slate-500">QR, FG, JOB, Consecutivo y Título.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">3</div>
                <p class="mt-3 text-sm font-semibold text-slate-900">Impresora</p>
                <p class="mt-1 text-xs text-slate-500">Conexión, media detectada y prueba final.</p>
            </div>
        </div>
    </section>

    {{-- Paso 1 --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
        <div class="mb-5 border-b border-slate-100 pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 1</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Define el template base</h2>
            <p class="mt-1 text-sm text-slate-500">Guarda los datos generales que identifican el template y su tamaño físico.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <label class="block text-sm font-semibold text-slate-700">Nombre template</label>
                <input name="name" value="{{ old('name', $template->name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" placeholder="Ej. Dummy QR RMT 2x1" required />
            </div>
            <div class="lg:col-span-3">
                <label class="block text-sm font-semibold text-slate-700">Tipo dummy</label>
                <select name="dummy_type" id="dummy_type" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" required>
                    <option value="rmt" @selected(old('dummy_type', $template->dummy_type ?? 'rmt') === 'rmt')>RMT Dummy QR</option>
                    <option value="rw" @selected(old('dummy_type', $template->dummy_type ?? '') === 'rw')>RW Dummy QR</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">DPI</label>
                <select name="dpi" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" required>
                    @foreach([203,300] as $dpi)
                        <option value="{{ $dpi }}" @selected((int) old('dpi', $template->dpi ?? 203) === $dpi)>{{ $dpi }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-slate-700">Orientación QR</label>
                <select name="qr_orientation" id="qr_orientation" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" required>
                    @foreach(['N' => 'Normal', 'R' => '90°', 'I' => '180°', 'B' => '270°'] as $orientation => $label)
                        <option value="{{ $orientation }}" @selected(old('qr_orientation', $template->qr_orientation ?? 'N') === $orientation)>{{ $orientation }} · {{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-3">
                <label class="block text-sm font-semibold text-slate-700">Ancho (mm)</label>
                <input type="number" step="0.01" name="width_mm" value="{{ old('width_mm', $template->width_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" placeholder="Ej. 50.80" />
            </div>
            <div class="lg:col-span-3">
                <label class="block text-sm font-semibold text-slate-700">Alto (mm)</label>
                <input type="number" step="0.01" name="height_mm" value="{{ old('height_mm', $template->height_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" placeholder="Ej. 25.40" />
            </div>
            <div class="lg:col-span-6">
                <label class="flex h-full items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" class="mt-1 rounded border-slate-300 text-red-600 focus:ring-red-500" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                    <span>
                        <span class="font-semibold">Template activo</span>
                        <span class="mt-1 block text-xs text-slate-500">Permite usar este template como opción disponible para impresión dummy.</span>
                    </span>
                </label>
            </div>
        </div>
    </section>

    {{-- Paso 2 --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
        <div class="mb-5 border-b border-slate-100 pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 2</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Ajusta el layout de impresión</h2>
            <p class="mt-1 text-sm text-slate-500">Edita coordenadas y tamaños mientras revisas la vista previa en tiempo real.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 2xl:grid-cols-12">
            <div class="space-y-4 2xl:col-span-7">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @foreach([
                        ['title' => 'A. Posiciones QR', 'description' => 'Código QR físico del dummy.', 'badge' => 'QR', 'fields' => [['qr_x','QR X',30],['qr_y','QR Y',65],['qr_magnification','QR Magnificación',4]]],
                        ['title' => 'B. Posiciones FG', 'description' => 'Texto FG que acompaña al QR.', 'badge' => 'FG', 'fields' => [['fg_x','FG X',360],['fg_y','FG Y',70],['fg_font_size','FG Font',40]]],
                        ['title' => 'C. Posiciones JOB', 'description' => 'Número de trabajo en la etiqueta.', 'badge' => 'JOB', 'fields' => [['job_x','JOB X',360],['job_y','JOB Y',130],['job_font_size','JOB Font',34]]],
                        ['title' => 'D. Posiciones Consecutivo', 'description' => 'Consecutivo grande del dummy.', 'badge' => '#', 'fields' => [['consecutive_x','Consecutivo X',380],['consecutive_y','Consecutivo Y',250],['consecutive_font_size','Consecutivo Font',58]]],
                    ] as $group)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-900">{{ $group['title'] }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ $group['description'] }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $group['badge'] }}</span>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach($group['fields'] as [$field,$label,$default])
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
                                        <input type="number" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, data_get($template, $field, $default)) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" required />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="rounded-2xl border border-slate-200 p-4 lg:col-span-2">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">E. Posiciones Título</h3>
                                <p class="mt-1 text-xs text-slate-500">Título principal impreso en la parte superior del dummy.</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Título</span>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            @foreach([['title_x','Título X',20],['title_y','Título Y',20],['title_font_size','Título Font',44]] as [$field,$label,$default])
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, data_get($template, $field, $default)) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" required />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <aside class="2xl:col-span-5">
                <div class="sticky top-6 rounded-3xl border border-slate-200 bg-slate-50 p-4 shadow-inner">
                    <div class="mb-3 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Vista previa</p>
                            <h3 class="text-base font-bold text-slate-900">Posiciones del dummy</h3>
                            <p class="mt-1 text-xs text-slate-500">Mueve X/Y y tamaños para ver QR, FG, JOB, Consecutivo y Título.</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">Canvas</span>
                    </div>

                    <div class="overflow-auto rounded-2xl border border-slate-200 bg-white p-3">
                        <canvas
                            id="layout-preview-stage"
                            class="rounded-lg border border-dashed border-slate-300 bg-white shadow-inner"
                            width="451"
                            height="220"
                        ></canvas>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <span class="font-bold text-slate-900">A · QR</span>
                            <p class="mt-1 text-slate-500">Código principal y magnificación.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <span class="font-bold text-slate-900">B · Textos</span>
                            <p class="mt-1 text-slate-500">FG, JOB, consecutivo y título.</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-red-100 bg-red-50 p-3 text-xs text-red-800">
                        <p class="font-semibold">Orientación QR activa</p>
                        <p class="mt-1">N = Normal · R = 90° · I = 180° · B = 270°</p>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    {{-- Paso 3 --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
        <div class="mb-5 border-b border-slate-100 pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 3</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Configura la impresora y prueba</h2>
            <p class="mt-1 text-sm text-slate-500">Selecciona conexión, consulta media detectada y genera ZPL de prueba.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="space-y-4 xl:col-span-7">
                <div class="rounded-2xl border border-slate-200 p-4">
                    <h3 class="text-base font-bold text-slate-900">Datos de conexión</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Conexión</label>
                            <select name="connection_type" id="connection_type" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" required>
                                <option value="usb" @selected(old('connection_type', $template->connection_type ?? 'usb') === 'usb')>USB</option>
                                <option value="network" @selected(old('connection_type', $template->connection_type ?? '') === 'network')>Red (IP)</option>
                            </select>
                        </div>
                        <div>
                            <div class="flex items-end justify-between gap-2">
                                <label class="block text-sm font-semibold text-slate-700">Impresoras USB detectadas</label>
                                <button id="refresh-printers" type="button" class="rounded-lg border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-100">Actualizar</button>
                            </div>
                            <select id="usb_printer_select" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                <option value="">Detecta impresoras para seleccionar...</option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Si hay varias impresoras conectadas, elige aquí cuál usar.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Printer name (opcional)</label>
                            <input name="default_printer_name" id="default_printer_name" value="{{ old('default_printer_name', $template->default_printer_name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" placeholder="Se autocompleta al elegir USB" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Printer IP (si red)</label>
                            <input name="default_printer_ip" id="default_printer_ip" value="{{ old('default_printer_ip', $template->default_printer_ip ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" placeholder="Ej. 192.168.1.50" />
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <h3 class="font-bold text-slate-900">Pruebas Zebra Browser Print</h3>
                    <p class="mt-1 text-xs text-slate-600">Valida conexión real y genera ZPL de prueba con datos dummy para revisar posiciones.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button id="test-printer-connection" type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Validar conexión actual</button>
                        <button id="preview-zpl" type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Ver ZPL generado</button>
                        <button id="test-print" type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Impresión de prueba</button>
                    </div>
                    <pre id="zpl-preview" class="mt-3 hidden max-h-56 overflow-auto rounded bg-slate-900 p-3 text-xs text-emerald-200"></pre>
                    <div id="printer-test-status" class="mt-2 text-sm text-slate-700">Sin pruebas ejecutadas.</div>
                </div>
            </div>

            <aside class="xl:col-span-5">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 shadow-inner">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Media detectada</p>
                            <h3 class="text-base font-bold text-slate-900">Datos de impresora</h3>
                        </div>
                        <button id="read-printer-media" type="button" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            Consultar tamaño/media
                        </button>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-2 text-xs text-slate-700 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-white p-3"><span class="font-semibold">ezpl.print_width:</span> <span id="printer-print-width">—</span></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3"><span class="font-semibold">zpl.label_length:</span> <span id="printer-label-length">—</span></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3"><span class="font-semibold">media.type:</span> <span id="printer-media-type">—</span></div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3"><span class="font-semibold">print.tone:</span> <span id="printer-print-tone">—</span></div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Usa esta información para comparar el tamaño configurado con la media real de Zebra.</p>
                </div>
            </aside>
        </div>
    </section>
</div>

<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
