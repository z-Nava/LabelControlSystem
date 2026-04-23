@php
    $showWeekControls = $showWeekControls ?? true;
    $lockYearToFour = $lockYearToFour ?? false;
    $defaultUnitDigits = $defaultUnitDigits ?? 5;
    $format = $format ?? null;
@endphp

<div>
    <label class="block text-sm font-medium text-slate-700">SKU</label>
    <select id="sku" name="sku" required
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
        <option value="">Selecciona un SKU activo ({{ $forcedStandard }})</option>
        @foreach(($activeSkus ?? collect()) as $skuOption)
            <option value="{{ $skuOption->sku }}" data-label-part-number="{{ $skuOption->label_part_number }}" @selected(old('sku', $format->sku ?? '') === $skuOption->sku)>
                {{ $skuOption->sku }} · {{ $skuOption->serial_standard }}
            </option>
        @endforeach
    </select>
    <p id="skuLabelPartNumber" class="mt-1 text-xs text-emerald-600"></p>
    @error('sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Descripción del formato</label>
    <input name="description" value="{{ old('description', $format->description ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="160"
           placeholder="Ej. {{ $forcedStandard }} serial format" />
    @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Año (dígitos)</label>
    @if($lockYearToFour)
        <input type="hidden" name="year_digits" value="4">
        <input type="text" value="4 (YYYY)" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700" readonly>
    @else
        <select id="yearDigits" name="year_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            <option value="2" @selected((int) old('year_digits', $format->year_digits ?? 2) === 2)>2 (YY)</option>
            <option value="4" @selected((int) old('year_digits', $format->year_digits ?? 2) === 4)>4 (YYYY)</option>
        </select>
    @endif
    @error('year_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Semana (dígitos)</label>
    @if($showWeekControls)
        <select id="weekDigits" name="week_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            <option value="1" @selected((int) old('week_digits', $format->week_digits ?? 2) === 1)>1 (W)</option>
            <option value="2" @selected((int) old('week_digits', $format->week_digits ?? 2) === 2)>2 (WW)</option>
        </select>
    @else
        <input type="hidden" name="week_digits" value="2">
        <input type="text" value="No aplica" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700" readonly>
    @endif
    @error('week_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Unit digits</label>
    <input id="unitDigits" type="number" min="1" max="10" name="unit_digits" value="{{ old('unit_digits', $format?->effectiveUnitDigits() ?? $defaultUnitDigits) }}"
           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
    <p class="mt-1 text-xs text-slate-500">Cuántos dígitos tendrá el consecutivo (ej. 5 = 00001).</p>
    @error('unit_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Longitud total del serial (opcional)</label>
    <input type="number" min="4" max="80" name="serial_length" value="{{ old('serial_length', $format->serial_length ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    <p class="mt-1 text-xs text-slate-500">Solo de referencia/validación: largo final esperado.</p>
    @error('serial_length') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700">Reset scope</label>
    @php($defaultResetScope = $format?->reset_scope ?? ($forcedStandard === 'UL' ? 'weekly' : 'monthly'))
    <select name="reset_scope" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
        <option value="weekly" @selected(old('reset_scope', $defaultResetScope) === 'weekly')>weekly</option>
        <option value="monthly" @selected(old('reset_scope', $defaultResetScope) === 'monthly')>monthly</option>
        <option value="yearly" @selected(old('reset_scope', $defaultResetScope) === 'yearly')>yearly</option>
        <option value="never" @selected(old('reset_scope', $defaultResetScope) === 'never')>never</option>
    </select>
</div>

<div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3">
    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="include_year" value="1" class="rounded border-slate-300" {{ old('include_year', $format->include_year ?? true) ? 'checked' : '' }}>
        Incluir año
    </label>

    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        @if($showWeekControls)
            <input type="checkbox" name="include_week" value="1" class="rounded border-slate-300" {{ old('include_week', $format->include_week ?? true) ? 'checked' : '' }}>
        @else
            <input type="hidden" name="include_week" value="0">
            <input type="checkbox" class="rounded border-slate-300" disabled>
        @endif
        Incluir semana
    </label>

    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', $format->is_active ?? true) ? 'checked' : '' }}>
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
