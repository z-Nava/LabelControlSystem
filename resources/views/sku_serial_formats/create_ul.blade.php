@php
    $isEdit = $isEdit ?? false;
    $format = $format ?? null;
    $unitDigits = (int) old('unit_digits', $format?->effectiveUnitDigits() ?? 5);
@endphp

@extends('layouts.app', ['title' => $isEdit ? 'Editar formato serial UL' : 'Agregar formato serial UL'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ $isEdit ? 'Editar formato serial - UL' : 'Agregar formato serial - UL' }}</h1>
        <a href="{{ route('sku_serial_formats.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    @include('sku_serial_formats._create_market_nav', ['forcedStandard' => 'UL'])

    <form class="mt-6 space-y-4" method="POST" action="{{ $isEdit ? route('sku_serial_formats.update', $format) : route('sku_serial_formats.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <input type="hidden" name="serial_standard" value="UL">

        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            <p class="font-semibold">Formato UL</p>
            <p class="mt-1">El serial se construye como <strong>PPP C PL YY WW SSSSS</strong>: prefijo, break, planta, ano, semana y consecutivo.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">SKU</label>
                <select id="sku" name="sku" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    <option value="">Selecciona un SKU activo (UL)</option>
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
                <input name="description" value="{{ old('description', $format?->description ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="160" placeholder="Ej. UL serial format" />
                @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Consecutivo</label>
                <input id="unitDigits" type="number" min="4" max="10" name="unit_digits" value="{{ $unitDigits }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                <p class="mt-1 text-xs text-slate-500">5 = 00001. 4 solo si el espacio de placa lo requiere.</p>
                @error('unit_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Componentes UL - PPP C PL YY WW SSSSS</p>
                    <p class="mt-1 text-sm text-slate-600">El prefijo normalmente usa 3 caracteres; para AEG North America puede usar 4. Break y planta usan 1 caracter.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Prefix (PPP)</label>
                    <input id="ulPrefix" name="ul_prefix" value="{{ old('ul_prefix', $format?->ul_prefix ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase" maxlength="4" placeholder="628" required />
                    @error('ul_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Serial break (C)</label>
                    <input id="ulBreak" name="ul_serial_break" value="{{ old('ul_serial_break', $format?->ul_serial_break ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase" maxlength="1" placeholder="D" required />
                    @error('ul_serial_break') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Plant code (PL)</label>
                    <input id="ulPlant" name="ul_plant_code" value="{{ old('ul_plant_code', $format?->ul_plant_code ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase" maxlength="1" placeholder="6" required />
                    @error('ul_plant_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" {{ old('is_active', $format?->is_active ?? true) ? 'checked' : '' }}>
                Activo
            </label>

            <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Validacion visual</p>
                <p class="mt-2 text-sm text-emerald-900">Asi se construira el serial UL:</p>
                <p id="ulLivePreview" class="mt-2 text-lg font-semibold text-emerald-950">628D6032300001</p>
                <p id="ulLiveBreakdown" class="mt-2 text-sm text-emerald-900">PPP=628 - C=D - PL=6 - YY=23 - WW=23 - S=00001</p>
            </div>
        </div>

        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">{{ $isEdit ? 'Actualizar' : 'Guardar' }}</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const skuSelect = document.getElementById('sku');
    const skuLabelPartNumber = document.getElementById('skuLabelPartNumber');
    const prefix = document.getElementById('ulPrefix');
    const brk = document.getElementById('ulBreak');
    const plant = document.getElementById('ulPlant');
    const unitDigits = document.getElementById('unitDigits');
    const preview = document.getElementById('ulLivePreview');
    const breakdown = document.getElementById('ulLiveBreakdown');

    const pad = (value, len) => String(value).padStart(len, '0');
    const upper = (value, fallback) => String(value || fallback).trim().toUpperCase();

    const updateSkuLabelPartNumber = () => {
        if (!skuSelect || !skuLabelPartNumber) return;
        const selectedOption = skuSelect.options[skuSelect.selectedIndex];
        const labelPartNumber = selectedOption?.dataset?.labelPartNumber;
        skuLabelPartNumber.textContent = labelPartNumber ? `Label Part Number: ${labelPartNumber}` : '';
    };

    const render = () => {
        if (!preview) return;
        const unitLen = Number(unitDigits?.value || 5);
        const now = new Date();
        const yearText = String(now.getFullYear()).slice(-2);
        const weekText = '23';
        const serialText = pad(1, unitLen);
        const prefixText = upper(prefix?.value, '628');
        const breakText = upper(brk?.value, 'D');
        const plantText = upper(plant?.value, '6');

        preview.textContent = `${prefixText}${breakText}${plantText}${yearText}${weekText}${serialText}`;
        if (breakdown) {
            breakdown.textContent = `PPP=${prefixText} - C=${breakText} - PL=${plantText} - YY=${yearText} - WW=${weekText} - S=${serialText}`;
        }
    };

    skuSelect?.addEventListener('change', updateSkuLabelPartNumber);
    [prefix, brk, plant].forEach((el) => {
        el?.addEventListener('input', () => {
            el.value = el.value.toUpperCase();
            render();
        });
    });
    unitDigits?.addEventListener('input', render);
    updateSkuLabelPartNumber();
    render();
});
</script>
@endsection
