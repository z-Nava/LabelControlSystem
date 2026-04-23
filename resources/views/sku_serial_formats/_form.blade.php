@csrf

@php
    $selectedStandard = old('serial_standard', $format->serial_standard ?? ($forcedStandard ?? 'UL'));
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">Estándar serial</label>
        @if(!empty($forcedStandard))
            <input type="hidden" name="serial_standard" value="{{ $forcedStandard }}">
            <input type="text" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2" value="{{ $forcedStandard }}" readonly>
        @else
            <select id="serialStandard" name="serial_standard" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                @foreach(['UL', 'EMEA', 'ANZ'] as $standard)
                    <option value="{{ $standard }}" @selected($selectedStandard === $standard)>{{ $standard }}</option>
                @endforeach
            </select>
        @endif
        @error('serial_standard') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">SKU</label>
        <select id="sku" name="sku" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            <option value="">Selecciona un SKU activo</option>
            @foreach(($activeSkus ?? collect()) as $skuOption)
                <option value="{{ $skuOption->sku }}" data-label-part-number="{{ $skuOption->label_part_number }}" data-serial-standard="{{ $skuOption->serial_standard }}"
                    @selected(old('sku', $format->sku ?? '') === $skuOption->sku)>
                    {{ $skuOption->sku }} · {{ $skuOption->serial_standard }}
                </option>
            @endforeach
        </select>
        <p id="skuLabelPartNumber" class="mt-1 text-xs text-emerald-600"></p>
        @error('sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Descripción del formato</label>
        <input name="description" value="{{ old('description', $format->description ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="160" />
        @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Esquema serial</label>
        <select id="serialScheme" name="serial_scheme" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            <option value="ul_standard" @selected(old('serial_scheme', $format->serial_scheme ?? 'ul_standard') === 'ul_standard')>UL Standard</option>
            <option value="emea_rating" @selected(old('serial_scheme', $format->serial_scheme ?? 'ul_standard') === 'emea_rating')>EMEA Rating</option>
            <option value="anz_standard" @selected(old('serial_scheme', $format->serial_scheme ?? 'ul_standard') === 'anz_standard')>ANZ Standard</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">QR payload format</label>
        <select name="qr_payload_format" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            @php($qrPayload = old('qr_payload_format', $format->qr_payload_format ?? 'serial_only'))
            <option value="serial_only" @selected($qrPayload === 'serial_only')>serial_only</option>
            <option value="emea_code_only" @selected($qrPayload === 'emea_code_only')>emea_code_only</option>
            <option value="customer_tool_code_serial" @selected($qrPayload === 'customer_tool_code_serial')>customer_tool_code_serial</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Date mode</label>
        <select name="date_mode" id="dateMode" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            @php($dateMode = old('date_mode', $format->date_mode ?? 'year_week'))
            <option value="year_week" @selected($dateMode === 'year_week')>year_week</option>
            <option value="month_year" @selected($dateMode === 'month_year')>month_year</option>
            <option value="none" @selected($dateMode === 'none')>none</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Año (dígitos)</label>
        <select name="year_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            <option value="2" @selected((int) old('year_digits', $format->year_digits ?? 2) === 2)>2</option>
            <option value="4" @selected((int) old('year_digits', $format->year_digits ?? 2) === 4)>4</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Semana (dígitos)</label>
        <select name="week_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            <option value="1" @selected((int) old('week_digits', $format->week_digits ?? 2) === 1)>1</option>
            <option value="2" @selected((int) old('week_digits', $format->week_digits ?? 2) === 2)>2</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Unit digits</label>
        <input type="number" min="1" max="10" name="unit_digits" value="{{ old('unit_digits', $format->unit_digits ?? $format->unit_length ?? 5) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Longitud total del serial (opcional)</label>
        <input type="number" min="4" max="80" name="serial_length" value="{{ old('serial_length', $format->serial_length ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
    </div>

    <div class="md:col-span-2 border rounded-xl p-4" id="ulFields">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Campos UL</p>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input name="ul_prefix" value="{{ old('ul_prefix', $format->ul_prefix ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="UL Prefix" />
            <input name="ul_serial_break" value="{{ old('ul_serial_break', $format->ul_serial_break ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="UL Break" />
            <input name="ul_plant_code" value="{{ old('ul_plant_code', $format->ul_plant_code ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="UL Plant" />
            <input type="number" name="ul_prefix_length" min="1" max="10" value="{{ old('ul_prefix_length', $format->ul_prefix_length ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="Prefix length" />
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 mt-3">
            <input type="checkbox" name="ul_use_plant_code" value="1" class="rounded border-slate-300" {{ old('ul_use_plant_code', $format->ul_use_plant_code ?? true) ? 'checked' : '' }}>
            Usar código de planta
        </label>
    </div>

    <div class="md:col-span-2 border rounded-xl p-4" id="emeaFields">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Campos EMEA</p>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input name="emea_prefix" value="{{ old('emea_prefix', $format->emea_prefix ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="EMEA Prefix" />
            <input name="emea_conformity_code" value="{{ old('emea_conformity_code', $format->emea_conformity_code ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="Conformity" />
            <input name="emea_plant_code" value="{{ old('emea_plant_code', $format->emea_plant_code ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="Plant (opcional)" />
            <input type="number" min="1" max="20" name="emea_prefix_digits" value="{{ old('emea_prefix_digits', $format->emea_prefix_digits ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="Prefix digits" />
            <select name="emea_prefix_source" class="rounded-xl border border-slate-300 px-3 py-2">
                @php($prefixSource = old('emea_prefix_source', $format->emea_prefix_source ?? 'fixed_value'))
                <option value="fixed_value" @selected($prefixSource === 'fixed_value')>fixed_value</option>
                <option value="sap_console_last_6" @selected($prefixSource === 'sap_console_last_6')>sap_console_last_6</option>
                <option value="packaging_code" @selected($prefixSource === 'packaging_code')>packaging_code</option>
            </select>
            <input type="number" min="1" max="10" name="emea_unit_digits" value="{{ old('emea_unit_digits', $format->emea_unit_digits ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="EMEA unit digits" />
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 mt-3">
            <input type="checkbox" name="emea_declaration_required" value="1" class="rounded border-slate-300" {{ old('emea_declaration_required', $format->emea_declaration_required ?? false) ? 'checked' : '' }}>
            Requiere declaración de conformidad
        </label>
    </div>

    <div class="md:col-span-2 border rounded-xl p-4" id="anzFields">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Campos ANZ</p>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input name="anz_product_prefix" value="{{ old('anz_product_prefix', $format->anz_product_prefix ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="Product prefix" />
            <input name="anz_tool_version" value="{{ old('anz_tool_version', $format->anz_tool_version ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="Tool version" />
            <input type="number" min="1" max="10" name="anz_unit_digits" value="{{ old('anz_unit_digits', $format->anz_unit_digits ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="ANZ unit digits" />
            <input name="anz_customer_tool_code" value="{{ old('anz_customer_tool_code', $format->anz_customer_tool_code ?? '') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="Customer tool code" />
            <input name="anz_qr_separator" value="{{ old('anz_qr_separator', $format->anz_qr_separator ?? ' | ') }}" class="rounded-xl border border-slate-300 px-3 py-2" placeholder="QR separator" />
            <select name="anz_serial_print_format" class="rounded-xl border border-slate-300 px-3 py-2">
                @php($anzPrintFormat = old('anz_serial_print_format', $format->anz_serial_print_format ?? 'spaces'))
                <option value="spaces" @selected($anzPrintFormat === 'spaces')>spaces</option>
                <option value="no_spaces" @selected($anzPrintFormat === 'no_spaces')>no_spaces</option>
                <option value="segmented" @selected($anzPrintFormat === 'segmented')>segmented</option>
            </select>
        </div>
        <div class="mt-3 flex flex-wrap gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="anz_include_customer_tool_code_in_qr" value="1" class="rounded border-slate-300" {{ old('anz_include_customer_tool_code_in_qr', $format->anz_include_customer_tool_code_in_qr ?? true) ? 'checked' : '' }}>
                Incluir customer tool code en QR
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="anz_tool_version_required" value="1" class="rounded border-slate-300" {{ old('anz_tool_version_required', $format->anz_tool_version_required ?? true) ? 'checked' : '' }}>
                Tool version requerida
            </label>
        </div>
    </div>

    <div class="md:col-span-2 flex flex-wrap gap-4">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="month_letter_enabled" value="1" class="rounded border-slate-300" {{ old('month_letter_enabled', $format->month_letter_enabled ?? false) ? 'checked' : '' }}>
            Habilitar letra de mes
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="include_year" value="1" class="rounded border-slate-300" {{ old('include_year', $format->include_year ?? true) ? 'checked' : '' }}>
            Incluir año
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="include_week" value="1" class="rounded border-slate-300" {{ old('include_week', $format->include_week ?? true) ? 'checked' : '' }}>
            Incluir semana
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', $format->is_active ?? true) ? 'checked' : '' }}>
            Activo
        </label>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Month letter map</label>
        <input name="month_letter_map" value="{{ old('month_letter_map', $format->month_letter_map ?? 'A,B,C,D,E,F,G,H,J,K,L,M') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="40" />
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Pattern legacy (opcional)</label>
        <input name="pattern" value="{{ old('pattern', $format->pattern ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="{PPP}{C}{PL}{YY}{WW}{SSSSS}" />
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const serialStandardSelect = document.getElementById('serialStandard');
    const serialScheme = document.getElementById('serialScheme');
    const ulFields = document.getElementById('ulFields');
    const emeaFields = document.getElementById('emeaFields');
    const anzFields = document.getElementById('anzFields');
    const skuSelect = document.getElementById('sku');
    const skuLabelPartNumber = document.getElementById('skuLabelPartNumber');

    const updateSkuInfo = () => {
        if (!skuSelect || !skuLabelPartNumber) return;
        const selectedOption = skuSelect.options[skuSelect.selectedIndex];
        skuLabelPartNumber.textContent = selectedOption?.dataset?.labelPartNumber
            ? `Label Part Number: ${selectedOption.dataset.labelPartNumber}`
            : '';
    };

    const applyStandard = (standard) => {
        ulFields?.classList.toggle('hidden', standard !== 'UL');
        emeaFields?.classList.toggle('hidden', standard !== 'EMEA');
        anzFields?.classList.toggle('hidden', standard !== 'ANZ');

        if (serialScheme) {
            serialScheme.value = standard === 'UL' ? 'ul_standard' : (standard === 'EMEA' ? 'emea_rating' : 'anz_standard');
        }
    };

    if (serialStandardSelect) {
        serialStandardSelect.addEventListener('change', () => applyStandard(serialStandardSelect.value));
        applyStandard(serialStandardSelect.value);
    } else {
        applyStandard(@json($selectedStandard));
    }

    skuSelect?.addEventListener('change', updateSkuInfo);
    updateSkuInfo();
});
</script>
