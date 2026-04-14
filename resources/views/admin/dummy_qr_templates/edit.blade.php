@extends('layouts.app', ['title' => 'Editar template Dummy QR'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-semibold">Editar template Dummy QR</h1>
    <form class="mt-6 space-y-4" method="POST" action="{{ route('admin.dummy_qr_templates.update', $template) }}">
        @method('PUT')
        @include('admin.dummy_qr_templates._form', ['template' => $template])
        <button class="w-full rounded-xl bg-slate-900 text-white py-3 font-semibold hover:bg-slate-800">Actualizar</button>
    </form>
</div>
@endsection
