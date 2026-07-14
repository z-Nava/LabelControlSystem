@php
    $isEdit = $isEdit ?? false;
    $format = $format ?? null;
    $emeaUnitDigits = 6;
@endphp

@extends('layouts.app', ['title' => $isEdit ? 'Editar formato serial EMEA' : 'Agregar formato serial EMEA'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ $isEdit ? 'Editar formato serial - EMEA' : 'Agregar formato serial - EMEA' }}</h1>
        <a href="{{ route('sku_serial_formats.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    @include('sku_serial_formats._create_market_nav', ['forcedStandard' => 'EMEA'])

    <form class="mt-6 space-y-4" method="POST" action="{{ $isEdit ? route('sku_serial_formats.update', $format) : route('sku_serial_formats.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="serial_standard" value="EMEA">
        <input type="hidden" name="serial_scheme" value="emea_rating">
        <input type="hidden" name="date_mode" value="month_year">
        <input type="hidden" name="month_letter_enabled" value="1">
        <input type="hidden" name="month_letter_map" value="A,B,C,D,E,F,G,H,J,K,L,M">
        <input type="hidden" name="year_digits" value="4">
        <input type="hidden" name="week_digits" value="2">
        <input type="hidden" name="include_year" value="1">
        <input type="hidden" name="include_week" value="0">
        <input type="hidden" name="qr_payload_format" value="emea_code_only">
        <input type="hidden" name="reset_scope" value="monthly">
        <input type="hidden" name="emea_unit_digits" value="{{ $emeaUnitDigits }}">
        <input type="hidden" name="unit_digits" value="{{ $emeaUnitDigits }}">

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <p class="font-semibold">Que estas configurando en EMEA?</p>
            <p class="mt-1">Estas armando el formato documentado: <strong>PPPP PP CC SSSSSS MYYYY</strong> (SAP console, conformidad, consecutivo, mes y ano).</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-800">Guia rapida (3 pasos)</p>
            <ol class="mt-2 space-y-1 text-sm text-slate-700 list-decimal list-inside">
                <li><strong>Paso 1:</strong> Selecciona SKU y parametros generales.</li>
                <li><strong>Paso 2:</strong> Define SAP console y codigo de conformidad.</li>
                <li><strong>Paso 3:</strong> Valida el serial de ejemplo antes de guardar.</li>
            </ol>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2 rounded-xl border border-slate-200 p-4 bg-white">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Paso 1 - Datos generales</p>
                <p class="mt-1 text-sm text-slate-600">EMEA usa ano de 4 digitos, mes con letra, consecutivo de 6 digitos y reinicio mensual.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">SKU</label>
                <select id="sku" name="sku" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    <option value="">Selecciona un SKU activo (EMEA)</option>
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
                <input name="description" value="{{ old('description', $format?->description ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="160" placeholder="Ej. EMEA serial format" />
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
                <label class="block text-sm font-medium text-slate-700">Consecutivo</label>
                <input type="text" value="6 digitos (000001-999999)" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700" readonly>
                <p class="mt-1 text-xs text-slate-500">La documentacion EMEA pide un numero continuo de 6 digitos.</p>
                @error('emea_unit_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                @error('unit_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Reinicio del consecutivo</label>
                <input type="text" value="Mensual" class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700" readonly>
                <p class="mt-1 text-xs text-slate-500">El consecutivo EMEA se controla por mes y ano.</p>
                @error('reset_scope') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', $format?->is_active ?? true) ? 'checked' : '' }}>
                    Activo
                </label>
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Paso 2 - Estructura EMEA - PPPP PP CC SSSSSS MYYYY</p>
                    <p class="mt-1 text-sm text-slate-600">Captura los ultimos 6 digitos del SAP console number y el codigo de conformidad.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">SAP console last 6 (PPPPPP)</label>
                    <input id="emeaPrefix" name="emea_prefix" value="{{ old('emea_prefix', $format?->emea_prefix ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="505554" />
                    <p class="mt-1 text-xs text-slate-500">Se imprime segmentado como 5055 54.</p>
                    @error('emea_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Conformity code (CC)</label>
                    <input id="emeaConformity" name="emea_conformity_code" value="{{ old('emea_conformity_code', $format?->emea_conformity_code ?? '01') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="2" pattern="[0-9]{2}" inputmode="numeric" placeholder="01" />
                    <p class="mt-1 text-xs text-slate-500">Ej. 00 sin declaracion de conformidad; 01 cuando aplique.</p>
                    @error('emea_conformity_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Paso 3 - Validacion visual (ejemplo en vivo)</p>
                <p class="mt-2 text-sm text-emerald-900">Asi se esta construyendo tu serial EMEA:</p>
                <p id="emeaLivePreview" class="mt-2 text-lg font-semibold text-emerald-950">5055 54 01 000001 A2026</p>
                <p id="emeaLiveBreakdown" class="mt-2 text-sm text-emerald-900">SAP=505554 - CC=01 - Unit=000001 - Date=A2026</p>
            </div>
        </div>

        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">{{ $isEdit ? 'Actualizar' : 'Guardar' }}</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const skuSelect = document.getElementById('sku');
    const skuLabelPartNumber = document.getElementById('skuLabelPartNumber');
    const prefix = document.getElementById('emeaPrefix');
    const conformity = document.getElementById('emeaConformity');
    const preview = document.getElementById('emeaLivePreview');
    const breakdown = document.getElementById('emeaLiveBreakdown');

    const monthCodes = ['A','B','C','D','E','F','G','H','J','K','L','M'];
    const pad = (value, len) => String(value).padStart(len, '0');

    const updateSkuLabelPartNumber = () => {
        if (!skuSelect || !skuLabelPartNumber) return;
        const selectedOption = skuSelect.options[skuSelect.selectedIndex];
        const labelPartNumber = selectedOption?.dataset?.labelPartNumber;
        skuLabelPartNumber.textContent = labelPartNumber ? `Label Part Number: ${labelPartNumber}` : '';
    };

    const render = () => {
        if (!preview) return;
        const now = new Date();
        const month = monthCodes[now.getMonth()] || 'A';
        const year = String(now.getFullYear());
        const units = pad(1, 6);
        const prefixText = (prefix?.value || '505554').replace(/\D/g, '').slice(0, 6);
        const conformityText = (conformity?.value || '01').replace(/\D/g, '').slice(0, 2);
        const prefixFirst = prefixText.slice(0, 4) || '5055';
        const prefixSecond = prefixText.slice(4, 6) || '54';
        const conformityCode = conformityText || '01';

        preview.textContent = [prefixFirst, prefixSecond, conformityCode, units, `${month}${year}`].join(' ');
        if (breakdown) {
            breakdown.textContent = `SAP=${prefixText || '505554'} - CC=${conformityCode} - Unit=${units} - Date=${month}${year}`;
        }
    };

    skuSelect?.addEventListener('change', updateSkuLabelPartNumber);
    [prefix, conformity].forEach((el) => {
        el?.addEventListener('input', render);
        el?.addEventListener('change', render);
    });
    updateSkuLabelPartNumber();
    render();
});
</script>
@endsection
