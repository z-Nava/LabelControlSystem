@php
    $isEdit = $isEdit ?? false;
    $format = $format ?? null;
    $selectedSeparator = old('separator', $format?->separator ?? '');
    $resetScope = old('reset_scope', $format?->reset_scope ?? 'weekly');
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
        <input type="hidden" name="serial_scheme" value="ul_standard">
        <input type="hidden" name="date_mode" value="year_week">
        <input type="hidden" name="month_letter_enabled" value="0">
        <input type="hidden" name="qr_payload_format" value="serial_only">

        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            <p class="font-semibold">Que estas configurando en UL?</p>
            <p class="mt-1">Estas definiendo como construir el serial: <strong>PPP C PL YY WW SSSSS</strong>.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-800">Guia rapida (3 pasos)</p>
            <ol class="mt-2 space-y-1 text-sm text-slate-700 list-decimal list-inside">
                <li><strong>Paso 1:</strong> Selecciona el SKU y define la base del serial.</li>
                <li><strong>Paso 2:</strong> Captura componentes UL (Prefix, Break, Plant).</li>
                <li><strong>Paso 3:</strong> Revisa el ejemplo en vivo y guarda.</li>
            </ol>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2 rounded-xl border border-slate-200 p-4 bg-white">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Paso 1 - Datos generales</p>
                <p class="mt-1 text-sm text-slate-600">Estos campos definen la base del formato para el SKU seleccionado.</p>
            </div>

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
                <label class="block text-sm font-medium text-slate-700">Ano (digitos)</label>
                <select id="yearDigits" name="year_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    <option value="2" @selected((int) old('year_digits', $format?->year_digits ?? 2) === 2)>2 (YY)</option>
                    <option value="4" @selected((int) old('year_digits', $format?->year_digits ?? 2) === 4)>4 (YYYY)</option>
                </select>
                @error('year_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Semana (digitos)</label>
                <select id="weekDigits" name="week_digits" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    <option value="1" @selected((int) old('week_digits', $format?->week_digits ?? 2) === 1)>1 (W)</option>
                    <option value="2" @selected((int) old('week_digits', $format?->week_digits ?? 2) === 2)>2 (WW)</option>
                </select>
                @error('week_digits') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Unit digits</label>
                <input id="unitDigits" type="number" min="1" max="10" name="unit_digits" value="{{ old('unit_digits', $format?->effectiveUnitDigits() ?? 5) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required />
                <p class="mt-1 text-xs text-slate-500">Cuantos digitos tendra el consecutivo (ej. 5 = 00001).</p>
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
                <label class="block text-sm font-medium text-slate-700">Separador entre segmentos</label>
                <select name="separator" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <option value="" @selected($selectedSeparator === '')>Sin separador</option>
                    <option value="__SPACE__" @selected(in_array($selectedSeparator, [' ', '__SPACE__'], true))>Espacio</option>
                    <option value="-" @selected($selectedSeparator === '-')>-</option>
                    <option value="_" @selected($selectedSeparator === '_')>_</option>
                    <option value="|" @selected($selectedSeparator === '|')>|</option>
                </select>
                @error('separator') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="include_year" value="0">
                    <input type="checkbox" name="include_year" value="1" class="rounded border-slate-300" {{ old('include_year', $format?->include_year ?? true) ? 'checked' : '' }}>
                    Incluir ano
                </label>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="include_week" value="0">
                    <input type="checkbox" name="include_week" value="1" class="rounded border-slate-300" {{ old('include_week', $format?->include_week ?? true) ? 'checked' : '' }}>
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
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Paso 2 - Estructura UL - PPP C PL YY WW SSSSS</p>
                    <p class="mt-1 text-sm text-slate-600">Llena cada bloque. El sistema te mostrara el resultado final automaticamente.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">UL Prefix (PPP)</label>
                    <input id="ulPrefix" name="ul_prefix" value="{{ old('ul_prefix', $format?->ul_prefix ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="628" />
                    @error('ul_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">UL Serial break (C)</label>
                    <input id="ulBreak" name="ul_serial_break" value="{{ old('ul_serial_break', $format?->ul_serial_break ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="D" />
                    @error('ul_serial_break') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">UL Plant code (PL)</label>
                    <input id="ulPlant" name="ul_plant_code" value="{{ old('ul_plant_code', $format?->ul_plant_code ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="6" />
                    @error('ul_plant_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Prefix length (opcional)</label>
                    <input type="number" min="1" max="10" name="ul_prefix_length" value="{{ old('ul_prefix_length', $format?->ul_prefix_length ?? '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
                    @error('ul_prefix_length') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 pt-8">
                    <input type="hidden" name="ul_use_plant_code" value="0">
                    <input type="checkbox" name="ul_use_plant_code" value="1" class="rounded border-slate-300" {{ old('ul_use_plant_code', $format?->ul_use_plant_code ?? true) ? 'checked' : '' }}>
                    Usar codigo de planta
                </label>
            </div>

            <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Paso 3 - Validacion visual (ejemplo en vivo)</p>
                <p class="mt-2 text-sm text-emerald-900">Asi se esta construyendo tu serial UL:</p>
                <p id="ulLivePreview" class="mt-2 text-lg font-semibold text-emerald-950">628D6032300001</p>
                <p id="ulLiveBreakdown" class="mt-2 text-sm text-emerald-900">PPP=628 - C=D - PL=6 - YY=23 - WW=23 - SSSSS=00001</p>
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
    const yearDigits = document.getElementById('yearDigits');
    const weekDigits = document.getElementById('weekDigits');
    const unitDigits = document.getElementById('unitDigits');
    const preview = document.getElementById('ulLivePreview');
    const breakdown = document.getElementById('ulLiveBreakdown');

    const pad = (value, len) => String(value).padStart(len, '0');

    const updateSkuLabelPartNumber = () => {
        if (!skuSelect || !skuLabelPartNumber) return;
        const selectedOption = skuSelect.options[skuSelect.selectedIndex];
        const labelPartNumber = selectedOption?.dataset?.labelPartNumber;
        skuLabelPartNumber.textContent = labelPartNumber ? `Label Part Number: ${labelPartNumber}` : '';
    };

    const render = () => {
        if (!preview) return;
        const yearLen = Number(yearDigits?.value || 2);
        const weekLen = Number(weekDigits?.value || 2);
        const unitLen = Number(unitDigits?.value || 5);
        const now = new Date();
        const yearText = yearLen === 4 ? String(now.getFullYear()) : String(now.getFullYear()).slice(-2);
        const weekText = pad(23, weekLen);
        const serialText = pad(1, unitLen);
        const prefixText = (prefix?.value || '628').toUpperCase();
        const breakText = (brk?.value || 'D').toUpperCase();
        const plantText = (plant?.value || '6').toUpperCase();

        preview.textContent = `${prefixText}${breakText}${plantText}${yearText}${weekText}${serialText}`;
        if (breakdown) {
            breakdown.textContent = `PPP=${prefixText} - C=${breakText} - PL=${plantText} - YY=${yearText} - WW=${weekText} - SSSSS=${serialText}`;
        }
    };

    skuSelect?.addEventListener('change', updateSkuLabelPartNumber);
    [prefix, brk, plant, yearDigits, weekDigits, unitDigits].forEach((el) => el?.addEventListener('input', render));
    updateSkuLabelPartNumber();
    render();
});
</script>
@endsection
