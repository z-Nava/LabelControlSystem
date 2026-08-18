@extends('layouts.app', ['title' => 'Master Assemblies Classification Rules'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Master Assemblies Classification Rules</h1>
            <p class="text-slate-600 mt-1">Administra los prefijos que permiten usar un Job como Ensamble o Empaque.</p>
        </div>

        <a href="{{ route('admin.master_assembly_classification_rules.create') }}"
           class="rounded-xl bg-red-600 text-center text-white px-4 py-2 font-semibold hover:bg-red-500 transition">
            + Nueva regla
        </a>
    </div>

    <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
        Ejemplo: el prefijo <strong>103</strong> en Assembly acepta valores como 103920001. Los cambios aplican en las siguientes consultas y requisiciones.
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form class="mt-5 flex gap-2" method="GET" action="{{ route('admin.master_assembly_classification_rules.index') }}">
        <input name="q" value="{{ $search }}"
               class="w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="Buscar prefijo, descripción o campo..." />
        <button class="rounded-xl bg-slate-900 text-white px-4 py-2 hover:bg-slate-800 transition">Buscar</button>
    </form>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">Campo</th>
                    <th class="py-3 pr-3">Prefijo</th>
                    <th class="py-3 pr-3">Descripción</th>
                    <th class="py-3 pr-3">Uso</th>
                    <th class="py-3 pr-3">Estado</th>
                    <th class="py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($rules as $rule)
                    <tr>
                        <td class="py-3 pr-3">{{ \App\Models\MasterAssemblyClassificationRule::fieldLabel($rule->match_field) }}</td>
                        <td class="py-3 pr-3 font-semibold text-slate-900">{{ $rule->prefix }}</td>
                        <td class="py-3 pr-3 text-slate-600">{{ $rule->description ?: '—' }}</td>
                        <td class="py-3 pr-3">
                            <div class="flex flex-wrap gap-1">
                                @if($rule->allows_assembly)
                                    <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-blue-800">Ensamble</span>
                                @endif
                                @if($rule->allows_packaging)
                                    <span class="inline-flex rounded-full bg-purple-100 px-3 py-1 text-purple-800">Empaque</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 pr-3">
                            @if($rule->active)
                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-green-800">Activa</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-slate-700">Inactiva</span>
                            @endif
                        </td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('admin.master_assembly_classification_rules.edit', $rule) }}"
                                   class="rounded-xl border px-3 py-2 hover:shadow transition">Editar</a>

                                <form method="POST" action="{{ route('admin.master_assembly_classification_rules.toggle', $rule) }}">
                                    @csrf
                                    <button class="rounded-xl {{ $rule->active ? 'bg-red-600 hover:bg-red-500' : 'bg-green-600 hover:bg-green-500' }} text-white px-3 py-2 transition">
                                        {{ $rule->active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-500">No hay reglas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $rules->links() }}</div>
</div>
@endsection
