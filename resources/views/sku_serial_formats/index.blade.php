@extends('layouts.app', ['title' => 'SKU Serial Formats'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">SKU Serial Formats</h1>
            <p class="text-slate-600 mt-1">Formato de serial por SKU para construir serial_full.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
            @foreach(['UL' => 'bg-slate-900 hover:bg-slate-800', 'EMEA' => 'bg-red-600 hover:bg-red-500', 'ANZ' => 'bg-indigo-600 hover:bg-indigo-500'] as $standard => $buttonClasses)
                <a href="{{ route('sku_serial_formats.create', ['standard' => $standard]) }}"
                   class="rounded-xl {{ $buttonClasses }} text-white px-4 py-2 font-semibold transition text-center">
                    + Agregar formato {{ $standard }}
                </a>
            @endforeach
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

    @foreach(['UL', 'EMEA', 'ANZ'] as $standard)
        @php($isUl = $standard === 'UL')
        @include('sku_serial_formats._table', [
            'title' => "Estándar {$standard}",
            'formats' => $formatsByStandard[$standard] ?? collect(),
            'emptyMessage' => "No hay formatos {$standard} registrados.",
            'prefixLabel' => $isUl ? 'UL Prefix' : 'Base code',
            'breakLabel' => $isUl ? 'UL Break' : 'Version code',
            'plantLabel' => $isUl ? 'UL Plant' : 'Plant/Line (opcional)',
        ])
    @endforeach
</div>
@endsection
