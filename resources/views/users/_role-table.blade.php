<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-slate-900">{{ $title }}</h2>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                    {{ $users->total() }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        </div>

        <span @class([
            'w-fit rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide',
            'bg-red-50 text-red-700' => $role === 'admin',
            'bg-blue-50 text-blue-700' => $role === 'label_room',
            'bg-emerald-50 text-emerald-700' => $role === 'kiosk',
        ])>
            {{ $role === 'label_room' ? 'Label Room' : ucfirst($role) }}
        </span>
    </div>

    <div class="overflow-x-auto px-5">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-3 pr-3">No. Empleado</th>
                    <th class="py-3 pr-3">Nombre</th>

                    @if($role === 'admin')
                        <th class="py-3 pr-3">Otros roles</th>
                    @elseif($role === 'label_room')
                        <th class="py-3 pr-3">Módulos</th>
                        <th class="py-3 pr-3">Turno</th>
                    @else
                        <th class="py-3 pr-3">Línea</th>
                        <th class="py-3 pr-3">Turno</th>
                        <th class="py-3 pr-3">Puesto</th>
                    @endif

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

                        @if($role === 'admin')
                            <td class="py-3 pr-3">
                                @php($otherRoles = $user->roles->where('name', '!=', 'admin'))
                                @forelse($otherRoles as $otherRole)
                                    <span class="mr-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700">
                                        {{ $otherRole->name }}
                                    </span>
                                @empty
                                    <span class="text-slate-500">Ninguno</span>
                                @endforelse
                            </td>
                        @elseif($role === 'label_room')
                            <td class="py-3 pr-3">
                                @if(empty($user->module_permissions))
                                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-emerald-800">Todos</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->module_permissions as $permission)
                                            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-blue-800">
                                                {{ $permission }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 pr-3">{{ $user->shift_label ?? 'Sin turno' }}</td>
                        @else
                            <td class="py-3 pr-3">
                                @if($user->productionLine)
                                    <div class="font-medium text-slate-800">{{ $user->productionLine->code }}</div>
                                    <div class="text-xs text-slate-500">{{ $user->productionLine->name }}</div>
                                @else
                                    <span class="text-slate-500">Sin línea</span>
                                @endif
                            </td>
                            <td class="py-3 pr-3">{{ $user->shift?->name ?? 'Sin turno' }}</td>
                            <td class="py-3 pr-3">{{ $user->position_label ?? 'Sin puesto' }}</td>
                        @endif

                        <td class="py-3 pr-3">
                            @if($user->is_active)
                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-green-800">Activo</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-slate-700">Inactivo</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap py-3 pr-3">
                            {{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Nunca' }}
                        </td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('users.edit', $user) }}"
                                   class="rounded-xl border px-3 py-2 transition hover:shadow">
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('users.toggle', $user) }}">
                                    @csrf
                                    <button class="rounded-xl bg-slate-900 px-3 py-2 text-white transition hover:bg-slate-800">
                                        {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                      onsubmit="return confirm('¿Eliminar este usuario?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl bg-red-600 px-3 py-2 text-white transition hover:bg-red-500">
                                        Baja
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="{{ $role === 'label_room' || $role === 'kiosk' ? 8 : 7 }}"
                            class="py-6 text-center text-slate-500"
                        >
                            No hay usuarios en esta categoría.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="border-t border-slate-200 px-5 py-4">
            {{ $users->links() }}
        </div>
    @endif
</section>
