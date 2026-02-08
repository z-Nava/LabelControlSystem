@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700">Code</label>
        <input name="code" value="{{ old('code', $line->code ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="MXC007" required />
        @error('code') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Type</label>
        <input name="line_type" value="{{ old('line_type', $line->line_type ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="consoles / batteries / motors / ops / hydraulics" required />
        @error('line_type') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">Name</label>
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
            Active
        </label>
        @error('active') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
</div>
