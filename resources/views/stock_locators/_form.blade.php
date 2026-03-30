@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">STOCK_LOCATOR (Línea)</label>
        <input name="stock_locator" value="{{ old('stock_locator', $stockLocator->stock_locator ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="MXC014" required />
        @error('stock_locator') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">SUBINVENTORY (Local)</label>
        <input name="subinventory" value="{{ old('subinventory', $stockLocator->subinventory ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="WIP" required />
        @error('subinventory') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="active" value="1"
                   class="rounded border-slate-300"
                   {{ old('active', ($stockLocator->active ?? true)) ? 'checked' : '' }}>
            Activo
        </label>
        @error('active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
</div>
