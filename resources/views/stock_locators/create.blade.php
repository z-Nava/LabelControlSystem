@extends('layouts.app', ['title' => 'Nuevo mapeo Local/Línea'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Nuevo mapeo Local/Línea</h1>
        <a href="{{ route('stock_locators.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('stock_locators.store') }}">
        @include('stock_locators._form')

        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">
            Guardar
        </button>
    </form>
</div>
@endsection
