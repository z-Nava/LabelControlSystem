@extends('layouts.app', ['title' => 'Usuarios'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Usuarios</h1>
            <p class="text-slate-600 mt-1">Administración de altas, bajas, roles y estado activo.</p>
        </div>

        <a href="{{ route('users.create') }}"
           class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">
            + Nuevo usuario
        </a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <form class="mt-5 flex gap-2" method="GET" action="{{ route('users.index') }}">
        <input name="q" value="{{ $search }}"
               class="w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="Buscar por no. empleado o nombre..." />
        <button class="rounded-xl bg-slate-900 text-white px-4 py-2 hover:bg-slate-800 transition">
            Buscar
        </button>
    </form>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">No. Empleado</th>
                    <th class="py-3 pr-3">Nombre</th>
                    <th class="py-3 pr-3">Roles</th>
                    <th class="py-3 pr-3">Módulos Label Room</th>
                    <th class="py-3 pr-3">Turno</th>
                    <th class="py-3 pr-3">Estado</th>
                    <th class="py-3 pr-3">Último acceso</th>
                    <th class="py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($users as $user)
                    <tr>
                        <td class="py-3 pr-3 font-semibold text-slate-900">{{ $user->employee_no }}</td>
                        <td class="py-3 pr-3">{{ $user->name }}</td>
                        <td class="py-3 pr-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles as $role)
                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ $role->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-3 pr-3">
                            @if($user->hasRole('label_room'))
                                @if(empty($user->module_permissions))
                                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-emerald-800">Todos</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->module_permissions as $permission)
                                            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-blue-800">{{ $permission }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <span class="text-slate-500">N/A</span>
                            @endif
                        </td>
                        <td class="py-3 pr-3">{{ $user->shift_label ?? 'Sin turno' }}</td>
                        <td class="py-3 pr-3">
                            @if($user->is_active)
                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-green-800">Activo</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-slate-700">Inactivo</span>
                            @endif
                        </td>
                        <td class="py-3 pr-3">{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Nunca' }}</td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('users.edit', $user) }}"
                                   class="rounded-xl border px-3 py-2 hover:shadow transition">Editar</a>

                                <form method="POST" action="{{ route('users.toggle', $user) }}">
                                    @csrf
                                    <button class="rounded-xl bg-slate-900 text-white px-3 py-2 hover:bg-slate-800 transition">Toggle</button>
                                </form>

                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                      onsubmit="return confirm('¿Eliminar este usuario?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl bg-red-600 text-white px-3 py-2 hover:bg-red-500 transition">Baja</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-500">No hay usuarios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
