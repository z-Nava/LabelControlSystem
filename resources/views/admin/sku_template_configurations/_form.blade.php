@csrf
@if ($errors->any())
    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <p class="font-semibold">No se pudo guardar la configuración.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
    <p class="font-semibold text-slate-900">Flujo recomendado</p>
    <p class="mt-1">Completa los pasos en orden: primero define el SKU/tipo de etiqueta, después el template, luego el layout y finalmente el perfil de impresión.</p>
</div>

<div class="space-y-5"
    id="sku-template-configuration-form"
    data-default-serial-ul="L36BH2606007A7"
    data-default-serial-emea="50555401123456A1234"
    data-default-serial-anz="AF02F2019 A 00001 A2026"
    data-default-sku="2978-OCUT">

    <section class="rounded-2xl border border-slate-200 p-4">
        <div class="mb-4 border-b border-slate-100 pb-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 1</p>
            <h2 class="text-lg font-semibold text-slate-900">Selecciona SKU y comportamiento de etiqueta</h2>
            <p class="mt-1 text-xs text-slate-500">Este paso define la base de la configuración y activa campos relevantes para Serial o Rating.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700">SKU</label>
                <div class="mt-2 inline-flex rounded-xl border border-slate-200 p-1" role="group" aria-label="Filtro de estándar SKU">
                    @foreach(($availableStandards ?? ['UL', 'EMEA', 'ANZ']) as $standard)
                <button type="button"
                        data-sku-standard-filter="{{ $standard }}"
                        class="rounded-lg px-3 py-1 text-xs font-semibold transition {{ (($formState['selected_serial_standard'] ?? 'UL') === $standard) ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                            {{ $standard }}
                            <span class="ml-1 text-[10px] opacity-80">({{ ($skuGroups[$standard] ?? collect())->count() }})</span>
                        </button>
                    @endforeach
                </div>
                <select name="label_sku_id" id="label_sku_id" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    @foreach(($availableStandards ?? ['UL', 'EMEA', 'ANZ']) as $standard)
                        <optgroup label="{{ $standard }}">
                            @foreach(($skuGroups[$standard] ?? collect()) as $sku)
                                <option value="{{ $sku->id }}"
                                        data-sku-code="{{ $sku->sku }}"
                                        data-serial-standard="{{ $sku->serial_standard ?? 'UL' }}"
                                        data-label-part-number="{{ $sku->label_part_number }}"
                                        data-console-sku="{{ $sku->console_sku }}"
                                        data-assembly-part-number="{{ $sku->assembly_part_number }}"
                                        data-packaging-part-number="{{ $sku->packaging_part_number }}"
                                        data-emea-sku="{{ $sku->emea_sku }}"
                                        data-anz-sku="{{ $sku->anz_sku }}"
                                        data-example-serial="{{ $skuPreviewSerials[$sku->id] ?? '' }}"
                                        @selected((string) old('label_sku_id', $configuration->label_sku_id ?? '') === (string) $sku->id)>
                                    {{ $sku->sku }} · {{ $sku->label_part_number }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Selecciona el mercado (UL / EMEA / ANZ) y luego el SKU con serial format activo.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Estándar serial</label>
                <input id="serial_standard_display" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700" value="{{ $formState['selected_serial_standard'] ?? 'UL' }}" readonly />
                <input type="hidden" name="serial_standard" id="serial_standard" value="{{ $formState['selected_serial_standard'] ?? 'UL' }}" />
                <p class="mt-1 text-xs text-slate-500">Se toma del SKU seleccionado para evitar inconsistencias.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Tipo de etiqueta</label>
                <select name="label_type" id="label_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    @foreach(['serial', 'rating'] as $type)
                        <option value="{{ $type }}" @selected($formState['selected_label_type'] === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:pt-6" id="rating-qr-toggle-wrapper" @if(($formState['selected_label_type'] ?? 'serial') !== 'rating') style="display:none;" @endif>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="rating_with_qr" value="1" class="rounded border-slate-300"
                           {{ old('rating_with_qr', ($formState['rating_qr'] ?? false)) ? 'checked' : '' }}>
                    Habilitar QR en etiquetas Rating
                </label>
                <p class="mt-1 text-xs text-slate-500">Solo aplica para tipo Rating.</p>

                <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="rating_hide_sku" value="1" class="rounded border-slate-300"
                           {{ old('rating_hide_sku', ($formState['rating_hide_sku'] ?? false)) ? 'checked' : '' }}>
                    Ocultar SKU en Rating con QR (solo SN + QR)
                </label>
                <p class="mt-1 text-xs text-slate-500">En EMEA/ANZ normalmente se oculta SKU para imprimir solo SN + QR.</p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 p-4">
        <div class="mb-4 border-b border-slate-100 pb-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 2</p>
            <h2 class="text-lg font-semibold text-slate-900">Configura el template base</h2>
            <p class="mt-1 text-xs text-slate-500">Define metadatos generales del template y sus dimensiones físicas.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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

            <div>
                <label class="block text-sm font-medium text-slate-700">Ancho mm</label>
                <input name="template_width_mm" value="{{ old('template_width_mm', $configuration->template->width_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Alto mm</label>
                <input name="template_height_mm" value="{{ old('template_height_mm', $configuration->template->height_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="template_is_active" value="1" {{ old('template_is_active', $configuration->template->is_active ?? true) ? 'checked' : '' }}> Template activo
                </label>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 p-4">
        <div class="mb-4 border-b border-slate-100 pb-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 3</p>
            <h2 class="text-lg font-semibold text-slate-900">Define layout de impresión (ZPL)</h2>
            <p class="mt-1 text-xs text-slate-500">Serial usa QR + SKU + SN. Rating usa SN y opcionalmente QR (en EMEA/ANZ puede ocultarse SKU).</p>
        </div>

        <div class="rounded-2xl border border-slate-200 p-4" data-layout-section="rating">
            <div class="mb-3">
                <h3 class="font-semibold text-slate-900">Bloque de texto SN / Rating</h3>
                <p class="mt-1 text-xs text-slate-500">Este bloque se usa para Rating y como respaldo para layouts simples.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Posición serial X</label>
                    <input type="number" name="serial_position_x" value="{{ old('serial_position_x', $formState['text_layout']['x'] ?? 40) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                    @error('serial_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Posición serial Y</label>
                    <input type="number" name="serial_position_y" value="{{ old('serial_position_y', $formState['text_layout']['y'] ?? 40) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                    @error('serial_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tamaño de letra</label>
                    <input type="number" name="serial_font_size" value="{{ old('serial_font_size', $formState['text_layout']['font_size'] ?? 40) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                    @error('serial_font_size') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Orientación serial</label>
                    <select name="serial_orientation" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                        @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('serial_orientation', $formState['text_layout']['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('serial_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4" data-layout-section="serial">
            <div class="mb-3">
                <h3 class="font-semibold text-slate-900" id="qr-layout-title">Configuración etiqueta Serial con QR</h3>
                <p class="mt-1 text-xs text-slate-500" id="qr-layout-description">El QR codifica el serial completo; además se muestra el SKU grande y el SN en texto pequeño.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">QR X</label>
                    <input type="number" name="qr_position_x" value="{{ old('qr_position_x', $formState['qr_layout']['x'] ?? 30) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('qr_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">QR Y</label>
                    <input type="number" name="qr_position_y" value="{{ old('qr_position_y', $formState['qr_layout']['y'] ?? 30) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('qr_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Magnificación QR</label>
                    <input type="number" name="qr_magnification" min="1" max="10" value="{{ old('qr_magnification', $formState['qr_layout']['magnification'] ?? 4) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('qr_magnification') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Orientación QR</label>
                    <select name="qr_orientation" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('qr_orientation', $formState['qr_layout']['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('qr_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Contenido QR</label>
                    <select name="qr_content_mode" id="qr_content_mode" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        <option value="auto" @selected(old('qr_content_mode', $formState['qr_layout']['content_mode'] ?? 'auto') === 'auto')>Automático por tipo (recomendado)</option>
                        <option value="serial_full" @selected(old('qr_content_mode', $formState['qr_layout']['content_mode'] ?? 'auto') === 'serial_full')>Solo Serial completo</option>
                        <option value="rating_qr" @selected(old('qr_content_mode', $formState['qr_layout']['content_mode'] ?? 'auto') === 'rating_qr')>Solo QR rating (EMEA/ANZ)</option>
                        <option value="custom" @selected(old('qr_content_mode', $formState['qr_layout']['content_mode'] ?? 'auto') === 'custom')>Personalizado (hasta 3 bloques)</option>
                    </select>
                    @error('qr_content_mode') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Separador QR</label>
                    <select name="qr_separator" id="qr_separator" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        <option value="pipe" @selected(old('qr_separator', $formState['qr_layout']['separator'] ?? 'pipe') === 'pipe')>Pipe ( | )</option>
                        <option value="space" @selected(old('qr_separator', $formState['qr_layout']['separator'] ?? 'pipe') === 'space')>Espacio</option>
                        <option value="none" @selected(old('qr_separator', $formState['qr_layout']['separator'] ?? 'pipe') === 'none')>Sin separador</option>
                    </select>
                    @error('qr_separator') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Formato SN en QR</label>
                    <select name="qr_serial_style" id="qr_serial_style" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        <option value="as_is" @selected(old('qr_serial_style', $formState['qr_layout']['serial_style'] ?? 'as_is') === 'as_is')>Como viene del serial</option>
                        <option value="segmented" @selected(old('qr_serial_style', $formState['qr_layout']['serial_style'] ?? 'as_is') === 'segmented')>Separado (5055 36 01 000002 A2026)</option>
                        <option value="compact" @selected(old('qr_serial_style', $formState['qr_layout']['serial_style'] ?? 'as_is') === 'compact')>Junto (50553601000002A2026)</option>
                    </select>
                    @error('qr_serial_style') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div data-layout-group="sku">
                    <label class="block text-sm font-medium text-slate-700">SKU X</label>
                    <input type="number" name="sku_position_x" value="{{ old('sku_position_x', $formState['sku_layout']['x'] ?? 170) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('sku_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div data-layout-group="sku">
                    <label class="block text-sm font-medium text-slate-700">SKU Y</label>
                    <input type="number" name="sku_position_y" value="{{ old('sku_position_y', $formState['sku_layout']['y'] ?? 35) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('sku_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div data-layout-group="sku">
                    <label class="block text-sm font-medium text-slate-700">Tamaño letra SKU</label>
                    <input type="number" name="sku_font_size" value="{{ old('sku_font_size', $formState['sku_layout']['font_size'] ?? 44) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('sku_font_size') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>

                <div data-layout-group="sku">
                    <label class="block text-sm font-medium text-slate-700">Orientación SKU</label>
                    <select name="sku_orientation" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('sku_orientation', $formState['sku_layout']['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('sku_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">SN pequeño X</label>
                    <input type="number" name="sn_position_x" value="{{ old('sn_position_x', $formState['sn_layout']['x'] ?? 170) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('sn_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">SN pequeño Y</label>
                    <input type="number" name="sn_position_y" value="{{ old('sn_position_y', $formState['sn_layout']['y'] ?? 95) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('sn_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tamaño letra SN pequeño</label>
                    <input type="number" name="sn_font_size" value="{{ old('sn_font_size', $formState['sn_layout']['font_size'] ?? 22) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('sn_font_size') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Orientación SN pequeño</label>
                    <select name="sn_orientation" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                        @foreach(['N' => 'Normal', 'R' => 'Rotada 90°', 'I' => 'Invertida 180°', 'B' => 'Bottom-up 270°'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('sn_orientation', $formState['sn_layout']['orientation'] ?? 'N') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('sn_orientation') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
                <div id="sn-prefix-wrapper" @if(($formState['selected_label_type'] ?? 'serial') !== 'serial') style="display:none;" @endif>
                    <label class="block text-sm font-medium text-slate-700">Prefijo texto SN</label>
                    <input name="sn_prefix" value="{{ old('sn_prefix', $formState['sn_layout']['prefix'] ?? 'SN:') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('sn_prefix') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                </div>
            </div>

            <div id="qr-custom-fields-wrapper" class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                <h4 class="text-sm font-semibold text-slate-900">Bloques de QR personalizado</h4>
                <p class="mt-1 text-xs text-slate-500">Ejemplo EMEA: 103 | Serial | EMEA SKU. Ejemplo alterno: EMEA SKU | Serial | EMEA SKU.</p>
                <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-3">
                    @php
                        $qrCustomOptions = [
                            '' => 'Vacío',
                            'fixed_103' => 'Valor fijo 103',
                            'serial_full' => 'Serial completo',
                            'rating_qr_code' => 'QR rating',
                            'sku' => 'SKU',
                            'label_part_number' => 'Label part number',
                            'console_sku' => 'Console SKU',
                            'assembly_part_number' => 'Assembly part number',
                            'packaging_part_number' => 'Packaging part number',
                            'emea_sku' => 'EMEA SKU',
                            'anz_sku' => 'ANZ SKU',
                        ];
                        $customFields = old('qr_custom_fields', $formState['qr_layout']['custom_fields'] ?? []);
                    @endphp
                    @foreach([1,2,3] as $position)
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Bloque {{ $position }}</label>
                            <select name="qr_custom_field_{{ $position }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                                @foreach($qrCustomOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('qr_custom_field_'.$position, $customFields[$position - 1] ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('qr_custom_field_'.$position) <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 p-4">
        <div class="mb-4 border-b border-slate-100 pb-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Paso 4</p>
            <h2 class="text-lg font-semibold text-slate-900">Configura perfil y conexión de impresión</h2>
            <p class="mt-1 text-xs text-slate-500">Último paso: define el perfil, el método de conexión y ejecuta pruebas.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                    <option value="usb" @selected($formState['connection_type'] === 'usb')>USB</option>
                    <option value="network" @selected($formState['connection_type'] === 'network')>Red (IP)</option>
                </select>
            </div>

            <div id="printer-ip-wrapper">
                <label class="block text-sm font-medium text-slate-700">Printer IP</label>
                <input name="default_printer_ip" id="default_printer_ip" value="{{ old('default_printer_ip', $configuration->default_printer_ip ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
            </div>

            <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="font-semibold text-slate-900">Pruebas de impresora</h3>
                <p class="mt-1 text-xs text-slate-600">Para USB, valida conexión antes de guardar y ejecuta impresión de prueba. La prueba cambia según el tipo: Serial imprime QR + SKU + SN; Rating con QR en EMEA imprime solo SN + QR (sin SKU).</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button id="test-usb-connection" type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">Probar conexión USB</button>
                    <button id="test-print" type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-sm text-white">Impresión de prueba</button>
                </div>
                <input type="hidden" name="usb_connected" id="usb_connected" value="{{ old('usb_connected', '0') }}" />
                <div id="printer-test-status" class="mt-2 text-sm text-slate-700">Sin prueba de conexión.</div>
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="profile_is_active" value="1" {{ old('profile_is_active', $configuration->is_active ?? true) ? 'checked' : '' }}> Profile activo
                </label>
            </div>
        </div>
    </section>
</div>
<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
