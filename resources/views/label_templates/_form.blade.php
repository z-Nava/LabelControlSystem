@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">Nombre</label>
        <input name="name" value="{{ old('name', $template->name ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Tipo</label>
        <select name="label_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            @foreach(['serial','rating','shipping'] as $type)
                <option value="{{ $type }}" @selected(old('label_type', $template->label_type ?? '') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">SKU (opcional)</label>
        <select id="label-template-sku" name="label_sku_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            <option value="" data-sku="" data-serial-preview="">Global</option>
            @foreach($labelSkus as $sku)
                <option
                    value="{{ $sku->id }}"
                    data-sku="{{ $sku->sku }}"
                    data-serial-preview="{{ ($serialFormatPreviews ?? [])[$sku->sku] ?? '' }}"
                    @selected((string) old('label_sku_id', $template->label_sku_id ?? '') === (string) $sku->id)
                >{{ $sku->sku }} · {{ $sku->label_part_number }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid grid-cols-3 gap-2">
        <div><label class="block text-sm font-medium text-slate-700">DPI</label><select name="dpi" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">@foreach([203,300] as $dpi)<option value="{{ $dpi }}" @selected((int) old('dpi', $template->dpi ?? 203) === $dpi)>{{ $dpi }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-slate-700">Ancho mm</label><input name="width_mm" value="{{ old('width_mm', $template->width_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" /></div>
        <div><label class="block text-sm font-medium text-slate-700">Alto mm</label><input name="height_mm" value="{{ old('height_mm', $template->height_mm ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" /></div>
    </div>
    <div class="md:col-span-2"><label class="block text-sm font-medium text-slate-700">ZPL</label><textarea id="label-template-zpl" name="zpl" rows="10" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>{{ old('zpl', $template->zpl ?? '') }}</textarea></div>
    <div class="md:col-span-2"><label class="block text-sm font-medium text-slate-700">Meta JSON (opcional)</label><textarea name="meta" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">{{ old('meta', isset($template) && $template->meta ? json_encode($template->meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '') }}</textarea></div>
    <div class="md:col-span-2"><label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}> Activo</label></div>
</div>
