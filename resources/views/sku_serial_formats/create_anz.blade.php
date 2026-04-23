@extends('layouts.app', ['title' => 'Agregar formato serial ANZ'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Agregar formato serial · ANZ</h1>
        <a href="{{ route('sku_serial_formats.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    @include('sku_serial_formats._create_market_nav', ['forcedStandard' => 'ANZ'])

    <form class="mt-6 space-y-4" method="POST" action="{{ route('sku_serial_formats.store') }}">
        @csrf
        <input type="hidden" name="serial_standard" value="ANZ">
        <input type="hidden" name="serial_scheme" value="anz_standard">
        <input type="hidden" name="date_mode" value="month_year">
        <input type="hidden" name="month_letter_enabled" value="1">
        <input type="hidden" name="month_letter_map" value="A,B,C,D,E,F,G,H,J,K,L,M">
        <input type="hidden" name="include_week" value="0">
        <input type="hidden" name="qr_payload_format" value="customer_tool_code_serial">

        <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
            <p class="font-semibold">¿Qué estás configurando en ANZ?</p>
            <p class="mt-1">Serial visible: <strong>PPPPPPPPP A XXXXX MJJJJ</strong>. QR: <strong>CCCC | Serial</strong> (si está habilitado).</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @include('sku_serial_formats._create_form_helpers', ['forcedStandard' => 'ANZ', 'activeSkus' => $activeSkus, 'showWeekControls' => false, 'lockYearToFour' => true, 'defaultUnitDigits' => 5])

            <div>
                <label class="block text-sm font-medium text-slate-700">Serial print format</label>
                <select name="anz_serial_print_format" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <option value="spaces" @selected(old('anz_serial_print_format', 'spaces') === 'spaces')>Con espacios</option>
                    <option value="no_spaces" @selected(old('anz_serial_print_format') === 'no_spaces')>Sin espacios</option>
                    <option value="segmented" @selected(old('anz_serial_print_format') === 'segmented')>Segmentado</option>
                </select>
                @error('anz_serial_print_format') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Estructura ANZ · PPPPPPPPP A XXXXX MJJJJ</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">ANZ Product Prefix</label>
                    <input id="anzPrefix" name="anz_product_prefix" value="{{ old('anz_product_prefix', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="20" placeholder="AF02F2019" />
                    @error('anz_product_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tool version (A-Z)</label>
                    <input id="anzVersion" name="anz_tool_version" value="{{ old('anz_tool_version', 'A') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="2" placeholder="A" />
                    @error('anz_tool_version') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">ANZ Unit digits</label>
                    <input type="number" min="1" max="10" name="anz_unit_digits" value="{{ old('anz_unit_digits', 5) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('anz_unit_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Customer tool code (CCCC)</label>
                    <input id="anzCustomerCode" name="anz_customer_tool_code" value="{{ old('anz_customer_tool_code', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="M12" />
                    @error('anz_customer_tool_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">QR separator</label>
                    <input id="anzQrSeparator" name="anz_qr_separator" value="{{ old('anz_qr_separator', ' | ') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="5" placeholder=" | " />
                    @error('anz_qr_separator') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 pt-8">
                    <input id="anzIncludeCustomerCode" type="checkbox" name="anz_include_customer_tool_code_in_qr" value="1" class="rounded border-slate-300" {{ old('anz_include_customer_tool_code_in_qr', true) ? 'checked' : '' }}>
                    Incluir CCCC en QR
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="anz_tool_version_required" value="1" class="rounded border-slate-300" {{ old('anz_tool_version_required', true) ? 'checked' : '' }}>
                    Tool version requerida
                </label>
            </div>

            <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Ejemplo en vivo</p>
                <p class="mt-2 text-sm text-emerald-900">Serial visible:</p>
                <p id="anzLiveSerialPreview" class="mt-1 text-lg font-semibold text-emerald-950">AF02F2019 A 00001 A2026</p>
                <p class="mt-3 text-sm text-emerald-900">QR final:</p>
                <p id="anzLiveQrPreview" class="mt-1 text-lg font-semibold text-emerald-950">M12 | AF02F2019 A 00001 A2026</p>
            </div>
        </div>

        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">Guardar</button>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const prefix = document.getElementById('anzPrefix');
    const version = document.getElementById('anzVersion');
    const unitDigits = document.getElementById('unitDigits');
    const customerCode = document.getElementById('anzCustomerCode');
    const qrSeparator = document.getElementById('anzQrSeparator');
    const includeCode = document.getElementById('anzIncludeCustomerCode');
    const printFormat = document.querySelector('select[name=\"anz_serial_print_format\"]');
    const serialPreview = document.getElementById('anzLiveSerialPreview');
    const qrPreview = document.getElementById('anzLiveQrPreview');

    const monthCodes = ['A','B','C','D','E','F','G','H','J','K','L','M'];
    const pad = (value, len) => String(value).padStart(len, '0');

    const render = () => {
        if (!serialPreview || !qrPreview) return;
        const now = new Date();
        const monthYear = `${monthCodes[now.getMonth()] || 'A'}${now.getFullYear()}`;
        const unit = pad(1, Number(unitDigits?.value || 5));
        const parts = [
            (prefix?.value || 'AF02F2019').toUpperCase(),
            (version?.value || 'A').toUpperCase(),
            unit,
            monthYear,
        ];
        const mode = printFormat?.value || 'spaces';
        const serial = mode === 'no_spaces' ? parts.join('') : parts.join(' ');
        serialPreview.textContent = serial;

        if (includeCode?.checked && (customerCode?.value || '').trim() !== '') {
            qrPreview.textContent = `${customerCode.value.toUpperCase()}${qrSeparator?.value || ' | '}${serial}`;
            return;
        }

        qrPreview.textContent = serial;
    };

    [prefix, version, unitDigits, customerCode, qrSeparator, includeCode, printFormat].forEach((el) => el?.addEventListener('input', render));
    includeCode?.addEventListener('change', render);
    render();
});
</script>
@endsection
