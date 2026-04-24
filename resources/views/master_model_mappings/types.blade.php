@extends('layouts.app', ['title' => 'Modelos Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">CRUD Master: Selección de modelo</h1>
        <p class="text-slate-600 mt-1">Selecciona el tipo de hoja master para administrar su catálogo NP/SKU.</p>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($types as $type)
            <a href="{{ route('master_model_mappings.index', $type) }}" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">{{ \App\Models\MasterModelMapping::labelForType($type) }}</div>
                <div class="text-sm text-slate-600 mt-1">Abrir CRUD de {{ strtolower(\App\Models\MasterModelMapping::labelForType($type)) }}.</div>
            </a>
        @endforeach
    </div>
</div>
@endsection
