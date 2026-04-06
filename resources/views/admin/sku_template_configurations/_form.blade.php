@csrf
@if ($errors->any())
    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 md:col-span-2">
        <p class="font-semibold">No se pudo guardar la configuración.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="grid grid-cols-1 gap-4 md:grid-cols-2"
    id="sku-template-configuration-form"
    data-default-serial="L36BH2606007A7"
    data-default-sku="2978-OCUT">
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
                <option value="{{ $type }}" @selected($formState['selected_label_type'] === $type)>{{ ucfirst($type) }}</option>
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

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-2" data-layout-section="serial">
        <div class="mb-3">
            <h3 class="font-semibold text-slate-900">Configuración etiqueta Serial con QR</h3>
            <p class="mt-1 text-xs text-slate-500">El QR codifica el serial completo; además se muestra el SKU grande y el SN en texto pequeño.</p>
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
                <label class="block text-sm font-medium text-slate-700">SKU X</label>
                <input type="number" name="sku_position_x" value="{{ old('sku_position_x', $formState['sku_layout']['x'] ?? 170) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('sku_position_x') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">SKU Y</label>
                <input type="number" name="sku_position_y" value="{{ old('sku_position_y', $formState['sku_layout']['y'] ?? 35) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('sku_position_y') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tamaño letra SKU</label>
                <input type="number" name="sku_font_size" value="{{ old('sku_font_size', $formState['sku_layout']['font_size'] ?? 44) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                @error('sku_font_size') <div class="mt-1 text-sm text-red-600">{{ $message }}</div> @enderror
            </div>

            <div>
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
            <div>
                <label class="block text-sm font-medium text-slate-700">Prefijo texto SN</label>
                <input name="sn_prefix" value="{{ old('sn_prefix', $formState['sn_layout']['prefix'] ?? 'SN:') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
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
            <option value="usb" @selected($formState['connection_type'] === 'usb')>USB</option>
            <option value="network" @selected($formState['connection_type'] === 'network')>Red (IP)</option>
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
