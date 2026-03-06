@extends('layouts.app', ['title' => 'Label SKU Tools'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Label SKU Tools</h1>
            <p class="text-slate-600 mt-1">Catálogo SKU ↔ Label PN para validación operativa.</p>
        </div>

        <a href="{{ route('label_skus.create') }}"
           class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">
            + Agregar SKU
        </a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form class="mt-5 flex gap-2" method="GET" action="{{ route('label_skus.index') }}">
        <input name="q" value="{{ $search }}"
               class="w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="Buscar por SKU, Label PN o descripción..." />
        <button class="rounded-xl bg-slate-900 text-white px-4 py-2 hover:bg-slate-800 transition">
            Buscar
        </button>
    </form>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">SKU</th>
                    <th class="py-3 pr-3">Label PN</th>
                    <th class="py-3 pr-3">Descripción</th>
                    <th class="py-3 pr-3">Activo</th>
                    <th class="py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($labelSkus as $labelSku)
                    <tr>
                        <td class="py-3 pr-3 font-semibold text-slate-900">{{ $labelSku->sku }}</td>
                        <td class="py-3 pr-3">{{ $labelSku->label_part_number }}</td>
                        <td class="py-3 pr-3">{{ $labelSku->description ?: '-' }}</td>
                        <td class="py-3 pr-3">
                            @if($labelSku->is_active)
                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-green-800">Sí</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-slate-700">No</span>
                            @endif
                        </td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('label_skus.edit', $labelSku) }}"
                                   class="rounded-xl border px-3 py-2 hover:shadow transition">
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('label_skus.toggle', $labelSku) }}">
                                    @csrf
                                    <button class="rounded-xl bg-slate-900 text-white px-3 py-2 hover:bg-slate-800 transition">
                                        {{ $labelSku->is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-500">
                            No hay SKUs registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $labelSkus->links() }}
    </div>
</div>
@endsection
