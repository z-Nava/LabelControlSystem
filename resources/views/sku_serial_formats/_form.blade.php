@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">Estándar serial</label>
        <select name="serial_standard" id="serialStandard"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                required>
            @foreach(['UL', 'EMEA'] as $standard)
                <option value="{{ $standard }}" @selected(old('serial_standard', $format->serial_standard ?? 'UL') === $standard)>
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
        <input name="separator" value="{{ old('separator', $format->separator ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               maxlength="5" placeholder="Vacío para serial continuo" />
        @error('separator') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700" id="prefixLabel">Prefix (opcional)</label>
        <input id="prefixInput" name="prefix" value="{{ old('prefix', $format->prefix ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               maxlength="10" placeholder="PPP" />
        <p id="prefixHelp" class="mt-1 text-xs text-slate-500 hidden"></p>
        @error('prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700" id="serialBreakLabel">Serial break (opcional)</label>
        <input id="serialBreakInput" name="serial_break" value="{{ old('serial_break', $format->serial_break ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               maxlength="10" placeholder="C" />
        <p id="serialBreakHelp" class="mt-1 text-xs text-slate-500 hidden"></p>
        @error('serial_break') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700" id="plantCodeLabel">Plant code (opcional)</label>
        <input id="plantCodeInput" name="plant_code" value="{{ old('plant_code', $format->plant_code ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               maxlength="10" placeholder="PL" />
        <p id="plantCodeHelp" class="mt-1 text-xs text-slate-500 hidden"></p>
        @error('plant_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700" id="yearDigitsLabel">Año (dígitos)</label>
        <select id="yearDigits" name="year_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
            <option value="2" {{ (int) old('year_digits', $format->year_digits ?? 2) === 2 ? 'selected' : '' }}>2 (YY)</option>
            <option value="4" {{ (int) old('year_digits', $format->year_digits ?? 2) === 4 ? 'selected' : '' }}>4 (YYYY)</option>
        </select>
        <p id="yearDigitsHelp" class="mt-1 text-xs text-slate-500 hidden"></p>
        @error('year_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div id="weekDigitsGroup">
        <label class="block text-sm font-medium text-slate-700" id="weekDigitsLabel">Semana (dígitos)</label>
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
        <p id="unitLengthHelp" class="mt-1 text-xs text-slate-500 hidden"></p>
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
        <label class="inline-flex items-center gap-2 text-sm text-slate-700" id="includeYearWrapper">
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

    <div id="ulExampleBox" class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
        Ejemplo esperado: <strong>G67</strong> + <strong>D</strong> + <strong>H</strong> + <strong>25</strong> + <strong>34</strong> + <strong>00001</strong> = <strong>G67DH253400001</strong>
    </div>

    <div id="emeaExampleBox" class="md:col-span-2 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-slate-700 hidden">
        <p class="font-semibold">Formato EMEA sugerido (según hoja de producción):</p>
        <p class="mt-1"><strong>5055</strong> + <strong>54</strong> + <strong>01</strong> + <strong>SSSSSS</strong> + <strong>M</strong> + <strong>JJJJ</strong></p>
        <ul class="mt-2 list-disc pl-5 text-xs">
            <li><strong>SSSSSS:</strong> consecutivo de 6 dígitos (000001-999999).</li>
            <li><strong>M:</strong> letra del mes (A=Enero, B=Febrero, ... , L=Diciembre).</li>
            <li><strong>JJJJ:</strong> año en 4 dígitos.</li>
        </ul>
        <p class="mt-2 text-xs text-slate-600">Nota: actualmente el serial automático usa el segmento de semana/calendario del sistema. Este bloque es una guía para capturar el formato EMEA en esta pantalla.</p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const serialStandardSelect = document.getElementById('serialStandard');
        const serialSchemeSelect = document.getElementById('serialScheme');
        const skuSelect = document.getElementById('sku');
        const skuLabelPartNumber = document.getElementById('skuLabelPartNumber');
        const prefixLabel = document.getElementById('prefixLabel');
        const prefixInput = document.getElementById('prefixInput');
        const prefixHelp = document.getElementById('prefixHelp');
        const serialBreakLabel = document.getElementById('serialBreakLabel');
        const serialBreakInput = document.getElementById('serialBreakInput');
        const serialBreakHelp = document.getElementById('serialBreakHelp');
        const plantCodeLabel = document.getElementById('plantCodeLabel');
        const plantCodeInput = document.getElementById('plantCodeInput');
        const plantCodeHelp = document.getElementById('plantCodeHelp');
        const yearDigits = document.getElementById('yearDigits');
        const yearDigitsHelp = document.getElementById('yearDigitsHelp');
        const weekDigitsGroup = document.getElementById('weekDigitsGroup');
        const includeWeekWrapper = document.getElementById('includeWeekWrapper');
        const includeWeekCheckbox = document.getElementById('includeWeekCheckbox');
        const includeYearCheckbox = document.getElementById('includeYearCheckbox');
        const unitLength = document.getElementById('unitLength');
        const unitLengthHelp = document.getElementById('unitLengthHelp');
        const ulExampleBox = document.getElementById('ulExampleBox');
        const emeaExampleBox = document.getElementById('emeaExampleBox');

        if (!serialStandardSelect || !serialSchemeSelect || !skuSelect || !skuLabelPartNumber) {
            return;
        }

        const setHelp = (element, text) => {
            if (!element) {
                return;
            }

            element.textContent = text ?? '';
            element.classList.toggle('hidden', !text);
        };

        const applyStandardUi = (standard) => {
            const isEmea = standard === 'EMEA';

            serialSchemeSelect.value = isEmea ? 'emea_rating' : 'ul_standard';

            if (isEmea) {
                prefixLabel.textContent = 'Código base EMEA (opcional)';
                prefixInput.placeholder = '5055';
                setHelp(prefixHelp, 'Sección fija sugerida en el estándar EMEA (ej. 5055).');

                serialBreakLabel.textContent = 'Código conformidad (opcional)';
                serialBreakInput.placeholder = '54';
                setHelp(serialBreakHelp, 'Ejemplo común en EMEA: 54.');

                plantCodeLabel.textContent = 'Código de planta/línea (opcional)';
                plantCodeInput.placeholder = '01';
                setHelp(plantCodeHelp, 'Ejemplo común en EMEA: 01.');

                if (yearDigits) {
                    yearDigits.value = '4';
                }
                setHelp(yearDigitsHelp, 'EMEA usa año de 4 dígitos (JJJJ).');

                if (includeYearCheckbox) {
                    includeYearCheckbox.checked = true;
                }

                if (includeWeekCheckbox) {
                    includeWeekCheckbox.checked = false;
                }

                if (weekDigitsGroup) {
                    weekDigitsGroup.classList.add('hidden');
                }
                if (includeWeekWrapper) {
                    includeWeekWrapper.classList.add('hidden');
                }

                if (unitLength && (unitLength.value === '' || unitLength.value === '5')) {
                    unitLength.value = '6';
                }
                setHelp(unitLengthHelp, 'Para EMEA normalmente se usa consecutivo de 6 dígitos.');

                ulExampleBox?.classList.add('hidden');
                emeaExampleBox?.classList.remove('hidden');
            } else {
                prefixLabel.textContent = 'Prefix (opcional)';
                prefixInput.placeholder = 'PPP';
                setHelp(prefixHelp, '');

                serialBreakLabel.textContent = 'Serial break (opcional)';
                serialBreakInput.placeholder = 'C';
                setHelp(serialBreakHelp, '');

                plantCodeLabel.textContent = 'Plant code (opcional)';
                plantCodeInput.placeholder = 'PL';
                setHelp(plantCodeHelp, '');
                setHelp(yearDigitsHelp, '');

                if (weekDigitsGroup) {
                    weekDigitsGroup.classList.remove('hidden');
                }
                if (includeWeekWrapper) {
                    includeWeekWrapper.classList.remove('hidden');
                }

                setHelp(unitLengthHelp, '');

                ulExampleBox?.classList.remove('hidden');
                emeaExampleBox?.classList.add('hidden');
            }
        };

        const updateLabelPartNumber = () => {
            const selectedOption = skuSelect.options[skuSelect.selectedIndex];
            const labelPartNumber = selectedOption?.dataset?.labelPartNumber;
            const skuSerialStandard = selectedOption?.dataset?.serialStandard;

            skuLabelPartNumber.textContent = labelPartNumber
                ? `Label Part Number: ${labelPartNumber}`
                : '';

            if (skuSerialStandard === 'UL' || skuSerialStandard === 'EMEA') {
                serialStandardSelect.value = skuSerialStandard;
            }

            applyStandardUi(serialStandardSelect.value);
        };

        skuSelect.addEventListener('change', updateLabelPartNumber);
        serialStandardSelect.addEventListener('change', function () {
            applyStandardUi(serialStandardSelect.value);
        });

        updateLabelPartNumber();
    });
</script>
