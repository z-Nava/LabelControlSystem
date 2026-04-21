@csrf

@php($currentStandard = old('serial_standard', $labelSku->serial_standard ?? $serialStandard ?? 'UL'))

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">Estándar serial</label>
        <select name="serial_standard"
                id="serial_standard"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                required>
            @foreach(['UL', 'EMEA', 'ANZ'] as $standard)
                <option value="{{ $standard }}" @selected($currentStandard === $standard)>
                    {{ $standard }}
                </option>
            @endforeach
        </select>
        @error('serial_standard') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
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

    <div>
        <label class="block text-sm font-medium text-slate-700">Console SKU</label>
        <input name="console_sku" value="{{ old('console_sku', $labelSku->console_sku ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="3052-EMEA" />
        @error('console_sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Assembly Part Number</label>
        <input name="assembly_part_number" value="{{ old('assembly_part_number', $labelSku->assembly_part_number ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="103920001" />
        @error('assembly_part_number') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Packaging Part Number(s)</label>
        <input name="packaging_part_number" value="{{ old('packaging_part_number', $labelSku->packaging_part_number ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="018920001, 055920001" />
        <p class="mt-1 text-xs text-slate-500">Puedes capturar más de un Packaging PN separado por coma, espacio, punto y coma o |.</p>
        @error('packaging_part_number') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">EMEA SKU</label>
        <input name="emea_sku" value="{{ old('emea_sku', $labelSku->emea_sku ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="M12FHI140" />
        @error('emea_sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">ANZ SKU</label>
        <input name="anz_sku" value="{{ old('anz_sku', $labelSku->anz_sku ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="M12FHI140-ANZ" />
        @error('anz_sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" id="market-help">
        @if(in_array($currentStandard, ['EMEA', 'ANZ'], true))
            Para {{ $currentStandard }}, Console SKU, Assembly Part Number y Packaging Code son obligatorios.
        @else
            Para UL, los campos adicionales son opcionales y quedan listos para futura configuración.
        @endif
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

<script>
    (() => {
        const serialStandardSelect = document.getElementById('serial_standard');
        const marketHelp = document.getElementById('market-help');

        if (!serialStandardSelect || !marketHelp) {
            return;
        }

        const updateHelp = () => {
            const value = serialStandardSelect.value;
            if (value === 'EMEA' || value === 'ANZ') {
                marketHelp.textContent = `Para ${value}, Console SKU, Assembly Part Number y Packaging Code son obligatorios.`;
                return;
            }

            marketHelp.textContent = 'Para UL, los campos adicionales son opcionales y quedan listos para futura configuración.';
        };

        serialStandardSelect.addEventListener('change', updateHelp);
        updateHelp();
    })();
</script>
