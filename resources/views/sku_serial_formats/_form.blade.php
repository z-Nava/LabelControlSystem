@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">SKU</label>
        <input name="sku" value="{{ old('sku', $format->sku ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="2552-20" required />
        @error('sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Pattern</label>
        <input name="pattern" value="{{ old('pattern', $format->pattern ?? '{PPP}{C}{PL}{YY}{WW}{SSSSS}') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="{PPP}{C}{PL}{YY}{WW}{SSSSS}" required />
        @error('pattern') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Prefix (opcional)</label>
        <input name="prefix" value="{{ old('prefix', $format->prefix ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               maxlength="10" placeholder="PPP" />
        @error('prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Serial break (opcional)</label>
        <input name="serial_break" value="{{ old('serial_break', $format->serial_break ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               maxlength="10" placeholder="C" />
        @error('serial_break') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Plant code (opcional)</label>
        <input name="plant_code" value="{{ old('plant_code', $format->plant_code ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               maxlength="10" placeholder="PL" />
        @error('plant_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Unit length</label>
        <input type="number" min="1" max="10" name="unit_length" value="{{ old('unit_length', $format->unit_length ?? 5) }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required />
        @error('unit_length') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
                   {{ old('is_active', ($format->is_active ?? true)) ? 'checked' : '' }}>
            Activo
        </label>
        @error('is_active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
</div>
