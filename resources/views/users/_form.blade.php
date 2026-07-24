@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">No. empleado</label>
        <input name="employee_no" value="{{ old('employee_no', $user->employee_no ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="14781" required />
        @error('employee_no') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Nombre</label>
        <input name="name" value="{{ old('name', $user->name ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="Nombre del usuario" required />
        @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Turno</label>
        <select name="shift_id"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            <option value="">Sin turno</option>
            @foreach($shifts as $shift)
                <option value="{{ $shift->id }}" @selected(old('shift_id', $user->shift_id ?? '') == $shift->id)>
                    Turno {{ $shift->code }}
                </option>
            @endforeach
        </select>
        @error('shift_id') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Línea de producción</label>
        <select name="production_line_id"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            <option value="">Sin línea asignada</option>
            @foreach($productionLines->groupBy('line_type') as $lineType => $lines)
                <optgroup label="{{ $lineType }}">
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}" @selected(old('production_line_id', $user->production_line_id ?? '') == $line->id)>
                            {{ $line->code }} — {{ $line->name }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        @error('production_line_id') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Puesto de producción</label>
        <select name="position"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            <option value="">Sin puesto asignado</option>
            @foreach($positions as $value => $label)
                <option value="{{ $value }}" @selected(old('position', $user->position ?? '') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('position') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 mt-7">
            <input type="checkbox" name="is_active" value="1"
                   class="rounded border-slate-300"
                   {{ old('is_active', ($user->is_active ?? true)) ? 'checked' : '' }}>
            Activo
        </label>
        @error('is_active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Roles</label>
        <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-2">
            @php
                $selectedRoles = old('roles', isset($user) ? $user->roles->pluck('id')->all() : []);
                $selectedModulePermissions = old('module_permissions', $user->module_permissions ?? []);
            @endphp

            @foreach($roles as $role)
                <label class="inline-flex items-center gap-2 rounded-xl border px-3 py-2">
                    <input
                        type="checkbox"
                        name="roles[]"
                        value="{{ $role->id }}"
                        class="rounded border-slate-300 js-role-checkbox"
                        data-role-name="{{ $role->name }}"
                        @checked(in_array($role->id, $selectedRoles))
                    >
                    <span class="text-sm">{{ $role->name }}</span>
                </label>
            @endforeach
        </div>
        @error('roles') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        @error('roles.*') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2" id="module-permissions-section">
        <label class="block text-sm font-medium text-slate-700">Módulos habilitados para Label Room</label>
        <p class="text-xs text-slate-500 mt-1">Si no seleccionas módulos, tendrá acceso total a Label Room por compatibilidad.</p>

        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
            @foreach($availableModulePermissions as $permission)
                <label class="inline-flex items-center gap-2 rounded-xl border px-3 py-2">
                    <input
                        type="checkbox"
                        name="module_permissions[]"
                        value="{{ $permission }}"
                        class="rounded border-slate-300"
                        @checked(in_array($permission, $selectedModulePermissions, true))
                    >
                    <span class="text-sm capitalize">{{ $permission }}</span>
                </label>
            @endforeach
        </div>
        @error('module_permissions') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        @error('module_permissions.*') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">
            Contraseña @if(isset($user))<span class="text-xs text-slate-500">(opcional para actualizar)</span>@endif
        </label>
        <input type="password" name="password"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="******" />
        @error('password') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Confirmar contraseña</label>
        <input type="password" name="password_confirmation"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="******" />
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleCheckboxes = Array.from(document.querySelectorAll('.js-role-checkbox'));
        const moduleSection = document.getElementById('module-permissions-section');

        if (!moduleSection || roleCheckboxes.length === 0) {
            return;
        }

        const toggleModuleSection = () => {
            const hasLabelRoomRole = roleCheckboxes.some((checkbox) => {
                return checkbox.checked && checkbox.dataset.roleName === 'label_room';
            });

            moduleSection.classList.toggle('hidden', !hasLabelRoomRole);
        };

        roleCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', toggleModuleSection);
        });

        toggleModuleSection();
    });
</script>
