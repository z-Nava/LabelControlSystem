@extends('layouts.app', ['title' => 'Editar SKU'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Editar SKU</h1>
        <a href="{{ route('label_skus.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('label_skus.update', $labelSku) }}">
        @method('PUT')
        @include('label_skus._form', ['labelSku' => $labelSku])

        <button class="w-full rounded-xl bg-slate-900 text-white py-3 font-semibold hover:bg-slate-800 transition">
            Actualizar
        </button>
    </form>
</div>
@endsection
