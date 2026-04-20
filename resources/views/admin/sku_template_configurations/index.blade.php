@extends('layouts.app', ['title' => 'Configuración de templates por SKU'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Templates + Print Profiles</h1>
            <p class="text-slate-600 mt-1">Vista unificada por SKU y número de parte.</p>
        </div>
        <a href="{{ route('admin.sku_template_configurations.create') }}" class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">+ Nueva configuración</a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form class="mt-5 grid gap-2 md:grid-cols-[1fr_auto_auto]" method="GET" action="{{ route('admin.sku_template_configurations.index') }}">
        <input name="q" value="{{ $search }}" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Buscar por SKU, número de parte, tipo o nombre..." />
        <select name="sort" class="rounded-xl border border-slate-300 px-3 py-2 bg-white">
            @foreach($sorts as $sortValue => $sortLabel)
                <option value="{{ $sortValue }}" @selected($sort === $sortValue)>{{ $sortLabel }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-slate-900 text-white px-4 py-2">Aplicar</button>
    </form>

    <div class="mt-3 text-sm text-slate-600">
        Mostrando <span class="font-semibold">{{ $configs->firstItem() ?? 0 }}</span>–<span class="font-semibold">{{ $configs->lastItem() ?? 0 }}</span> de
        <span class="font-semibold">{{ $configs->total() }}</span> configuraciones.
    </div>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b bg-slate-50">
                    <th class="py-3 pr-3">SKU</th>
                    <th class="py-3 pr-3">Part Number</th>
                    <th class="py-3 pr-3">Tipo</th>
                    <th class="py-3 pr-3">Template</th>
                    <th class="py-3 pr-3">Print Profile</th>
                    <th class="py-3 pr-3">Activo</th>
                    <th class="py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($configs as $config)
                    <tr>
                        <td class="py-3 pr-3 font-semibold text-slate-900">{{ $config->sku?->sku ?? '-' }}</td>
                        <td class="py-3 pr-3">{{ $config->sku?->label_part_number ?? '-' }}</td>
                        <td class="py-3 pr-3">
                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                {{ ucfirst($config->label_type ?? 'general') }}
                            </span>
                        </td>
                        <td class="py-3 pr-3">{{ $config->template?->name ?? 'Sin template' }}</td>
                        <td class="py-3 pr-3">{{ $config->name }}</td>
                        <td class="py-3 pr-3">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $config->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ $config->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('admin.sku_template_configurations.edit', $config) }}" class="rounded-xl border px-3 py-2">Editar</a>
                                <form method="POST" action="{{ route('admin.sku_template_configurations.toggle', $config) }}">
                                    @csrf
                                    <button class="rounded-xl bg-slate-900 text-white px-3 py-2">{{ $config->is_active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-slate-500">No hay configuraciones registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $configs->links() }}</div>
</div>
@endsection
