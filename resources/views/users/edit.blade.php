@extends('layouts.app', ['title' => 'Editar usuario'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6 max-w-4xl">
    <h1 class="text-2xl font-semibold text-slate-900">Editar usuario</h1>
    <p class="text-slate-600 mt-1">Actualizar información, roles y estado.</p>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('users.update', $user) }}">
        @method('PUT')
        @include('users._form')

        <div class="pt-2 flex gap-2">
            <button class="rounded-xl bg-red-600 text-white px-4 py-2 font-semibold hover:bg-red-500 transition">Actualizar</button>
            <a href="{{ route('users.index') }}" class="rounded-xl border px-4 py-2 hover:shadow transition">Cancelar</a>
        </div>
    </form>
</div>
@endsection
