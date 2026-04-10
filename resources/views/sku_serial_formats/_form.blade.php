@csrf

@php
    $lockedStandard = $forcedStandard ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">Estándar serial</label>
        @if($lockedStandard)
            <input type="hidden" name="serial_standard" value="{{ $lockedStandard }}">
        @endif
        <select name="serial_standard" id="serialStandard"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                required @disabled($lockedStandard !== null)>
            @foreach(['UL', 'EMEA'] as $standard)
                <option value="{{ $standard }}" @selected(old('serial_standard', $format->serial_standard ?? $lockedStandard ?? 'UL') === $standard)>
                    {{ $standard }}
                </option>
            @endforeach
        </select>
        @error('serial_standard') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Esquema serial</label>
        <select name="serial_scheme" id="serialScheme"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                required>
            <option value="ul_standard" @selected(old('serial_scheme', $format->serial_scheme ?? 'ul_standard') === 'ul_standard')>UL Standard</option>
            <option value="emea_rating" @selected(old('serial_scheme', $format->serial_scheme ?? 'ul_standard') === 'emea_rating')>EMEA Rating</option>
        </select>
        @error('serial_scheme') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">SKU</label>
        <select id="sku" name="sku" required
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            <option value="">Selecciona un SKU activo</option>
            @foreach(($activeSkus ?? collect()) as $skuOption)
                @php($selectedSku = old('sku', $format->sku ?? ''))
                <option value="{{ $skuOption->sku }}"
                        data-serial-standard="{{ $skuOption->serial_standard }}"
                        data-label-part-number="{{ $skuOption->label_part_number }}"
                        @selected($selectedSku === $skuOption->sku)>
                    {{ $skuOption->sku }} · {{ $skuOption->serial_standard }}
                </option>
            @endforeach
        </select>
        <p id="skuLabelPartNumber" class="mt-1 text-xs text-emerald-600"></p>
        @error('sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Separador entre segmentos</label>
        <select name="separator"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            @php($selectedSeparator = old('separator', $format->separator ?? ''))
            <option value="" @selected($selectedSeparator === '')>Sin separador</option>
            <option value=" " @selected($selectedSeparator === ' ')>Espacio</option>
            <option value="-" @selected($selectedSeparator === '-')>- (guion)</option>
            <option value="_" @selected($selectedSeparator === '_')>_ (guion bajo)</option>
            <option value="|" @selected($selectedSeparator === '|')>| (pipe)</option>
        </select>
        @error('separator') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div id="ulFields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Estructura UL</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">UL Prefix (PPP)</label>
            <input name="ul_prefix" value="{{ old('ul_prefix', $format->ul_prefix ?? '') }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                   maxlength="10" placeholder="628" />
            @error('ul_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">UL Serial break (C)</label>
            <input name="ul_serial_break" value="{{ old('ul_serial_break', $format->ul_serial_break ?? '') }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                   maxlength="10" placeholder="D" />
            @error('ul_serial_break') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">UL Plant code (PL)</label>
            <input name="ul_plant_code" value="{{ old('ul_plant_code', $format->ul_plant_code ?? '') }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                   maxlength="10" placeholder="6" />
            @error('ul_plant_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
    </div>

    <div id="emeaFields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 hidden">
        <div class="md:col-span-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Estructura EMEA</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">EMEA Base code</label>
            <input name="emea_prefix" value="{{ old('emea_prefix', $format->emea_prefix ?? '') }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                   maxlength="10" placeholder="5055 54" />
            @error('emea_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">EMEA Conformity code</label>
            <input name="emea_conformity_code" value="{{ old('emea_conformity_code', $format->emea_conformity_code ?? '') }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                   maxlength="10" placeholder="01" />
            @error('emea_conformity_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">EMEA Plant / line code (opcional)</label>
            <input name="emea_plant_code" value="{{ old('emea_plant_code', $format->emea_plant_code ?? '') }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                   maxlength="10" placeholder="(vacío)" />
            @error('emea_plant_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Año (dígitos)</label>
        <select id="yearDigits" name="year_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
            <option value="2" {{ (int) old('year_digits', $format->year_digits ?? 2) === 2 ? 'selected' : '' }}>2 (YY)</option>
            <option value="4" {{ (int) old('year_digits', $format->year_digits ?? 2) === 4 ? 'selected' : '' }}>4 (YYYY)</option>
        </select>
        @error('year_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div id="weekDigitsGroup">
        <label class="block text-sm font-medium text-slate-700">Semana (dígitos)</label>
        <select id="weekDigits" name="week_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
            <option value="1" {{ (int) old('week_digits', $format->week_digits ?? 2) === 1 ? 'selected' : '' }}>1 (W)</option>
            <option value="2" {{ (int) old('week_digits', $format->week_digits ?? 2) === 2 ? 'selected' : '' }}>2 (WW)</option>
        </select>
        @error('week_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Unit length</label>
        <input id="unitLength" type="number" min="1" max="10" name="unit_length" value="{{ old('unit_length', $format->unit_length ?? 5) }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required />
        @error('unit_length') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Pattern legacy (opcional)</label>
        <input name="pattern" value="{{ old('pattern', $format->pattern ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="{PPP}{C}{PL}{YY}{WW}{SSSSS}" />
        @error('pattern') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="include_year" id="includeYearCheckbox" value="1" class="rounded border-slate-300"
                   {{ old('include_year', ($format->include_year ?? true)) ? 'checked' : '' }}>
            Incluir año
        </label>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700" id="includeWeekWrapper">
            <input type="checkbox" name="include_week" id="includeWeekCheckbox" value="1" class="rounded border-slate-300"
                   {{ old('include_week', ($format->include_week ?? true)) ? 'checked' : '' }}>
            Incluir semana
        </label>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
                   {{ old('is_active', ($format->is_active ?? true)) ? 'checked' : '' }}>
            Activo
        </label>
    </div>

    <div id="expectedExampleBox" class="md:col-span-2 rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-sm text-slate-700">
        <span class="font-medium">Ejemplo esperado:</span>
        <span id="expectedExampleText" class="font-semibold text-slate-800">G67 + D + H + 25 + 34 + 00001 = G67DH253400001</span>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const serialStandardSelect = document.getElementById('serialStandard');
    const serialSchemeSelect = document.getElementById('serialScheme');
    const skuSelect = document.getElementById('sku');
    const skuLabelPartNumber = document.getElementById('skuLabelPartNumber');
    const ulFields = document.getElementById('ulFields');
    const emeaFields = document.getElementById('emeaFields');
    const weekDigitsGroup = document.getElementById('weekDigitsGroup');
    const includeWeekWrapper = document.getElementById('includeWeekWrapper');
    const includeWeekCheckbox = document.getElementById('includeWeekCheckbox');
    const includeYearCheckbox = document.getElementById('includeYearCheckbox');
    const yearDigits = document.getElementById('yearDigits');
    const unitLength = document.getElementById('unitLength');
    const expectedExampleText = document.getElementById('expectedExampleText');

    if (!serialStandardSelect || !serialSchemeSelect || !skuSelect) {
        return;
    }

    const applyStandardUi = (standard) => {
        const isEmea = standard === 'EMEA';

        serialSchemeSelect.value = isEmea ? 'emea_rating' : 'ul_standard';
        ulFields?.classList.toggle('hidden', isEmea);
        emeaFields?.classList.toggle('hidden', !isEmea);

        if (isEmea) {
            if (expectedExampleText) {
                expectedExampleText.textContent = '5055 54 + 01 + 000001 + A2026 = 5055 54 01 000001 A2026';
            }
            if (yearDigits) yearDigits.value = '4';
            if (includeYearCheckbox) includeYearCheckbox.checked = true;
            if (includeWeekCheckbox) includeWeekCheckbox.checked = false;
            weekDigitsGroup?.classList.add('hidden');
            includeWeekWrapper?.classList.add('hidden');
            if (unitLength && (unitLength.value === '' || unitLength.value === '5')) {
                unitLength.value = '6';
            }
        } else {
            if (expectedExampleText) {
                expectedExampleText.textContent = 'G67 + D + H + 25 + 34 + 00001 = G67DH253400001';
            }
            weekDigitsGroup?.classList.remove('hidden');
            includeWeekWrapper?.classList.remove('hidden');
        }
    };

    const updateFromSku = () => {
        const selectedOption = skuSelect.options[skuSelect.selectedIndex];
        const labelPartNumber = selectedOption?.dataset?.labelPartNumber;
        const skuSerialStandard = selectedOption?.dataset?.serialStandard;

        skuLabelPartNumber.textContent = labelPartNumber ? `Label Part Number: ${labelPartNumber}` : '';

        if (skuSerialStandard === 'UL' || skuSerialStandard === 'EMEA') {
            serialStandardSelect.value = skuSerialStandard;
        }

        applyStandardUi(serialStandardSelect.value);
    };

    skuSelect.addEventListener('change', updateFromSku);
    serialStandardSelect.addEventListener('change', () => applyStandardUi(serialStandardSelect.value));

    updateFromSku();
});
</script>
