@extends('layouts.app', ['title' => 'Nuevo mapeo Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6 max-w-3xl">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-slate-900">Nuevo mapeo · {{ $typeLabel }}</h1>
        <a href="{{ route('master_model_mappings.index', $type) }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('master_model_mappings.store', $type) }}">
        @include('master_model_mappings._form')

        <div class="pt-2">
            <button class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">
                Guardar
            </button>
        </div>
    </form>
</div>
@endsection
