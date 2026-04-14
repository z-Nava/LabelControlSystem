@extends('layouts.app', ['title' => 'Templates Dummy QR'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Templates Dummy QR</h1>
            <p class="text-slate-600 mt-1">Configura layout y perfil básico de impresión para RMT y RW.</p>
        </div>
        <a href="{{ route('admin.dummy_qr_templates.create') }}" class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">+ Nuevo template</a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form class="mt-5 flex gap-2" method="GET" action="{{ route('admin.dummy_qr_templates.index') }}">
        <input name="q" value="{{ $search }}" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Buscar por nombre, tipo o impresora..." />
        <button class="rounded-xl bg-slate-900 text-white px-4 py-2">Buscar</button>
    </form>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">Nombre</th>
                    <th class="py-3 pr-3">Tipo</th>
                    <th class="py-3 pr-3">DPI</th>
                    <th class="py-3 pr-3">Conexión</th>
                    <th class="py-3 pr-3">Impresora</th>
                    <th class="py-3 pr-3">Activo</th>
                    <th class="py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($templates as $template)
                    <tr>
                        <td class="py-3 pr-3 font-semibold text-slate-900">{{ $template->name }}</td>
                        <td class="py-3 pr-3">{{ strtoupper($template->dummy_type) }}</td>
                        <td class="py-3 pr-3">{{ $template->dpi }}</td>
                        <td class="py-3 pr-3">{{ strtoupper($template->connection_type) }}</td>
                        <td class="py-3 pr-3">{{ $template->default_printer_name ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $template->is_active ? 'Sí' : 'No' }}</td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('admin.dummy_qr_templates.edit', $template) }}" class="rounded-xl border px-3 py-2">Editar</a>
                                <form method="POST" action="{{ route('admin.dummy_qr_templates.toggle', $template) }}">
                                    @csrf
                                    <button class="rounded-xl bg-slate-900 text-white px-3 py-2">{{ $template->is_active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-slate-500">No hay templates Dummy QR registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $templates->links() }}</div>
</div>
@endsection
