@extends('layouts.app', ['title' => 'Modelos Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">CRUD Master: {{ $typeLabel }}</h1>
            <p class="text-slate-600 mt-1">Catálogo NP/SKU para resolver el Modelo en hojas master.</p>
        </div>

        <a href="{{ route('master_model_mappings.create', $type) }}"
           class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">
            + Nuevo registro
        </a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-3">
        <form class="flex gap-2" method="GET" action="{{ route('master_model_mappings.index', $type) }}">
            <input name="q" value="{{ $search }}"
                   class="w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                   placeholder="Buscar por NP o SKU..." />
            <button class="rounded-xl bg-slate-900 text-white px-4 py-2 hover:bg-slate-800 transition">Buscar</button>
        </form>

        <form method="POST" enctype="multipart/form-data" action="{{ route('master_model_mappings.import', $type) }}" class="flex gap-2">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                   class="w-full rounded-xl border border-slate-300 px-3 py-2 bg-white" />
            <button class="rounded-xl bg-emerald-700 text-white px-4 py-2 hover:bg-emerald-600 transition">Importar</button>
        </form>
    </div>

    <p class="text-xs text-slate-500 mt-2">Formato esperado: NP, SKU, HOJA MASTER. En este módulo se fuerza el tipo {{ $typeLabel }}.</p>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">NP</th>
                    <th class="py-3 pr-3">SKU / Modelo</th>
                    <th class="py-3 pr-3">Activo</th>
                    <th class="py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($mappings as $mapping)
                    <tr>
                        <td class="py-3 pr-3 font-semibold text-slate-900">{{ $mapping->np }}</td>
                        <td class="py-3 pr-3">{{ $mapping->sku }}</td>
                        <td class="py-3 pr-3">
                            @if($mapping->active)
                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-green-800">Sí</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-slate-700">No</span>
                            @endif
                        </td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('master_model_mappings.edit', [$type, $mapping]) }}"
                                   class="rounded-xl border px-3 py-2 hover:shadow transition">Editar</a>

                                <form method="POST" action="{{ route('master_model_mappings.toggle', [$type, $mapping]) }}">
                                    @csrf
                                    <button class="rounded-xl bg-slate-900 text-white px-3 py-2 hover:bg-slate-800 transition">Toggle</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-slate-500">No hay registros para este tipo.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $mappings->links() }}
    </div>
</div>
@endsection
