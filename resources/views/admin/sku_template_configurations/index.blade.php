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

    <form class="mt-5 flex gap-2" method="GET" action="{{ route('admin.sku_template_configurations.index') }}">
        <input name="q" value="{{ $search }}" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Buscar por SKU, número de parte, tipo o nombre..." />
        <button class="rounded-xl bg-slate-900 text-white px-4 py-2">Buscar</button>
    </form>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
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
                        <td class="py-3 pr-3">{{ ucfirst($config->label_type ?? 'general') }}</td>
                        <td class="py-3 pr-3">{{ $config->template?->name ?? 'Sin template' }}</td>
                        <td class="py-3 pr-3">{{ $config->name }}</td>
                        <td class="py-3 pr-3">{{ $config->is_active ? 'Sí' : 'No' }}</td>
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
