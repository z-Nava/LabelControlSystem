@extends('layouts.app', ['title' => 'Nuevo template ZPL'])
@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-semibold">Nuevo template ZPL</h1>
    <form class="mt-6 space-y-4" method="POST" action="{{ route('label_templates.store') }}">
        @include('label_templates._form')
        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500">Guardar</button>
    </form>
</div>
@endsection
