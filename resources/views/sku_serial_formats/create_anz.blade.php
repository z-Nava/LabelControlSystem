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
        <input type="hidden" name="emea_plant_code" value="">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @include('sku_serial_formats._create_form_helpers', ['forcedStandard' => 'ANZ', 'activeSkus' => $activeSkus, 'showWeekControls' => false, 'lockYearToFour' => true])

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Estructura ANZ</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Prefix (PPPPPPPP)</label>
                    <input name="emea_prefix" value="{{ old('emea_prefix', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="AF02F2019" />
                    @error('emea_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Tool version (A-Z)</label>
                    <input name="emea_conformity_code" value="{{ old('emea_conformity_code', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="1" placeholder="A" />
                    @error('emea_conformity_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">ANZ Customer tool code (CCCC)</label>
                    <input name="anz_customer_tool_code" value="{{ old('anz_customer_tool_code', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="M12" />
                    <p class="mt-1 text-xs text-slate-500">QR: <strong>CCCC | PPPPPPPP A XXXXX MJJJJ</strong>.</p>
                    @error('anz_customer_tool_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">Guardar</button>
    </form>
</div>
@endsection
