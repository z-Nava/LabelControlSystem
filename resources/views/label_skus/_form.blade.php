@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">SKU</label>
        <input name="sku" value="{{ old('sku', $labelSku->sku ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="2552-20" required />
        @error('sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Label PN</label>
        <input name="label_part_number" value="{{ old('label_part_number', $labelSku->label_part_number ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="941050066" required />
        @error('label_part_number') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Descripción (opcional)</label>
        <input name="description" value="{{ old('description', $labelSku->description ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="Descripción interna" />
        @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1"
                   class="rounded border-slate-300"
                   {{ old('is_active', ($labelSku->is_active ?? true)) ? 'checked' : '' }}>
            Activo
        </label>
        @error('is_active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
</div>
