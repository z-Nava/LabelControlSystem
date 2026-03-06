@extends('layouts.app', ['title' => 'Agregar SKU'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Agregar SKU</h1>
        <a href="{{ route('label_skus.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('label_skus.store') }}">
        @include('label_skus._form')
        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">
            Guardar
        </button>
    </form>
</div>
@endsection
