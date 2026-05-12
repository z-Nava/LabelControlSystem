@extends('layouts.app', ['title' => 'Production Lines'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Production Lines</h1>
            <p class="text-slate-600 mt-1">Catálogo de líneas para requisiciones y reportes.</p>
        </div>

        <a href="{{ route('production_lines.create') }}"
           class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">
            + Nueva línea
        </a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form class="mt-5 flex gap-2" method="GET" action="{{ route('production_lines.index') }}">
        <input name="q" value="{{ $search }}"
               class="w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="Buscar por code, name o type..." />
        <button class="rounded-xl bg-slate-900 text-white px-4 py-2 hover:bg-slate-800 transition">
            Buscar
        </button>
    </form>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">Codigo</th>
                    <th class="py-3 pr-3">Nombre</th>
                    <th class="py-3 pr-3">Tipo</th>
                    <th class="py-3 pr-3">Activo</th>
                    <th class="py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($lines as $line)
                    <tr>
                        <td class="py-3 pr-3 font-semibold text-slate-900">{{ $line->code }}</td>
                        <td class="py-3 pr-3">{{ $line->name }}</td>
                        <td class="py-3 pr-3">
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-slate-700">
                                {{ $line->line_type }}
                            </span>
                        </td>
                        <td class="py-3 pr-3">
                            @if($line->active)
                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-green-800">Activo</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-slate-700">Inactivo</span>
                            @endif
                        </td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('production_lines.edit', $line) }}"
                                   class="rounded-xl border px-3 py-2 hover:shadow transition">
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('production_lines.toggle', $line) }}">
                                    @csrf
                                    <button class="rounded-xl {{ $line->active ? 'bg-red-600 hover:bg-red-500' : 'bg-green-600 hover:bg-green-500' }} text-white px-3 py-2 transition">
                                        {{ $line->active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-500">
                            No hay líneas registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $lines->links() }}
    </div>
</div>
@endsection
