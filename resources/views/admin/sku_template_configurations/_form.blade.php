@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">SKU</label>
        <select name="label_sku_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            @foreach($labelSkus as $sku)
                <option value="{{ $sku->id }}" @selected((string) old('label_sku_id', $configuration->label_sku_id ?? '') === (string) $sku->id)>
                    {{ $sku->sku }} · {{ $sku->label_part_number }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Tipo de etiqueta</label>
        <select name="label_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            @foreach(['serial', 'rating', 'shipping'] as $type)
                <option value="{{ $type }}" @selected(old('label_type', $configuration->label_type ?? $configuration->template?->label_type ?? 'serial') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2 border-t pt-3 mt-1">
        <h2 class="font-semibold text-slate-900">Template ZPL</h2>
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
    <div>
        <label class="block text-sm font-medium text-slate-700">Ancho mm</label>
        <input name="template_width_mm" value="{{ old('template_width_mm', $configuration->template->width_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Alto mm</label>
        <input name="template_height_mm" value="{{ old('template_height_mm', $configuration->template->height_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">ZPL</label>
        <textarea name="template_zpl" rows="8" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>{{ old('template_zpl', $configuration->template->zpl ?? '') }}</textarea>
    </div>

    <div class="md:col-span-2 border-t pt-3 mt-1">
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
        <input name="default_printer_name" value="{{ old('default_printer_name', $configuration->default_printer_name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Printer IP</label>
        <input name="default_printer_ip" value="{{ old('default_printer_ip', $configuration->default_printer_ip ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="template_is_active" value="1" {{ old('template_is_active', $configuration->template->is_active ?? true) ? 'checked' : '' }}> Template activo
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 ml-6">
            <input type="checkbox" name="profile_is_active" value="1" {{ old('profile_is_active', $configuration->is_active ?? true) ? 'checked' : '' }}> Profile activo
        </label>
    </div>
</div>
