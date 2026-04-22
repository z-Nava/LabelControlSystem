@php
    $showWeekControls = $showWeekControls ?? true;
    $lockYearToFour = $lockYearToFour ?? false;
@endphp

<div>
    <label class="block text-sm font-medium text-slate-700">SKU</label>
    <select id="sku" name="sku" required
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
        <option value="">Selecciona un SKU activo ({{ $forcedStandard }})</option>
        @foreach(($activeSkus ?? collect()) as $skuOption)
            <option value="{{ $skuOption->sku }}" data-label-part-number="{{ $skuOption->label_part_number }}" @selected(old('sku') === $skuOption->sku)>
                {{ $skuOption->sku }} · {{ $skuOption->serial_standard }}
            </option>
        @endforeach
    </select>
    <p id="skuLabelPartNumber" class="mt-1 text-xs text-emerald-600"></p>
    @error('sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Separador entre segmentos</label>
    <select name="separator" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
        @php($selectedSeparator = old('separator', ''))
        <option value="" @selected($selectedSeparator === '')>Sin separador</option>
        <option value="__SPACE__" @selected(in_array($selectedSeparator, [' ', '__SPACE__'], true))>Espacio</option>
        <option value="-" @selected($selectedSeparator === '-')>- (guion)</option>
        <option value="_" @selected($selectedSeparator === '_')>_ (guion bajo)</option>
        <option value="|" @selected($selectedSeparator === '|')>| (pipe)</option>
    </select>
    @error('separator') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Año (dígitos)</label>
    @if($lockYearToFour)
        <input type="hidden" name="year_digits" value="4">
        <input type="text" value="4 (YYYY)" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700" readonly>
    @else
        <select name="year_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
            <option value="2" @selected((int) old('year_digits', 2) === 2)>2 (YY)</option>
            <option value="4" @selected((int) old('year_digits', 2) === 4)>4 (YYYY)</option>
        </select>
    @endif
    @error('year_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Semana (dígitos)</label>
    @if($showWeekControls)
        <select name="week_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
            <option value="1" @selected((int) old('week_digits', 2) === 1)>1 (W)</option>
            <option value="2" @selected((int) old('week_digits', 2) === 2)>2 (WW)</option>
        </select>
    @else
        <input type="hidden" name="week_digits" value="2">
        <input type="text" value="No aplica para ANZ" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700" readonly>
    @endif
    @error('week_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Unit length</label>
    <input type="number" min="1" max="10" name="unit_length" value="{{ old('unit_length', 5) }}"
           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required />
    @error('unit_length') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Pattern legacy (opcional)</label>
    <input name="pattern" value="{{ old('pattern', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
           placeholder="{PPP}{C}{PL}{YY}{WW}{SSSSS}" />
    @error('pattern') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3">
    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        @if($lockYearToFour)
            <input type="hidden" name="include_year" value="1">
            <input type="checkbox" class="rounded border-slate-300" checked disabled>
        @else
            <input type="checkbox" name="include_year" value="1" class="rounded border-slate-300" {{ old('include_year', true) ? 'checked' : '' }}>
        @endif
        Incluir año
    </label>

    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        @if($showWeekControls)
            <input type="checkbox" name="include_week" value="1" class="rounded border-slate-300" {{ old('include_week', true) ? 'checked' : '' }}>
        @else
            <input type="hidden" name="include_week" value="0">
            <input type="checkbox" class="rounded border-slate-300" checked disabled>
        @endif
        Incluir semana
    </label>

    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', true) ? 'checked' : '' }}>
        Activo
    </label>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const skuSelect = document.getElementById('sku');
    const skuLabelPartNumber = document.getElementById('skuLabelPartNumber');

    if (!skuSelect || !skuLabelPartNumber) {
        return;
    }

    const updateSkuLabelPartNumber = () => {
        const selectedOption = skuSelect.options[skuSelect.selectedIndex];
        const labelPartNumber = selectedOption?.dataset?.labelPartNumber;
        skuLabelPartNumber.textContent = labelPartNumber ? `Label Part Number: ${labelPartNumber}` : '';
    };

    skuSelect.addEventListener('change', updateSkuLabelPartNumber);
    updateSkuLabelPartNumber();
});
</script>
