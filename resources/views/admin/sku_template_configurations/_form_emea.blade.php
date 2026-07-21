@csrf

@if ($errors->any())
    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                !
            </div>
            <div>
                <p class="font-semibold">No se pudo guardar la configuración.</p>
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

<div
    id="sku-template-configuration-form"
    class="space-y-6"
    data-default-serial-ul="L36BH2606007A7"
    data-default-serial-emea="50555401123456A1234"
    data-default-serial-anz="AF02F2019 A 00001 A2026"
    data-default-sku="2978-OCUT"
    data-template-market="EMEA"
>
    {{-- Header / guía rápida --}}
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-red-700 bg-red-700 px-5 py-5 text-white md:px-6">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-100">Configuración de impresión</p>
            <h1 class="mt-2 text-2xl font-bold">Template y perfil EMEA por SKU</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-200">
                Completa esta configuración en orden. Primero selecciona el SKU, después define el tamaño del template,
                ajusta el layout de impresión y finalmente prueba la conexión con la impresora.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-3 bg-slate-50 p-4 md:grid-cols-4 md:p-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-600 text-sm font-bold text-white">1</div>
                <p class="mt-3 text-sm font-semibold text-slate-900">SKU y etiqueta</p>
                <p class="mt-1 text-xs text-slate-500">Mercado, SKU, Serial o Rating.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">2</div>
                <p class="mt-3 text-sm font-semibold text-slate-900">Template base</p>
                <p class="mt-1 text-xs text-slate-500">Nombre, DPI y medidas físicas.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">3</div>
                <p class="mt-3 text-sm font-semibold text-slate-900">Layout ZPL</p>
                <p class="mt-1 text-xs text-slate-500">QR, SKU, SN y bloques impresos.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">4</div>
                <p class="mt-3 text-sm font-semibold text-slate-900">Impresora</p>
                <p class="mt-1 text-xs text-slate-500">Perfil, conexión y prueba final.</p>
            </div>
        </div>
    </section>

    {{-- Paso 1 --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
        <div class="mb-5 flex flex-col gap-3 border-b border-slate-100 pb-4 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 1</p>
                <h2 class="mt-1 text-xl font-bold text-slate-900">Selecciona el SKU y el tipo de etiqueta</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Esta sección define el mercado, el SKU base y si la configuración se usará para etiqueta Serial o Rating.
                </p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800 md:max-w-sm">
                <span class="font-semibold">Tip:</span> el estándar serial se llena automáticamente desde el SKU seleccionado para evitar inconsistencias.
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
            <div class="space-y-5 xl:col-span-8">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">1A · Mercado y SKU</p>

                    <input type="hidden" name="serial_standard" id="serial_standard" value="EMEA">

                    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div class="lg:col-span-2">
                            <label for="label_sku_id" class="block text-sm font-semibold text-slate-700">SKU</label>
                            <select
                                name="label_sku_id"
                                id="label_sku_id"
                                class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                                required
                            >
                                @foreach($marketStandards as $standard)
                                    <optgroup label="{{ $standard }}">
                                        @foreach(($skuGroups[$standard] ?? collect()) as $sku)
                                            <option
                                                value="{{ $sku->id }}"
                                                data-sku-code="{{ $sku->sku }}"
                                                data-serial-standard="{{ $sku->serial_standard ?? 'UL' }}"
                                                data-label-part-number="{{ $sku->label_part_number }}"
                                                data-console-sku="{{ $sku->console_sku }}"
                                                data-assembly-part-number="{{ $sku->assembly_part_number }}"
                                                data-packaging-part-number="{{ $sku->packaging_part_number }}"
                                                data-emea-sku="{{ $sku->emea_sku }}"
                                                data-anz-sku="{{ $sku->anz_sku }}"
                                                data-example-serial="{{ $skuPreviewSerials[$sku->id] ?? '' }}"
                                                data-anz-customer-tool-code="{{ $skuAnzCustomerToolCodes[$sku->id] ?? '' }}"
                                                data-anz-qr-separator="{{ $skuAnzQrSeparators[$sku->id] ?? ' | ' }}"
                                                @selected((string) old('label_sku_id', $configuration->label_sku_id ?? '') === (string) $sku->id)
                                            >
                                                {{ $sku->sku }} · {{ $sku->label_part_number }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">
                                @if(isset($forcedStandard))
                                    Formulario dedicado {{ $forcedStandard }}: selecciona un SKU activo para este mercado.
                                @else
                                    Selecciona primero el mercado y después el SKU que corresponde a la etiqueta.
                                @endif
                            </p>
                        </div>

                        <div>
                            <label for="label_type" class="block text-sm font-semibold text-slate-700">Tipo de etiqueta</label>
                            <select
                                name="label_type"
                                id="label_type"
                                class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                                required
                            >
                                @foreach(['serial' => 'Serial', 'rating' => 'Rating'] as $type => $label)
                                    <option value="{{ $type }}" @selected($selectedLabelType === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Serial imprime QR + SKU + SN. Rating imprime SN y opcionalmente QR.</p>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-5 xl:col-span-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Resumen automático</p>

                    <div class="mt-4 space-y-3">
                        <div>
                            <label for="serial_standard_display" class="block text-sm font-semibold text-slate-700">Estándar serial</label>
                            <input
                                id="serial_standard_display"
                                class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700"
                                value="{{ $formState['selected_serial_standard'] ?? 'UL' }}"
                                readonly
                            />
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                            Este formulario está bloqueado a EMEA; usa únicamente SKUs y reglas de impresión EMEA.
                        </div>
                    </div>
                </div>

                <div
                    id="rating-qr-toggle-wrapper"
                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                    @if($selectedLabelType !== 'rating') style="display:none;" @endif
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Opciones Rating EMEA</p>

                    <label class="mt-3 flex items-start gap-3 rounded-xl bg-white p-3 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            name="rating_with_qr"
                            value="1"
                            class="mt-1 rounded border-slate-300 text-red-600 focus:ring-red-500"
                            {{ old('rating_with_qr', ($formState['rating_qr'] ?? false)) ? 'checked' : '' }}
                        >
                        <span>
                            <span class="font-semibold">Habilitar QR en Rating</span>
                            <span class="mt-1 block text-xs text-slate-500">Activa un QR adicional para etiquetas tipo Rating.</span>
                        </span>
                    </label>

                    <label class="mt-3 flex items-start gap-3 rounded-xl bg-white p-3 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            name="rating_hide_sku"
                            value="1"
                            class="mt-1 rounded border-slate-300 text-red-600 focus:ring-red-500"
                            {{ old('rating_hide_sku', ($formState['rating_hide_sku'] ?? false)) ? 'checked' : '' }}
                        >
                        <span>
                            <span class="font-semibold">Ocultar SKU en Rating con QR</span>
                            <span class="mt-1 block text-xs text-slate-500">En EMEA se usa para etiquetas Rating con SN + QR, sin SKU visible.</span>
                        </span>
                    </label>
                </div>
            </aside>
        </div>
    </section>

    {{-- Paso 2 --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
        <div class="mb-5 border-b border-slate-100 pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 2</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Define el template base</h2>
            <p class="mt-1 text-sm text-slate-500">
                Aquí se guarda la información general del template: nombre, resolución y tamaño físico de la etiqueta.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <label for="template_name" class="block text-sm font-semibold text-slate-700">Nombre del template</label>
                <input
                    id="template_name"
                    name="template_name"
                    value="{{ old('template_name', $configuration->template->name ?? '') }}"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                    placeholder="Ej. Serial UL 2x1 ZD421"
                    required
                />
                <p class="mt-1 text-xs text-slate-500">Usa un nombre claro para identificar el tamaño, mercado o tipo de etiqueta.</p>
            </div>

            <div class="lg:col-span-2">
                <label for="template_dpi" class="block text-sm font-semibold text-slate-700">DPI template</label>
                <select
                    id="template_dpi"
                    name="template_dpi"
                    class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                >
                    @foreach([203, 300] as $dpi)
                        <option value="{{ $dpi }}" @selected((int) old('template_dpi', $configuration->template->dpi ?? 203) === $dpi)>{{ $dpi }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label for="template_width_mm" class="block text-sm font-semibold text-slate-700">Ancho mm</label>
                <input
                    id="template_width_mm"
                    type="number"
                    step="0.01"
                    min="1"
                    name="template_width_mm"
                    value="{{ old('template_width_mm', $configuration->template->width_mm ?? '') }}"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                    placeholder="Ej. 50.8"
                />
            </div>

            <div class="lg:col-span-2">
                <label for="template_height_mm" class="block text-sm font-semibold text-slate-700">Alto mm</label>
                <input
                    id="template_height_mm"
                    type="number"
                    step="0.01"
                    min="1"
                    name="template_height_mm"
                    value="{{ old('template_height_mm', $configuration->template->height_mm ?? '') }}"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                    placeholder="Ej. 25.4"
                />
            </div>

            <div class="lg:col-span-1">
                <label class="block text-sm font-semibold text-slate-700">Estado</label>
                <label class="mt-2 flex h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="template_is_active"
                        value="1"
                        class="rounded border-slate-300 text-red-600 focus:ring-red-500"
                        {{ old('template_is_active', $configuration->template->is_active ?? true) ? 'checked' : '' }}
                    >
                    Activo
                </label>
            </div>
        </div>

        <div
            id="template-dots-summary"
            class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
        >
            Tamaño real calculado: completa DPI, ancho y alto para ajustar el layout físico.
        </div>
    </section>

    {{-- Paso 3 --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
        <div class="mb-5 border-b border-slate-100 pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 3</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Configura el layout de impresión ZPL</h2>
            <p class="mt-1 text-sm text-slate-500">
                Define dónde se imprimirá cada elemento. Las posiciones se manejan en dots de Zebra, no en milímetros.
            </p>
        </div>

        <div class="mb-6 rounded-3xl border border-slate-200 bg-slate-50 p-4 shadow-sm md:p-5 xl:p-6" id="live-layout-preview-panel">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-12 xl:items-stretch">
                <div class="xl:col-span-9">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 3 · Layout físico interactivo</p>
                            <h3 class="mt-1 text-lg font-bold text-slate-900" id="layout-context-title">Acomoda los elementos de impresión</h3>
                            <p class="mt-1 text-sm text-slate-500" id="layout-context-description">
                                Mueve los bloques en la etiqueta y después afina las coordenadas en las secciones A, B y C.
                            </p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm">
                            Arrastra · snap 5 dots
                        </span>
                    </div>

                    <div id="sku-layout-preview-canvas-frame" class="mt-4 overflow-x-auto rounded-2xl border border-dashed border-slate-300 bg-white p-3">
                        <div class="flex min-w-[1120px] justify-center">
                            <canvas id="sku-layout-preview-canvas" width="1120" height="520"></canvas>
                        </div>
                    </div>
                </div>

                <aside class="xl:col-span-3">
                    <div class="flex h-full flex-col justify-between rounded-2xl border border-slate-200 bg-white p-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Guía del layout</p>
                            <h4 class="mt-1 text-base font-bold text-slate-900">Qué debes mover según el tipo</h4>
                            <div class="mt-4 grid grid-cols-1 gap-2 text-sm">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <span class="font-bold text-slate-900">A · Rating</span>
                                    <p class="mt-1 text-xs text-slate-500">Texto principal de Rating. Usa sus X/Y y orientación.</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <span class="font-bold text-slate-900">B · QR</span>
                                    <p class="mt-1 text-xs text-slate-500">QR físico. Aplica para Serial y Rating con QR.</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <span class="font-bold text-slate-900">C · Serial</span>
                                    <p class="mt-1 text-xs text-slate-500">SKU visible y SN pequeño para etiquetas Serial.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                            <p class="font-semibold text-slate-900">Elemento seleccionado</p>
                            <p class="mt-1" id="layout-selected-element">Selecciona QR, SKU o SN en el canvas.</p>
                            <p class="mt-2 font-mono text-slate-600" id="layout-coordinate-summary">X/Y: --</p>
                        </div>

                        <div class="mt-4 rounded-xl border border-red-100 bg-red-50 p-3 text-xs text-red-800">
                            <p class="font-semibold">Orientación física activa</p>
                            <p class="mt-1" id="layout-orientation-summary">N = Normal · R = 90° · I = 180° · B = 270°</p>
                            <p class="mt-2 text-red-700" id="layout-active-elements">El canvas se actualizará según Serial, Rating o Rating con QR.</p>
                            <p class="mt-2 text-red-700" id="layout-scale-summary">Escala visual: —</p>
                        </div>

                        <div
                            id="layout-out-of-bounds-warning"
                            class="mt-3 hidden rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-semibold text-amber-800"
                        >
                            Advertencia: este elemento está fuera del área física de la etiqueta.
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 2xl:grid-cols-12">
            <div class="space-y-5 2xl:col-span-8">
                {{-- Texto principal / Rating --}}
                <div class="rounded-2xl border border-slate-200 p-4" data-layout-section="rating">
                    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">A. Texto principal para etiqueta Rating</h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Estas coordenadas se usan para Rating simple y Rating con QR. No se mezclan con el SN pequeño de Serial.
                            </p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Rating</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Rating X</label>
                            <input type="number" min="0" max="5000" step="1" name="serial_position_x" value="{{ old('serial_position_x', $formState['text_layout']['x'] ?? 40) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" required />
                            @error('serial_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Rating Y</label>
                            <input type="number" min="0" max="5000" step="1" name="serial_position_y" value="{{ old('serial_position_y', $formState['text_layout']['y'] ?? 40) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" required />
                            @error('serial_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Tamaño letra</label>
                            <input type="number" min="1" max="500" step="1" name="serial_font_size" value="{{ old('serial_font_size', $formState['text_layout']['font_size'] ?? 40) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" required />
                            @error('serial_font_size') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Orientación</label>
                            <select name="serial_orientation" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" required>
                                @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('serial_orientation', $formState['text_layout']['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('serial_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- QR --}}
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4" data-layout-section="qr">
                    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900" id="qr-layout-title">B. QR y contenido codificado</h3>
                            <p class="mt-1 text-xs text-slate-500" id="qr-layout-description">
                                Configura la posición física del QR y el contenido que se codificará.
                            </p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">QR</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">QR X</label>
                            <input type="number" min="0" max="5000" step="1" name="qr_position_x" value="{{ old('qr_position_x', $formState['qr_layout']['x'] ?? 30) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                            @error('qr_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">QR Y</label>
                            <input type="number" min="0" max="5000" step="1" name="qr_position_y" value="{{ old('qr_position_y', $formState['qr_layout']['y'] ?? 30) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                            @error('qr_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Magnificación</label>
                            <input type="number" name="qr_magnification" min="1" max="10" step="1" value="{{ old('qr_magnification', $formState['qr_layout']['magnification'] ?? 4) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                            @error('qr_magnification') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Orientación QR</label>
                            <select name="qr_orientation" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('qr_orientation', $formState['qr_layout']['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('qr_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div class="xl:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700">Contenido QR</label>
                            <select name="qr_content_mode" id="qr_content_mode" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                @foreach(($allowedQrContentByStandard[$activeStandard] ?? $allowedQrContentByStandard['UL']) as $value)
                                    <option value="{{ $value }}" @selected(old('qr_content_mode', $formState['qr_layout']['content_mode'] ?? 'auto') === $value)>{{ $qrContentOptions[$value] ?? $value }}</option>
                                @endforeach
                            </select>
                            @error('qr_content_mode') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Separador QR</label>
                            <select name="qr_separator" id="qr_separator" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                <option value="pipe" @selected(old('qr_separator', $formState['qr_layout']['separator'] ?? 'pipe') === 'pipe')>Pipe ( | )</option>
                                <option value="space" @selected(old('qr_separator', $formState['qr_layout']['separator'] ?? 'pipe') === 'space')>Espacio</option>
                                <option value="none" @selected(old('qr_separator', $formState['qr_layout']['separator'] ?? 'pipe') === 'none')>Sin separador</option>
                            </select>
                            @error('qr_separator') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Formato SN en QR</label>
                            <select name="qr_serial_style" id="qr_serial_style" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                @foreach(($allowedQrSerialStylesByStandard[$activeStandard] ?? $allowedQrSerialStylesByStandard['UL']) as $value)
                                    <option value="{{ $value }}" @selected(old('qr_serial_style', $formState['qr_layout']['serial_style'] ?? 'as_is') === $value)>{{ $qrSerialStyles[$value] ?? $value }}</option>
                                @endforeach
                            </select>
                            @error('qr_serial_style') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div id="qr-custom-fields-wrapper" class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="mb-4">
                            <h4 class="text-sm font-bold text-slate-900">QR personalizado</h4>
                            <p class="mt-1 text-xs text-slate-500">
                                Usa estos bloques cuando el QR deba armarse con varios valores. Ejemplo EMEA: 103 | Serial | EMEA SKU.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            @foreach([1,2,3] as $position)
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700">Bloque {{ $position }}</label>
                                    <select name="qr_custom_field_{{ $position }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                        @foreach(($allowedQrCustomByStandard[$activeStandard] ?? $allowedQrCustomByStandard['UL']) as $value)
                                            <option value="{{ $value }}" @selected(old('qr_custom_field_'.$position, $customFields[$position - 1] ?? '') === $value)>{{ $qrCustomOptions[$value] ?? $value }}</option>
                                        @endforeach
                                    </select>
                                    @error('qr_custom_field_'.$position) <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- SKU y SN --}}
                <div class="rounded-2xl border border-slate-200 p-4" data-layout-section="serial-text">
                    <div class="mb-4">
                        <h3 class="text-base font-bold text-slate-900">C. Texto visible solo para etiqueta Serial</h3>
                        <p class="mt-1 text-xs text-slate-500">Configura el SKU principal, el SN pequeño y la repetición de bloques. Rating usa la sección A.</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bloque SKU principal</p>
                        <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div data-layout-group="sku">
                                <label class="block text-sm font-semibold text-slate-700">SKU X</label>
                                <input type="number" min="0" max="5000" step="1" name="sku_position_x" value="{{ old('sku_position_x', $formState['sku_layout']['x'] ?? 170) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                                @error('sku_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <div data-layout-group="sku">
                                <label class="block text-sm font-semibold text-slate-700">SKU Y</label>
                                <input type="number" min="0" max="5000" step="1" name="sku_position_y" value="{{ old('sku_position_y', $formState['sku_layout']['y'] ?? 35) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                                @error('sku_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <div data-layout-group="sku">
                                <label class="block text-sm font-semibold text-slate-700">Tamaño SKU</label>
                                <input type="number" min="1" max="500" step="1" name="sku_font_size" value="{{ old('sku_font_size', $formState['sku_layout']['font_size'] ?? 44) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                                @error('sku_font_size') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <div data-layout-group="sku">
                                <label class="block text-sm font-semibold text-slate-700">Orientación SKU</label>
                                <select name="sku_orientation" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('sku_orientation', $formState['sku_layout']['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('sku_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bloque SN pequeño</p>
                        <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">SN X</label>
                                <input type="number" min="0" max="5000" step="1" name="sn_position_x" value="{{ old('sn_position_x', $formState['sn_layout']['x'] ?? 170) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                                @error('sn_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">SN Y</label>
                                <input type="number" min="0" max="5000" step="1" name="sn_position_y" value="{{ old('sn_position_y', $formState['sn_layout']['y'] ?? 95) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                                @error('sn_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">Tamaño SN</label>
                                <input type="number" min="1" max="500" step="1" name="sn_font_size" value="{{ old('sn_font_size', $formState['sn_layout']['font_size'] ?? 22) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                                @error('sn_font_size') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">Orientación SN</label>
                                <select name="sn_orientation" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                    @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('sn_orientation', $formState['sn_layout']['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('sn_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <div id="sn-prefix-wrapper" @if($selectedLabelType !== 'serial') style="display:none;" @endif>
                                <label class="block text-sm font-semibold text-slate-700">Prefijo texto SN</label>
                                <input name="sn_prefix" value="{{ old('sn_prefix', $formState['sn_layout']['prefix'] ?? 'SN:') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                                @error('sn_prefix') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">Bloques por etiqueta</label>
                                <input type="number" name="serial_block_count" min="1" max="4" step="1" value="{{ old('serial_block_count', $formState['serial_block_layout']['count'] ?? 1) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                                @error('serial_block_count') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">Offset vertical</label>
                                <input type="number" name="serial_block_offset_y" min="0" max="5000" step="1" value="{{ old('serial_block_offset_y', $formState['serial_block_layout']['offset_y'] ?? 180) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100" />
                                @error('serial_block_offset_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Preview --}}
            <aside class="2xl:col-span-4">
                <div class="sticky top-4 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm" id="live-print-preview">
                    <div class="mb-4 border-b border-slate-100 pb-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vista previa</p>
                        <h3 class="mt-1 text-lg font-bold text-slate-900">Contenido real de impresión</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            El acomodo físico se controla en el canvas; aquí confirmas QR, SKU, SN y payload.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Etiqueta simulada</p>
                        <div class="mt-3 grid min-h-[180px] grid-cols-1 gap-3 rounded-2xl border border-dashed border-slate-300 bg-white p-3 sm:grid-cols-[auto_1fr] sm:items-center">
                            <div id="live-preview-qr" class="flex h-36 w-36 items-center justify-center rounded-xl bg-slate-50 text-center text-xs text-slate-500">
                                QR no disponible
                            </div>
                            <div class="space-y-2 text-sm text-slate-700">
                                <div id="live-preview-sku-line" class="break-words rounded-xl bg-slate-50 px-3 py-2 font-bold shadow-sm">—</div>
                                <div id="live-preview-sn-line" class="break-all rounded-xl bg-slate-50 px-3 py-2 font-mono shadow-sm">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Detalle técnico</p>
                        <dl class="mt-3 grid grid-cols-1 gap-3 text-sm text-slate-700 sm:grid-cols-2">
                            <div>
                                <dt class="font-semibold text-slate-900">Mercado</dt>
                                <dd id="live-preview-standard">—</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-900">Tipo</dt>
                                <dd id="live-preview-label-type">—</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-900">Modo QR</dt>
                                <dd id="live-preview-qr-mode">—</dd>
                            </div>
                            <div>
                                <dt class="font-semibold text-slate-900">Formato SN</dt>
                                <dd id="live-preview-serial-style">—</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="font-semibold text-slate-900">Payload QR final</dt>
                                <dd class="mt-1 break-words rounded-xl bg-white px-3 py-2 font-mono text-xs text-slate-800 shadow-sm" id="live-preview-qr-payload">—</dd>
                            </div>
                        </dl>

                        <div id="live-preview-warning" class="mt-4 hidden rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-800"></div>
                    </div>
                </div>
            </aside>
        </div>

    </section>

    {{-- Paso 4 --}}
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
        <div class="mb-5 border-b border-slate-100 pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 4</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">Configura el perfil de impresión</h2>
            <p class="mt-1 text-sm text-slate-500">
                Define el nombre del perfil, método de conexión y ejecuta una prueba antes de guardar.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
            <div class="space-y-5 xl:col-span-7">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">4A · Datos del perfil</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="profile_name" class="block text-sm font-semibold text-slate-700">Nombre del profile</label>
                            <input
                                id="profile_name"
                                name="profile_name"
                                value="{{ old('profile_name', $configuration->name ?? '') }}"
                                class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                                placeholder="Ej. ZD421 USB - Serial UL"
                                required
                            />
                            <p class="mt-1 text-xs text-slate-500">Nombre visible para identificar la configuración de impresión.</p>
                        </div>

                        <div>
                            <label for="profile_dpi" class="block text-sm font-semibold text-slate-700">DPI profile</label>
                            <select
                                id="profile_dpi"
                                name="profile_dpi"
                                class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                            >
                                @foreach([203, 300] as $dpi)
                                    <option value="{{ $dpi }}" @selected((int) old('profile_dpi', $configuration->dpi ?? 203) === $dpi)>{{ $dpi }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="connection_type" class="block text-sm font-semibold text-slate-700">Tipo de conexión</label>
                            <select
                                name="connection_type"
                                id="connection_type"
                                class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                                required
                            >
                                <option value="usb" @selected($selectedConnectionType === 'usb')>USB</option>
                                <option value="network" @selected($selectedConnectionType === 'network')>Red (IP)</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label for="default_printer_name" class="block text-sm font-semibold text-slate-700">Printer name</label>
                            <input
                                name="default_printer_name"
                                id="default_printer_name"
                                value="{{ old('default_printer_name', $configuration->default_printer_name ?? '') }}"
                                class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                                placeholder="Se autocompleta al elegir una impresora USB"
                                required
                            />
                            <p class="mt-1 text-xs text-slate-500">Para USB se puede llenar automáticamente desde Zebra Browser Print.</p>
                        </div>

                        <div id="usb-printers-wrapper" class="md:col-span-2">
                            <label for="usb_printer_select" class="block text-sm font-semibold text-slate-700">Impresoras USB detectadas</label>
                            <select id="usb_printer_select" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">
                                <option value="">Primero haz clic en &quot;Probar conexión USB&quot; para listar impresoras</option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Si tienes más de una impresora conectada, selecciona aquí cuál quieres usar.</p>
                        </div>

                        <div id="printer-ip-wrapper" class="md:col-span-2">
                            <label for="default_printer_ip" class="block text-sm font-semibold text-slate-700">Printer IP</label>
                            <input
                                name="default_printer_ip"
                                id="default_printer_ip"
                                value="{{ old('default_printer_ip', $configuration->default_printer_ip ?? '') }}"
                                class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                                placeholder="Ej. 192.168.1.50"
                            />
                            <p class="mt-1 text-xs text-slate-500">Solo aplica cuando la conexión sea por red.</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="profile_is_active"
                                    value="1"
                                    class="mt-1 rounded border-slate-300 text-red-600 focus:ring-red-500"
                                    {{ old('profile_is_active', $configuration->is_active ?? true) ? 'checked' : '' }}
                                >
                                <span>
                                    <span class="font-semibold">Profile activo</span>
                                    <span class="mt-1 block text-xs text-slate-500">Permite usar esta configuración para impresión.</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="xl:col-span-5">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">4B · Pruebas de impresora</p>
                    <h3 class="mt-1 text-base font-bold text-slate-900">Validación antes de guardar</h3>
                    <p class="mt-1 text-xs text-slate-600">
                        Para USB, primero lista las impresoras detectadas. Después ejecuta una impresión de prueba o revisa el ZPL generado.
                    </p>

                    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <button id="test-usb-connection" type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            Probar USB
                        </button>
                        <button id="test-print" type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                            Imprimir prueba
                        </button>
                        <button id="preview-zpl" type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            Ver ZPL
                        </button>
                    </div>

                    <input type="hidden" name="usb_connected" id="usb_connected" value="{{ old('usb_connected', '0') }}" />

                    <div id="printer-test-status" class="mt-4 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                        Sin prueba de conexión.
                    </div>

                    <div id="zpl-preview-wrapper" class="mt-4 hidden">
                        <label for="zpl-preview-output" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">ZPL generado para prueba</label>
                        <textarea
                            id="zpl-preview-output"
                            readonly
                            rows="12"
                            class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono text-xs text-slate-700"
                        ></textarea>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</div>
