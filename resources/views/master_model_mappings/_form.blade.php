@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">NP (Número de parte)</label>
        <input name="np" value="{{ old('np', $mapping->np ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="103365001" required />
        @error('np') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">SKU / Modelo</label>
        <input name="sku" value="{{ old('sku', $mapping->sku ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="2505" required />
        @error('sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="active" value="1"
                   class="rounded border-slate-300"
                   {{ old('active', ($mapping->active ?? true)) ? 'checked' : '' }}>
            Activo
        </label>
        @error('active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
</div>
