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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @include('sku_serial_formats._create_form_helpers', ['forcedStandard' => 'EMEA', 'activeSkus' => $activeSkus])

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Estructura EMEA</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Base code</label>
                    <input name="emea_prefix" value="{{ old('emea_prefix', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="5055 54" />
                    @error('emea_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Conformity / Version code</label>
                    <input name="emea_conformity_code" value="{{ old('emea_conformity_code', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="01" />
                    @error('emea_conformity_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Plant / line code (opcional)</label>
                    <input name="emea_plant_code" value="{{ old('emea_plant_code', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="(vacío)" />
                    @error('emea_plant_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">Guardar</button>
    </form>
</div>
@endsection
