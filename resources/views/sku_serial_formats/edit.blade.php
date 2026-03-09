@extends('layouts.app', ['title' => 'Editar formato serial'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Editar formato serial</h1>
        <a href="{{ route('sku_serial_formats.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('sku_serial_formats.update', $format) }}">
        @method('PUT')
        @include('sku_serial_formats._form', ['format' => $format])
        <button class="w-full rounded-xl bg-slate-900 text-white py-3 font-semibold hover:bg-slate-800 transition">Actualizar</button>
    </form>
</div>
@endsection
