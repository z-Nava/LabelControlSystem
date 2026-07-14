@php
    $isEdit = $isEdit ?? false;
    $format = $format ?? null;
    $customerToolCode = old('anz_customer_tool_code', $format?->anz_customer_tool_code ?? '');
    $productPrefix = old('anz_product_prefix', $format?->anz_product_prefix ?? '');
    $toolVersion = old('anz_tool_version', $format?->anz_tool_version ?? 'A');
@endphp

@extends('layouts.app', ['title' => $isEdit ? 'Editar formato serial ANZ' : 'Agregar formato serial ANZ'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ $isEdit ? 'Editar formato serial - ANZ' : 'Agregar formato serial - ANZ' }}</h1>
        <a href="{{ route('sku_serial_formats.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    @include('sku_serial_formats._create_market_nav', ['forcedStandard' => 'ANZ'])

    <form class="mt-6 space-y-5" method="POST" action="{{ $isEdit ? route('sku_serial_formats.update', $format) : route('sku_serial_formats.store') }}">
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
        <input type="hidden" name="include_year" value="1">
        <input type="hidden" name="include_week" value="0">
        <input type="hidden" name="unit_digits" value="5">
        <input type="hidden" name="anz_unit_digits" value="5">
        <input type="hidden" name="serial_length" value="23">
        <input type="hidden" name="reset_scope" value="monthly">
        <input type="hidden" name="qr_payload_format" value="customer_tool_code_serial">
        <input type="hidden" name="anz_qr_separator" value=" | ">
        <input type="hidden" name="anz_include_customer_tool_code_in_qr" value="1">
        <input type="hidden" name="anz_serial_print_format" value="spaces">
        <input type="hidden" name="anz_tool_version_required" value="1">

        <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
            <p class="font-semibold">Regla ANZ segun documentacion</p>
            <p class="mt-1">Serial visible: <strong>PPPPPPPPP A XXXXX MYYYY</strong>. QR: <strong>Customer tool name | Serial visible</strong>.</p>
            <p class="mt-1">Los espacios son fijos: uno entre los segmentos del serial y uno a cada lado del pipe del QR.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2 rounded-xl border border-slate-200 p-4 bg-slate-50">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Datos base</p>
                <p class="mt-1 text-sm text-slate-600">Selecciona el SKU y captura solo los segmentos variables que asigna TAC o el cliente.</p>
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
                <label class="block text-sm font-medium text-slate-700">Descripcion interna</label>
                <input name="description" value="{{ old('description', $format?->description ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="160" placeholder="Ej. ANZ serial format" />
                @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2 rounded-xl border border-slate-200 p-4 bg-slate-50">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Segmentos ANZ</p>
                <p class="mt-1 text-sm text-slate-600">El consecutivo siempre es de 5 digitos y la fecha siempre usa letra de mes + anio de 4 digitos.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Product prefix (PPPPPPPPP)</label>
                <input id="anzPrefix" name="anz_product_prefix" value="{{ $productPrefix }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase" maxlength="9" pattern="[A-Za-z0-9]{9}" placeholder="AF02F2019" required />
                <p class="mt-1 text-xs text-slate-500">Exactamente 9 caracteres alfanumericos.</p>
                @error('anz_product_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Tool version (A-Z)</label>
                <input id="anzVersion" name="anz_tool_version" value="{{ $toolVersion }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase" maxlength="1" pattern="[A-Za-z]" placeholder="A" required />
                <p class="mt-1 text-xs text-slate-500">Una letra: A version original, B primer cambio, C segundo cambio, etc.</p>
                @error('anz_tool_version') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Customer tool name para QR</label>
                <input id="anzCustomerCode" name="anz_customer_tool_code" value="{{ $customerToolCode }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase" maxlength="40" placeholder="M12 FIR12G2" required />
                <p class="mt-1 text-xs text-slate-500">Primer segmento del QR. Se imprimira como: customer tool name, espacio, pipe, espacio, serial.</p>
                @error('anz_customer_tool_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2 grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 md:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Consecutivo</p>
                    <p class="mt-1 font-semibold">XXXXX = 5 digitos</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha</p>
                    <p class="mt-1 font-semibold">MYYYY</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">QR separator</p>
                    <p class="mt-1 font-semibold"> | </p>
                </div>
                <label class="flex items-center gap-2 font-semibold">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', $format?->is_active ?? true) ? 'checked' : '' }}>
                    Activo
                </label>
            </div>

            <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Vista previa</p>
                <p class="mt-2 text-sm text-emerald-900">Serial visible:</p>
                <p id="anzLiveSerialPreview" class="mt-1 break-words text-lg font-semibold text-emerald-950">AF02F2019 A 00001 A2026</p>
                <p id="anzLiveSerialBreakdown" class="mt-1 text-sm text-emerald-900">Prefix=AF02F2019 - Tool=A - Unit=00001 - Date=A2026</p>
                <p class="mt-3 text-sm text-emerald-900">QR final:</p>
                <p id="anzLiveQrPreview" class="mt-1 break-words text-lg font-semibold text-emerald-950">M12 FIR12G2 | AF02F2019 A 00001 A2026</p>
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
    const customerCode = document.getElementById('anzCustomerCode');
    const serialPreview = document.getElementById('anzLiveSerialPreview');
    const serialBreakdown = document.getElementById('anzLiveSerialBreakdown');
    const qrPreview = document.getElementById('anzLiveQrPreview');

    const monthCodes = ['A','B','C','D','E','F','G','H','J','K','L','M'];

    const updateSkuLabelPartNumber = () => {
        if (!skuSelect || !skuLabelPartNumber) return;
        const selectedOption = skuSelect.options[skuSelect.selectedIndex];
        const labelPartNumber = selectedOption?.dataset?.labelPartNumber;
        skuLabelPartNumber.textContent = labelPartNumber ? `Label Part Number: ${labelPartNumber}` : '';
    };

    const forceUppercase = (element) => {
        if (!element) return;
        element.value = element.value.toUpperCase();
    };

    const render = () => {
        if (!serialPreview || !qrPreview) return;
        forceUppercase(prefix);
        forceUppercase(version);
        forceUppercase(customerCode);

        const now = new Date();
        const monthYear = `${monthCodes[now.getMonth()] || 'A'}${now.getFullYear()}`;
        const unit = '00001';
        const prefixText = (prefix?.value || 'AF02F2019').toUpperCase();
        const versionText = (version?.value || 'A').toUpperCase();
        const serial = [prefixText, versionText, unit, monthYear].join(' ');
        const customerText = (customerCode?.value || 'M12 FIR12G2').trim().toUpperCase();

        serialPreview.textContent = serial;
        if (serialBreakdown) {
            serialBreakdown.textContent = `Prefix=${prefixText} - Tool=${versionText} - Unit=${unit} - Date=${monthYear}`;
        }

        qrPreview.textContent = `${customerText} | ${serial}`;
    };

    skuSelect?.addEventListener('change', updateSkuLabelPartNumber);
    [prefix, version, customerCode].forEach((el) => {
        el?.addEventListener('input', render);
        el?.addEventListener('change', render);
    });
    updateSkuLabelPartNumber();
    render();
});
</script>
@endsection
