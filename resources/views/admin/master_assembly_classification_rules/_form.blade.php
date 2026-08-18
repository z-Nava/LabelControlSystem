@csrf

@php
    $selectedField = old('match_field', $rule->match_field ?? \App\Models\MasterAssemblyClassificationRule::FIELD_ASSEMBLY);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">Campo de Oracle</label>
        <select name="match_field"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                required>
            @foreach(\App\Models\MasterAssemblyClassificationRule::MATCH_FIELDS as $field)
                <option value="{{ $field }}" @selected($selectedField === $field)>
                    {{ \App\Models\MasterAssemblyClassificationRule::fieldLabel($field) }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Normalmente usa Assembly; Línea se utiliza para casos como Motores.</p>
        @error('match_field') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Prefijo</label>
        <input name="prefix" value="{{ old('prefix', $rule->prefix ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="103" maxlength="30" required />
        <p class="mt-1 text-xs text-slate-500">Se compara desde el inicio del valor, sin distinguir mayúsculas.</p>
        @error('prefix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Descripción</label>
        <input name="description" value="{{ old('description', $rule->description ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="Ensamble / Subensamble" maxlength="160" />
        @error('description') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-sm font-medium text-slate-800">Clasificación permitida</div>
        <p class="mt-1 text-xs text-slate-500">Puedes seleccionar ambas cuando el prefijo sea válido para Ensamble y Empaque.</p>

        <div class="mt-3 flex flex-wrap gap-6">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="allows_assembly" value="0">
                <input type="checkbox" name="allows_assembly" value="1"
                       class="rounded border-slate-300"
                       @checked(old('allows_assembly', $rule->allows_assembly ?? true))>
                Ensamble
            </label>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="allows_packaging" value="0">
                <input type="checkbox" name="allows_packaging" value="1"
                       class="rounded border-slate-300"
                       @checked(old('allows_packaging', $rule->allows_packaging ?? false))>
                Empaque
            </label>
        </div>
        @error('allows_assembly') <div class="text-sm text-red-600 mt-2">{{ $message }}</div> @enderror
        @error('allows_packaging') <div class="text-sm text-red-600 mt-2">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1"
                   class="rounded border-slate-300"
                   @checked(old('active', $rule->active ?? true))>
            Regla activa
        </label>
        @error('active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
</div>
