@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">SKU</label>
        <select id="sku" name="sku" required
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            <option value="">Selecciona un SKU activo</option>
            @foreach(($activeSkus ?? collect()) as $skuOption)
                @php($selectedSku = old('sku', $format->sku ?? ''))
                <option value="{{ $skuOption->sku }}"
                        data-label-part-number="{{ $skuOption->label_part_number }}"
                        @selected($selectedSku === $skuOption->sku)>
                    {{ $skuOption->sku }}
                </option>
            @endforeach
        </select>
        <p id="skuLabelPartNumber" class="mt-1 text-xs text-emerald-600"></p>
        @error('sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Separador entre segmentos</label>
        <input name="separator" value="{{ old('separator', $format->separator ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               maxlength="5" placeholder="Vacío para serial continuo" />
        @error('separator') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
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
        <label class="block text-sm font-medium text-slate-700">Año (dígitos)</label>
        <select name="year_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
            <option value="2" {{ (int) old('year_digits', $format->year_digits ?? 2) === 2 ? 'selected' : '' }}>2 (YY)</option>
            <option value="4" {{ (int) old('year_digits', $format->year_digits ?? 2) === 4 ? 'selected' : '' }}>4 (YYYY)</option>
        </select>
        @error('year_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Semana (dígitos)</label>
        <select name="week_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
            <option value="1" {{ (int) old('week_digits', $format->week_digits ?? 2) === 1 ? 'selected' : '' }}>1 (W)</option>
            <option value="2" {{ (int) old('week_digits', $format->week_digits ?? 2) === 2 ? 'selected' : '' }}>2 (WW)</option>
        </select>
        @error('week_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Unit length</label>
        <input type="number" min="1" max="10" name="unit_length" value="{{ old('unit_length', $format->unit_length ?? 5) }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required />
        @error('unit_length') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Pattern legacy (opcional)</label>
        <input name="pattern" value="{{ old('pattern', $format->pattern ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="{PPP}{C}{PL}{YY}{WW}{SSSSS}" />
        <p class="mt-1 text-xs text-slate-500">Si lo dejas vacío, se construye por segmentos (prefijos + año/semana + consecutivo).</p>
        @error('pattern') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="include_year" value="1" class="rounded border-slate-300"
                   {{ old('include_year', ($format->include_year ?? true)) ? 'checked' : '' }}>
            Incluir año
        </label>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="include_week" value="1" class="rounded border-slate-300"
                   {{ old('include_week', ($format->include_week ?? true)) ? 'checked' : '' }}>
            Incluir semana
        </label>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
                   {{ old('is_active', ($format->is_active ?? true)) ? 'checked' : '' }}>
            Activo
        </label>
    </div>

    <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
        Ejemplo esperado: <strong>G67</strong> + <strong>D</strong> + <strong>H</strong> + <strong>25</strong> + <strong>34</strong> + <strong>00001</strong> = <strong>G67DH253400001</strong>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const skuSelect = document.getElementById('sku');
        const skuLabelPartNumber = document.getElementById('skuLabelPartNumber');

        if (!skuSelect || !skuLabelPartNumber) {
            return;
        }

        const updateLabelPartNumber = () => {
            const selectedOption = skuSelect.options[skuSelect.selectedIndex];
            const labelPartNumber = selectedOption?.dataset?.labelPartNumber;

            skuLabelPartNumber.textContent = labelPartNumber
                ? `Label Part Number: ${labelPartNumber}`
                : '';
        };

        skuSelect.addEventListener('change', updateLabelPartNumber);
        updateLabelPartNumber();
    });
</script>
