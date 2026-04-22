@extends('layouts.app', ['title' => 'Agregar formato serial UL'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Agregar formato serial · UL</h1>
        <a href="{{ route('sku_serial_formats.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    @include('sku_serial_formats._create_market_nav', ['forcedStandard' => 'UL'])

    <form class="mt-6 space-y-4" method="POST" action="{{ route('sku_serial_formats.store') }}">
        @csrf
        <input type="hidden" name="serial_standard" value="UL">
        <input type="hidden" name="serial_scheme" value="ul_standard">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @include('sku_serial_formats._create_form_helpers', ['forcedStandard' => 'UL', 'activeSkus' => $activeSkus])

            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Estructura UL</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">UL Prefix (PPP)</label>
                    <input name="ul_prefix" value="{{ old('ul_prefix', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="628" />
                    @error('ul_prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">UL Serial break (C)</label>
                    <input name="ul_serial_break" value="{{ old('ul_serial_break', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="D" />
                    @error('ul_serial_break') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">UL Plant code (PL)</label>
                    <input name="ul_plant_code" value="{{ old('ul_plant_code', '') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" maxlength="10" placeholder="6" />
                    @error('ul_plant_code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">Guardar</button>
    </form>
</div>
@endsection
