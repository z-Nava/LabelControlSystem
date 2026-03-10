@extends('layouts.app', ['title' => 'Nuevo print profile'])
@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-semibold">Nuevo print profile</h1>
    <form class="mt-6 space-y-4" method="POST" action="{{ route('label_print_profiles.store') }}">
        @include('label_print_profiles._form')
        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500">Guardar</button>
    </form>
</div>
@endsection
