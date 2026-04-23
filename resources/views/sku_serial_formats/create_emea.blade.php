@extends('layouts.app', ['title' => 'Agregar formato serial EMEA'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Agregar formato serial · EMEA</h1>
        <a href="{{ route('sku_serial_formats.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    @include('sku_serial_formats._create_market_nav', ['forcedStandard' => 'EMEA'])

    <form class="mt-6 space-y-4" method="POST" action="{{ route('sku_serial_formats.store') }}">
        @csrf
        <input type="hidden" name="serial_standard" value="EMEA">
        <input type="hidden" name="serial_scheme" value="emea_rating">
        <input type="hidden" name="date_mode" value="month_year">
        <input type="hidden" name="month_letter_enabled" value="1">
        <input type="hidden" name="month_letter_map" value="A,B,C,D,E,F,G,H,J,K,L,M">
        <input type="hidden" name="include_week" value="0">
        <input type="hidden" name="qr_payload_format" value="emea_code_only">

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <p class="font-semibold">¿Qué estás configurando en EMEA?</p>
            <p class="mt-1">Estás armando el formato: <strong>PPPPPP CC SSSSSS MJJJJ</strong> (mes con letra y año de 4 dígitos).</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @include('sku_serial_formats._create_form_helpers', ['forcedStandard' => 'EMEA', 'activeSkus' => $activeSkus, 'showWeekControls' => false, 'lockYearToFour' => true, 'defaultUnitDigits' => 6])

            <div>
                <label class="block text-sm font-medium text-slate-700">Prefix source</label>
                <select name="emea_prefix_source" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <option value="fixed_value" @selected(old('emea_prefix_source', 'fixed_value') === 'fixed_value')>fixed_value</option>
                    <option value="sap_console_last_6" @selected(old('emea_prefix_source') === 'sap_console_last_6')>sap_console_last_6</option>
                    <option value="packaging_code" @selected(old('emea_prefix_source') === 'packaging_code')>packaging_code</option>
                </select>
                @error('emea_prefix_source') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Estructura EMEA · PPPPPP CC SSSSSS MJJJJ</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">EMEA Prefix (PPPPPP)</label>
                    <input id="emeaPrefix" name="emea_prefix" value="{{ old('emea_prefix', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="20" placeholder="505554" />
                    @error('emea_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Conformity code (CC)</label>
                    <input id="emeaConformity" name="emea_conformity_code" value="{{ old('emea_conformity_code', '01') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="01" />
                    @error('emea_conformity_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Plant / line code (opcional)</label>
                    <input name="emea_plant_code" value="{{ old('emea_plant_code', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="(opcional)" />
                    @error('emea_plant_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Prefix digits</label>
                    <input type="number" min="1" max="20" name="emea_prefix_digits" value="{{ old('emea_prefix_digits', 6) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('emea_prefix_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">EMEA unit digits</label>
                    <input type="number" min="1" max="10" name="emea_unit_digits" value="{{ old('emea_unit_digits', 6) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('emea_unit_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 pt-8">
                    <input type="checkbox" name="emea_declaration_required" value="1" class="rounded border-slate-300" {{ old('emea_declaration_required', true) ? 'checked' : '' }}>
                    Requiere declaración de conformidad
                </label>
            </div>

            <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Ejemplo en vivo</p>
                <p class="mt-2 text-sm text-emerald-900">Así se está construyendo tu serial EMEA:</p>
                <p id="emeaLivePreview" class="mt-2 text-lg font-semibold text-emerald-950">505554 01 000001 A2026</p>
            </div>
        </div>

        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">Guardar</button>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const prefix = document.getElementById('emeaPrefix');
    const conformity = document.getElementById('emeaConformity');
    const unitDigits = document.getElementById('unitDigits');
    const preview = document.getElementById('emeaLivePreview');

    const monthCodes = ['A','B','C','D','E','F','G','H','J','K','L','M'];
    const pad = (value, len) => String(value).padStart(len, '0');

    const render = () => {
        if (!preview) return;
        const now = new Date();
        const month = monthCodes[now.getMonth()] || 'A';
        const year = String(now.getFullYear());
        const units = pad(1, Number(unitDigits?.value || 6));

        preview.textContent = `${(prefix?.value || '505554').toUpperCase()} ${(conformity?.value || '01').toUpperCase()} ${units} ${month}${year}`;
    };

    [prefix, conformity, unitDigits].forEach((el) => el?.addEventListener('input', render));
    render();
});
</script>
@endsection
