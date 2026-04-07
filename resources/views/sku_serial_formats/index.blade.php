@extends('layouts.app', ['title' => 'SKU Serial Formats'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">SKU Serial Formats</h1>
            <p class="text-slate-600 mt-1">Formato de serial por SKU para construir serial_full.</p>
        </div>

        <a href="{{ route('sku_serial_formats.create') }}"
           class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">
            + Agregar formato
        </a>
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

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">SKU</th>
                    <th class="py-3 pr-3">Estándar</th>
                    <th class="py-3 pr-3">Esquema</th>
                    <th class="py-3 pr-3">Prefix</th>
                    <th class="py-3 pr-3">Break</th>
                    <th class="py-3 pr-3">Plant</th>
                    <th class="py-3 pr-3">Sep</th>
                    <th class="py-3 pr-3">Año/Sem</th>
                    <th class="py-3 pr-3">Unit</th>
                    <th class="py-3 pr-3">Pattern</th>
                    <th class="py-3 pr-3">Activo</th>
                    <th class="py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($formats as $format)
                    <tr>
                        <td class="py-3 pr-3 font-semibold text-slate-900">{{ $format->sku }}</td>
                        <td class="py-3 pr-3">{{ $format->serial_standard ?? 'UL' }}</td>
                        <td class="py-3 pr-3">{{ $format->serial_scheme ?? 'ul_standard' }}</td>
                        <td class="py-3 pr-3">{{ $format->prefix ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->serial_break ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->plant_code ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->separator === '' ? '∅' : $format->separator }}</td>
                        <td class="py-3 pr-3">
                            {{ $format->include_year ? $format->year_digits : '-' }}/{{ $format->include_week ? $format->week_digits : '-' }}
                        </td>
                        <td class="py-3 pr-3">{{ $format->unit_length }}</td>
                        <td class="py-3 pr-3">{{ $format->pattern ?: 'segmentado' }}</td>
                        <td class="py-3 pr-3">
                            @if($format->is_active)
                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-green-800">Sí</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-slate-700">No</span>
                            @endif
                        </td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('sku_serial_formats.edit', $format) }}" class="rounded-xl border px-3 py-2 hover:shadow transition">Editar</a>
                                <form method="POST" action="{{ route('sku_serial_formats.toggle', $format) }}">
                                    @csrf
                                    <button class="rounded-xl bg-slate-900 text-white px-3 py-2 hover:bg-slate-800 transition">
                                        {{ $format->is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="py-6 text-center text-slate-500">No hay formatos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $formats->links() }}</div>
</div>
@endsection
