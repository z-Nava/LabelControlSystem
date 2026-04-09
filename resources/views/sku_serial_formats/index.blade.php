@extends('layouts.app', ['title' => 'SKU Serial Formats'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">SKU Serial Formats</h1>
            <p class="text-slate-600 mt-1">Formato de serial por SKU para construir serial_full.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
            <a href="{{ route('sku_serial_formats.create', ['standard' => 'UL']) }}"
               class="rounded-xl bg-slate-900 text-white px-4 py-2 font-semibold hover:bg-slate-800 transition text-center">
                + Agregar formato UL
            </a>
            <a href="{{ route('sku_serial_formats.create', ['standard' => 'EMEA']) }}"
               class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition text-center">
                + Agregar formato EMEA
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form class="mt-5 flex gap-2" method="GET" action="{{ route('sku_serial_formats.index') }}">
        <input name="q" value="{{ $search }}" class="w-full rounded-xl border border-slate-300 px-3 py-2"
               placeholder="Buscar por SKU, prefijos, separador o pattern..." />
        <button class="rounded-xl bg-slate-900 text-white px-4 py-2 hover:bg-slate-800 transition">Buscar</button>
    </form>

    @include('sku_serial_formats._table', [
        'title' => 'Estándar UL',
        'formats' => $ulFormats,
        'emptyMessage' => 'No hay formatos UL registrados.',
        'prefixLabel' => 'UL Prefix',
        'breakLabel' => 'UL Break',
        'plantLabel' => 'UL Plant',
    ])

    @include('sku_serial_formats._table', [
        'title' => 'Estándar EMEA',
        'formats' => $emeaFormats,
        'emptyMessage' => 'No hay formatos EMEA registrados.',
        'prefixLabel' => 'EMEA Base',
        'breakLabel' => 'EMEA Conformity',
        'plantLabel' => 'EMEA Plant/Line',
    ])
</div>
@endsection
