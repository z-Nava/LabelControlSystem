@extends('layouts.app', ['title' => 'Editar template Dummy QR'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Dummy QR Templates</p>
            <h1 class="text-2xl font-bold text-slate-900">Editar template Dummy QR</h1>
            <p class="mt-1 text-sm text-slate-500">Actualiza layout, datos base y conexión de impresora para este template.</p>
        </div>
        <a href="{{ route('admin.dummy_qr_templates.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Ver templates
        </a>
    </div>

    <form class="space-y-6" method="POST" action="{{ route('admin.dummy_qr_templates.update', $template) }}">
        @method('PUT')
        @include('admin.dummy_qr_templates._form', ['template' => $template])
        <div class="sticky bottom-4 z-10 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
            <button class="w-full rounded-xl bg-slate-900 py-3 font-semibold text-white hover:bg-slate-800">Actualizar template Dummy QR</button>
        </div>
    </form>
</div>
@endsection
