@extends('layouts.app', ['title' => 'Locals by Line'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Locals by Oracle Line</h1>
            <p class="text-slate-600 mt-1">Catálogo para mapear STOCK_LOCATOR (línea Oracle) contra SUBINVENTORY (local).</p>
        </div>

        <a href="{{ route('stock_locators.create') }}"
           class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">
            + Nuevo mapeo
        </a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form class="mt-5 flex gap-2" method="GET" action="{{ route('stock_locators.index') }}">
        <input name="q" value="{{ $search }}"
               class="w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="Buscar por stock locator o local..." />
        <button class="rounded-xl bg-slate-900 text-white px-4 py-2 hover:bg-slate-800 transition">
            Buscar
        </button>
    </form>

    <div class="mt-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">STOCK_LOCATOR</th>
                    <th class="py-3 pr-3">SUBINVENTORY (Local)</th>
                    <th class="py-3 pr-3">Activo</th>
                    <th class="py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($stockLocators as $stockLocator)
                    <tr>
                        <td class="py-3 pr-3 font-semibold text-slate-900">{{ $stockLocator->stock_locator }}</td>
                        <td class="py-3 pr-3">{{ $stockLocator->subinventory }}</td>
                        <td class="py-3 pr-3">
                            @if($stockLocator->active)
                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-green-800">Sí</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-slate-700">No</span>
                            @endif
                        </td>
                        <td class="py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('stock_locators.edit', $stockLocator) }}"
                                   class="rounded-xl border px-3 py-2 hover:shadow transition">
                                    Editar
                                </a>

                                <form method="POST" action="{{ route('stock_locators.toggle', $stockLocator) }}">
                                    @csrf
                                    <button class="rounded-xl bg-slate-900 text-white px-3 py-2 hover:bg-slate-800 transition">
                                        Toggle
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-slate-500">
                            No hay mapeos registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $stockLocators->links() }}
    </div>
</div>
@endsection
