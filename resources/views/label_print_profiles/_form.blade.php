@csrf
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div><label class="block text-sm font-medium text-slate-700">SKU</label><select id="label-print-profile-sku" name="label_sku_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>@foreach($labelSkus as $sku)<option value="{{ $sku->id }}" data-sku="{{ $sku->sku }}" @selected((string) old('label_sku_id', $profile->label_sku_id ?? '') === (string) $sku->id)>{{ $sku->sku }} · {{ $sku->label_part_number }}</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium text-slate-700">Tipo (opcional)</label><select name="label_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"><option value="">General</option>@foreach(['serial','rating','shipping'] as $type)<option value="{{ $type }}" @selected(old('label_type', $profile->label_type ?? '') === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium text-slate-700">Template</label><select id="label-print-profile-template" name="label_template_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"><option value="" data-zpl-base64="">Sin template</option>@foreach($templates as $template)<option value="{{ $template->id }}" data-zpl-base64="{{ base64_encode($template->zpl ?? '') }}" @selected((string) old('label_template_id', $profile->label_template_id ?? '') === (string) $template->id)>{{ $template->name }} ({{ $template->label_type }})</option>@endforeach</select></div>
    <div class="md:col-span-3"><label class="block text-sm font-medium text-slate-700">Nombre</label><input name="name" value="{{ old('name', $profile->name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required /></div>
    <div><label class="block text-sm font-medium text-slate-700">Printer name</label><input id="label-print-profile-printer-name" name="default_printer_name" value="{{ old('default_printer_name', $profile->default_printer_name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" /></div>
    <div><label class="block text-sm font-medium text-slate-700">Printer IP</label><input name="default_printer_ip" value="{{ old('default_printer_ip', $profile->default_printer_ip ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" /><p class="mt-1 text-xs text-slate-500">Opcional: solo para impresoras de red. Por USB no se requiere.</p></div>
    <div><label class="block text-sm font-medium text-slate-700">DPI</label><select name="dpi" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">@foreach([203,300] as $dpi)<option value="{{ $dpi }}" @selected((int) old('dpi', $profile->dpi ?? 203) === $dpi)>{{ $dpi }}</option>@endforeach</select></div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Darkness</label>
        <select name="darkness" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            <option value="">Por defecto de impresora</option>
            @foreach([10, 12, 15, 18, 20, 25] as $darkness)
                <option value="{{ $darkness }}" @selected((string) old('darkness', $profile->darkness ?? '') === (string) $darkness)>
                    {{ $darkness }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">203 dpi: direct thermal 10–15, thermal transfer 15–25 (^MD).</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Speed</label>
        <select name="speed" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            <option value="">Por defecto de impresora</option>
            @foreach([2, 3, 4, 5, 6, 8] as $speed)
                <option value="{{ $speed }}" @selected((string) old('speed', $profile->speed ?? '') === (string) $speed)>
                    {{ $speed }} ips
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Desktop recomendado 3–4 ips, industrial 4–6 ips (^PR).</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Media type</label>
        <select name="media_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            <option value="">Por defecto de impresora</option>
            <option value="direct_thermal" @selected(old('media_type', $profile->media_type ?? '') === 'direct_thermal')>Direct thermal (sin ribbon)</option>
            <option value="thermal_transfer" @selected(old('media_type', $profile->media_type ?? '') === 'thermal_transfer')>Thermal transfer (con ribbon)</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Media tracking</label>
        <select name="media_tracking" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            <option value="">Por defecto de impresora</option>
            <option value="gap" @selected(old('media_tracking', $profile->media_tracking ?? '') === 'gap')>Gap</option>
            <option value="black_mark" @selected(old('media_tracking', $profile->media_tracking ?? '') === 'black_mark')>Black mark</option>
            <option value="continuous" @selected(old('media_tracking', $profile->media_tracking ?? '') === 'continuous')>Continuous</option>
            <option value="default" @selected(old('media_tracking', $profile->media_tracking ?? '') === 'default')>Default</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">La mayoría de etiquetas usan <span class="font-medium">gap</span> (^MN).</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Print mode</label>
        <select name="print_mode" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            <option value="">Por defecto de impresora</option>
            <option value="tear_off" @selected(old('print_mode', $profile->print_mode ?? '') === 'tear_off')>Tear off</option>
            <option value="peel_off" @selected(old('print_mode', $profile->print_mode ?? '') === 'peel_off')>Peel off</option>
            <option value="cutter" @selected(old('print_mode', $profile->print_mode ?? '') === 'cutter')>Cutter</option>
            <option value="rewind" @selected(old('print_mode', $profile->print_mode ?? '') === 'rewind')>Rewind</option>
            <option value="applicator" @selected(old('print_mode', $profile->print_mode ?? '') === 'applicator')>Applicator</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">Sin accesorios, usar <span class="font-medium">tear_off</span> (^MM).</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Offset X</label>
        <input type="number" name="offset_x" value="{{ old('offset_x', $profile->offset_x ?? 0) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        <p class="mt-1 text-xs text-slate-500">Unidad en dots. 203 dpi ≈ 8 dots/mm. Ej.: 2 mm ≈ 16 dots (^LS).</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Offset Y</label>
        <input type="number" name="offset_y" value="{{ old('offset_y', $profile->offset_y ?? 0) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        <p class="mt-1 text-xs text-slate-500">Unidad en dots. Ej.: 5 mm ≈ 40 dots (^LT).</p>
    </div>
    <div class="md:col-span-3"><label class="block text-sm font-medium text-slate-700">Settings JSON</label><textarea name="settings" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">{{ old('settings', isset($profile) && $profile->settings ? json_encode($profile->settings, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '') }}</textarea></div>
    <div class="md:col-span-3"><label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $profile->is_active ?? true) ? 'checked' : '' }}> Activo</label></div>
</div>
