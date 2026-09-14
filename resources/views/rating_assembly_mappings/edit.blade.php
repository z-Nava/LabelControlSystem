@extends('layouts.app', ['title' => 'Editar relación Rating y Ensamble'])

@section('content')
<div class="mx-auto max-w-4xl rounded-2xl bg-white p-6 shadow">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Editar relación Rating y Ensamble</h1>
            <p class="mt-1 text-sm text-slate-600">La combinación NP Rating, ensamble 018 y mercado debe ser única.</p>
        </div>
        <a href="{{ route('rating_assembly_mappings.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    <form method="POST" action="{{ route('rating_assembly_mappings.update', $mapping) }}" class="mt-6 space-y-5">
        @method('PUT')
        @include('rating_assembly_mappings._form')
        <button class="rounded-xl bg-red-600 px-4 py-2 font-semibold text-white transition hover:bg-red-500">Actualizar</button>
    </form>
</div>
@endsection
