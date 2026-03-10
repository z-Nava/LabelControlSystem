@extends('layouts.app', ['title' => 'Editar print profile'])
@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-semibold">Editar print profile</h1>
    <form class="mt-6 space-y-4" method="POST" action="{{ route('label_print_profiles.update', $profile) }}">
        @method('PUT')
        @include('label_print_profiles._form', ['profile' => $profile])
        <button class="w-full rounded-xl bg-slate-900 text-white py-3 font-semibold hover:bg-slate-800">Actualizar</button>
    </form>
</div>
@endsection
