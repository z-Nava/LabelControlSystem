@extends('layouts.app', ['title' => 'Catálogo Rating y Ensamble', 'mainClass' => 'max-w-7xl'])

@section('content')
<div class="rounded-2xl bg-white p-6 shadow">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Catálogo Rating y Ensamble</h1>
            <p class="mt-1 text-slate-600">Números de parte Rating, Serial, Shipping e Inner por ensamble de empaque 018 y mercado.</p>
        </div>
        <a href="{{ route('rating_assembly_mappings.create') }}"
           class="rounded-xl bg-red-600 px-4 py-2 text-center font-semibold text-white transition hover:bg-red-500">+ Nueva relación</a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <div class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-2">
        <form method="GET" action="{{ route('rating_assembly_mappings.index') }}" class="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_auto_auto_auto]">
            <input name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="Buscar ensamble o NP de etiqueta..."
                   class="rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" />
            <select name="market" class="rounded-xl border border-slate-300 px-3 py-2">
                <option value="">Mercados</option>
                @foreach($markets as $market)<option value="{{ $market }}" @selected(($filters['market'] ?? '') === $market)>{{ $market }}</option>@endforeach
            </select>
            <select name="status" class="rounded-xl border border-slate-300 px-3 py-2">
                <option value="">Estados</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activos</option>
                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactivos</option>
            </select>
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-white hover:bg-slate-800">Buscar</button>
        </form>

        <form method="POST" enctype="multipart/form-data" action="{{ route('rating_assembly_mappings.import') }}" class="flex flex-col gap-2 sm:flex-row">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                   class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2" />
            <button class="rounded-xl bg-emerald-700 px-4 py-2 font-semibold text-white hover:bg-emerald-600">Importar Excel</button>
        </form>
    </div>
    <p class="mt-2 text-xs text-slate-500">Columnas requeridas: EMPAQUE, RATING_PN y MERCADO. Opcionales: SERIAL_NP, SHIPPING_NP e INNER_NP. También acepta ENSAMBLE/ASSEMBLY, NP_RATING y MARKET/SERIAL_STANDARD. Alias: UK/EU → EMEA; ASIA/JPN → APJ. Formatea los números de parte como texto en Excel para conservar ceros iniciales.</p>
    <p class="mt-1 text-xs text-slate-500">Si omites una columna opcional, se conserva su valor actual. Si la incluyes vacía, se borra ese número de parte. Las filas incompletas, con mercado no reconocido o con números de parte de más de 80 caracteres se omiten.</p>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full min-w-[1100px] text-sm">
            <thead><tr class="border-b text-left text-slate-500">
                <th class="py-3 pr-3">Ensamble / Empaque 018</th><th class="py-3 pr-3">NP Rating</th><th class="py-3 pr-3">NP Serial</th><th class="py-3 pr-3">NP Shipping</th><th class="py-3 pr-3">NP Inner</th><th class="py-3 pr-3">Mercado</th><th class="py-3 pr-3">Activo</th><th class="py-3 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y">
                @forelse($mappings as $mapping)
                    <tr>
                        <td class="py-3 pr-3">{{ $mapping->assembly_part_number }}</td>
                        <td class="py-3 pr-3 font-semibold text-slate-900">{{ $mapping->rating_part_number }}</td>
                        <td class="py-3 pr-3">{{ $mapping->serial_part_number ?? '—' }}</td>
                        <td class="py-3 pr-3">{{ $mapping->shipping_part_number ?? '—' }}</td>
                        <td class="py-3 pr-3">{{ $mapping->inner_part_number ?? '—' }}</td>
                        <td class="py-3 pr-3"><span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-sky-800">{{ $mapping->market }}</span></td>
                        <td class="py-3 pr-3">{{ $mapping->active ? 'Sí' : 'No' }}</td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('rating_assembly_mappings.edit', $mapping) }}" class="rounded-xl border px-3 py-2 hover:shadow">Editar</a>
                                <form method="POST" action="{{ route('rating_assembly_mappings.toggle', $mapping) }}">@csrf
                                    <button class="rounded-xl bg-slate-900 px-3 py-2 text-white hover:bg-slate-800">{{ $mapping->active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-slate-500">El catálogo está listo para capturar relaciones manualmente o importar un Excel.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $mappings->links() }}</div>
</div>
@endsection
