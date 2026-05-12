@csrf

@php
    $lineTypeOptions = \App\Models\ProductionLine::TYPES;
    $selectedLineType = old('line_type', $line->line_type ?? '');
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">Codigo</label>
        <input name="code" value="{{ old('code', $line->code ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="MXC007" required />
        @error('code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Tipo</label>
        <select name="line_type"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                required>
            <option value="" disabled @selected($selectedLineType === '')>Selecciona un tipo</option>
            @foreach($lineTypeOptions as $lineTypeOption)
                <option value="{{ $lineTypeOption }}" @selected($selectedLineType === $lineTypeOption)>
                    {{ $lineTypeOption }}
                </option>
            @endforeach
        </select>
        @error('line_type') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Nombre</label>
        <input name="name" value="{{ old('name', $line->name ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="MXC Consoles Line 007" required />
        @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="active" value="1"
                   class="rounded border-slate-300"
                   {{ old('active', ($line->active ?? true)) ? 'checked' : '' }}>
            Activo
        </label>
        @error('active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
</div>
