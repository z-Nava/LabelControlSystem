@php
    $isEdit = $isEdit ?? false;
    $format = $format ?? null;
    $anzUnitDigits = old('anz_unit_digits', $format?->anz_unit_digits ?? $format?->effectiveUnitDigits() ?? 5);
    $resetScope = old('reset_scope', $format?->reset_scope ?? 'monthly');
    $printFormat = old('anz_serial_print_format', $format?->anz_serial_print_format ?? 'spaces');
@endphp

@extends('layouts.app', ['title' => $isEdit ? 'Editar formato serial ANZ' : 'Agregar formato serial ANZ'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ $isEdit ? 'Editar formato serial - ANZ' : 'Agregar formato serial - ANZ' }}</h1>
        <a href="{{ route('sku_serial_formats.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    @include('sku_serial_formats._create_market_nav', ['forcedStandard' => 'ANZ'])

    <form class="mt-6 space-y-4" method="POST" action="{{ $isEdit ? route('sku_serial_formats.update', $format) : route('sku_serial_formats.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="serial_standard" value="ANZ">
        <input type="hidden" name="serial_scheme" value="anz_standard">
        <input type="hidden" name="date_mode" value="month_year">
        <input type="hidden" name="month_letter_enabled" value="1">
        <input type="hidden" name="month_letter_map" value="A,B,C,D,E,F,G,H,J,K,L,M">
        <input type="hidden" name="year_digits" value="4">
        <input type="hidden" name="week_digits" value="2">
        <input type="hidden" name="include_week" value="0">
        <input type="hidden" name="qr_payload_format" value="customer_tool_code_serial">
        <input id="unitDigits" type="hidden" name="unit_digits" value="{{ old('unit_digits', $anzUnitDigits) }}">

        <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
            <p class="font-semibold">Que estas configurando en ANZ?</p>
            <p class="mt-1">Serial visible: <strong>PPPPPPPPP A XXXXX MYYYY</strong>. QR: <strong>CCCC | Serial</strong> cuando esta habilitado.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-800">Guia rapida (3 pasos)</p>
            <ol class="mt-2 space-y-1 text-sm text-slate-700 list-decimal list-inside">
                <li><strong>Paso 1:</strong> Selecciona SKU y reglas base del consecutivo.</li>
                <li><strong>Paso 2:</strong> Configura prefijo de producto, version y QR.</li>
                <li><strong>Paso 3:</strong> Revisa serial y payload QR en tiempo real.</li>
            </ol>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2 rounded-xl border border-slate-200 p-4 bg-white">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Paso 1 - Datos generales</p>
                <p class="mt-1 text-sm text-slate-600">Define SKU, longitud de unidad y reinicio del consecutivo.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">SKU</label>
                <select id="sku" name="sku" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    <option value="">Selecciona un SKU activo (ANZ)</option>
                    @foreach(($activeSkus ?? collect()) as $skuOption)
                        <option value="{{ $skuOption->sku }}" data-label-part-number="{{ $skuOption->label_part_number }}" @selected(old('sku', $format?->sku ?? '') === $skuOption->sku)>
                            {{ $skuOption->sku }} - {{ $skuOption->serial_standard }}
                        </option>
                    @endforeach
                </select>
                <p id="skuLabelPartNumber" class="mt-1 text-xs text-emerald-600"></p>
                @error('sku') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Descripcion del formato</label>
                <input name="description" value="{{ old('description', $format?->description ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="160" placeholder="Ej. ANZ serial format" />
                @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Ano (digitos)</label>
                <input type="text" value="4 (YYYY)" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700" readonly>
                @error('year_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Semana (digitos)</label>
                <input type="text" value="No aplica" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700" readonly>
                @error('week_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">ANZ unit digits</label>
                <input id="anzUnitDigits" type="number" min="1" max="10" name="anz_unit_digits" value="{{ $anzUnitDigits }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                <p class="mt-1 text-xs text-slate-500">Cuantos digitos tendra el consecutivo (ej. 5 = 00001).</p>
                @error('anz_unit_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                @error('unit_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Longitud total del serial (opcional)</label>
                <input type="number" min="4" max="80" name="serial_length" value="{{ old('serial_length', $format?->serial_length ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                <p class="mt-1 text-xs text-slate-500">Solo de referencia/validacion: largo final esperado.</p>
                @error('serial_length') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Reset scope</label>
                <select name="reset_scope" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <option value="weekly" @selected($resetScope === 'weekly')>weekly</option>
                    <option value="monthly" @selected($resetScope === 'monthly')>monthly</option>
                    <option value="yearly" @selected($resetScope === 'yearly')>yearly</option>
                    <option value="never" @selected($resetScope === 'never')>never</option>
                </select>
                @error('reset_scope') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Serial print format</label>
                <select id="anzPrintFormat" name="anz_serial_print_format" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <option value="spaces" @selected($printFormat === 'spaces')>Con espacios</option>
                    <option value="no_spaces" @selected($printFormat === 'no_spaces')>Sin espacios</option>
                    <option value="segmented" @selected($printFormat === 'segmented')>Segmentado</option>
                </select>
                @error('anz_serial_print_format') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="include_year" value="0">
                    <input type="checkbox" name="include_year" value="1" class="rounded border-slate-300" {{ old('include_year', $format?->include_year ?? true) ? 'checked' : '' }}>
                    Incluir ano
                </label>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" class="rounded border-slate-300" disabled>
                    Incluir semana
                </label>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', $format?->is_active ?? true) ? 'checked' : '' }}>
                    Activo
                </label>
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Paso 2 - Estructura ANZ - PPPPPPPPP A XXXXX MYYYY</p>
                    <p class="mt-1 text-sm text-slate-600">Puedes activar o desactivar el codigo de herramienta de cliente dentro del QR.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">ANZ Product Prefix</label>
                    <input id="anzPrefix" name="anz_product_prefix" value="{{ old('anz_product_prefix', $format?->anz_product_prefix ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="20" placeholder="AF02F2019" />
                    @error('anz_product_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tool version (A-Z)</label>
                    <input id="anzVersion" name="anz_tool_version" value="{{ old('anz_tool_version', $format?->anz_tool_version ?? 'A') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="2" placeholder="A" />
                    @error('anz_tool_version') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Customer tool code (CCCC)</label>
                    <input id="anzCustomerCode" name="anz_customer_tool_code" value="{{ old('anz_customer_tool_code', $format?->anz_customer_tool_code ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="M12" />
                    @error('anz_customer_tool_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">QR separator</label>
                    <input id="anzQrSeparator" name="anz_qr_separator" value="{{ old('anz_qr_separator', $format?->anz_qr_separator ?? ' | ') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="5" placeholder=" | " />
                    @error('anz_qr_separator') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 pt-8">
                    <input type="hidden" name="anz_include_customer_tool_code_in_qr" value="0">
                    <input id="anzIncludeCustomerCode" type="checkbox" name="anz_include_customer_tool_code_in_qr" value="1" class="rounded border-slate-300" {{ old('anz_include_customer_tool_code_in_qr', $format?->anz_include_customer_tool_code_in_qr ?? true) ? 'checked' : '' }}>
                    Incluir CCCC en QR
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 pt-8">
                    <input type="hidden" name="anz_tool_version_required" value="0">
                    <input type="checkbox" name="anz_tool_version_required" value="1" class="rounded border-slate-300" {{ old('anz_tool_version_required', $format?->anz_tool_version_required ?? true) ? 'checked' : '' }}>
                    Tool version requerida
                </label>
            </div>

            <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Paso 3 - Validacion visual (ejemplo en vivo)</p>
                <p class="mt-2 text-sm text-emerald-900">Serial visible:</p>
                <p id="anzLiveSerialPreview" class="mt-1 text-lg font-semibold text-emerald-950">AF02F2019 A 00001 A2026</p>
                <p id="anzLiveSerialBreakdown" class="mt-1 text-sm text-emerald-900">Prefix=AF02F2019 - Tool=A - Unit=00001 - Date=A2026</p>
                <p class="mt-3 text-sm text-emerald-900">QR final:</p>
                <p id="anzLiveQrPreview" class="mt-1 text-lg font-semibold text-emerald-950">M12 | AF02F2019 A 00001 A2026</p>
            </div>
        </div>

        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">{{ $isEdit ? 'Actualizar' : 'Guardar' }}</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const skuSelect = document.getElementById('sku');
    const skuLabelPartNumber = document.getElementById('skuLabelPartNumber');
    const prefix = document.getElementById('anzPrefix');
    const version = document.getElementById('anzVersion');
    const unitDigits = document.getElementById('unitDigits');
    const anzUnitDigits = document.getElementById('anzUnitDigits');
    const customerCode = document.getElementById('anzCustomerCode');
    const qrSeparator = document.getElementById('anzQrSeparator');
    const includeCode = document.getElementById('anzIncludeCustomerCode');
    const printFormat = document.getElementById('anzPrintFormat');
    const serialPreview = document.getElementById('anzLiveSerialPreview');
    const serialBreakdown = document.getElementById('anzLiveSerialBreakdown');
    const qrPreview = document.getElementById('anzLiveQrPreview');

    const monthCodes = ['A','B','C','D','E','F','G','H','J','K','L','M'];
    const pad = (value, len) => String(value).padStart(len, '0');

    const updateSkuLabelPartNumber = () => {
        if (!skuSelect || !skuLabelPartNumber) return;
        const selectedOption = skuSelect.options[skuSelect.selectedIndex];
        const labelPartNumber = selectedOption?.dataset?.labelPartNumber;
        skuLabelPartNumber.textContent = labelPartNumber ? `Label Part Number: ${labelPartNumber}` : '';
    };

    const syncUnitDigits = () => {
        if (unitDigits && anzUnitDigits) {
            unitDigits.value = anzUnitDigits.value || '5';
        }
    };

    const render = () => {
        if (!serialPreview || !qrPreview) return;
        syncUnitDigits();
        const now = new Date();
        const monthYear = `${monthCodes[now.getMonth()] || 'A'}${now.getFullYear()}`;
        const unit = pad(1, Number(anzUnitDigits?.value || 5));
        const prefixText = (prefix?.value || 'AF02F2019').toUpperCase();
        const versionText = (version?.value || 'A').toUpperCase();
        const parts = [prefixText, versionText, unit, monthYear];
        const mode = printFormat?.value || 'spaces';
        const serial = mode === 'no_spaces' ? parts.join('') : parts.join(' ');

        serialPreview.textContent = serial;
        if (serialBreakdown) {
            serialBreakdown.textContent = `Prefix=${prefixText} - Tool=${versionText} - Unit=${unit} - Date=${monthYear}`;
        }

        const customerText = (customerCode?.value || '').trim().toUpperCase();
        if (includeCode?.checked && customerText !== '') {
            qrPreview.textContent = `${customerText}${qrSeparator?.value || ' | '}${serial}`;
            return;
        }

        qrPreview.textContent = serial;
    };

    skuSelect?.addEventListener('change', updateSkuLabelPartNumber);
    [prefix, version, anzUnitDigits, customerCode, qrSeparator, includeCode, printFormat].forEach((el) => {
        el?.addEventListener('input', render);
        el?.addEventListener('change', render);
    });
    updateSkuLabelPartNumber();
    render();
});
</script>
@endsection
