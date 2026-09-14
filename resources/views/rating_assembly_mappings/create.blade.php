@extends('layouts.app', ['title' => 'Nueva relación Rating y Ensamble'])

@section('content')
<div class="mx-auto max-w-4xl rounded-2xl bg-white p-6 shadow">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Nueva relación Rating y Ensamble</h1>
            <p class="mt-1 text-sm text-slate-600">Asocia el NP Rating con el ensamble de empaque 018 y su mercado. Agrega los NP Serial, Shipping e Inner cuando apliquen.</p>
        </div>
        <a href="{{ route('rating_assembly_mappings.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    <form method="POST" action="{{ route('rating_assembly_mappings.store') }}" class="mt-6 space-y-5">
        @include('rating_assembly_mappings._form')
        <button class="rounded-xl bg-red-600 px-4 py-2 font-semibold text-white transition hover:bg-red-500">Guardar</button>
    </form>
</div>
@endsection
