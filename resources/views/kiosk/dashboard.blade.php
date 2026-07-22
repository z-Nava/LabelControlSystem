@extends('layouts.kiosk', ['title' => 'Kiosko de requisiciones'])

@section('content')
<div class="space-y-6">
    @if(session('kiosk_receipt'))
        @php($receipt = session('kiosk_receipt'))
        <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Solicitud enviada</p>
                    <h2 class="mt-1 text-2xl font-semibold text-emerald-950">{{ $receipt['type'] }} #{{ $receipt['request_id'] }}</h2>
                    <p class="mt-2 text-emerald-800">Label Room ya puede consultarla como pendiente.</p>
                </div>
                <div class="rounded-2xl bg-white px-5 py-3 text-sm text-emerald-800 shadow-sm">
                    Registrada: {{ $receipt['created_at'] }}
                </div>
            </div>
        </section>
    @elseif(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="rounded-3xl bg-slate-900 p-7 text-white shadow-lg">
        <p class="text-sm font-semibold uppercase tracking-wider text-red-300">Kiosko de Producción</p>
        <h1 class="mt-2 text-3xl font-semibold">¿Qué necesitas solicitar?</h1>
        <p class="mt-2 text-slate-300">Selecciona una opción. Este acceso permite registrar requisiciones y consultar Jobs.</p>
    </section>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <a href="{{ route('kiosk.master_requests.create') }}" class="group rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-0.5 hover:border-red-300 hover:shadow-lg">
            <div class="text-sm font-semibold uppercase tracking-wide text-red-600">Master</div>
            <h2 class="mt-3 text-2xl font-semibold text-slate-900">Requisición Master</h2>
            <p class="mt-2 text-slate-600">Solicita hojas Master para ensamble, empaque, baterías, motores o moldeo.</p>
            <div class="mt-6 font-semibold text-red-600 group-hover:text-red-500">Crear requisición →</div>
        </a>

        <a href="{{ route('kiosk.label_requests.create') }}" class="group rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-0.5 hover:border-red-300 hover:shadow-lg">
            <div class="text-sm font-semibold uppercase tracking-wide text-red-600">Serial / Rating</div>
            <h2 class="mt-3 text-2xl font-semibold text-slate-900">Requisición de etiquetas</h2>
            <p class="mt-2 text-slate-600">Solicita etiquetas por Job, Label PN, estándar y cantidad requerida.</p>
            <div class="mt-6 font-semibold text-red-600 group-hover:text-red-500">Crear requisición →</div>
        </a>

        <a href="{{ route('kiosk.dummy_requests.create') }}" class="group rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-0.5 hover:border-red-300 hover:shadow-lg">
            <div class="text-sm font-semibold uppercase tracking-wide text-red-600">Dummy QR</div>
            <h2 class="mt-3 text-2xl font-semibold text-slate-900">Requisición Dummy QR</h2>
            <p class="mt-2 text-slate-600">Solicita dummys de primera vez o retrabajo con consecutivo controlado.</p>
            <div class="mt-6 font-semibold text-red-600 group-hover:text-red-500">Crear requisición →</div>
        </a>

        <a href="{{ route('kiosk.oracle_jobs.index') }}" class="group rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg">
            <div class="text-sm font-semibold uppercase tracking-wide text-blue-600">Solo consulta</div>
            <h2 class="mt-3 text-2xl font-semibold text-slate-900">Consultar Job en Oracle</h2>
            <p class="mt-2 text-slate-600">Consulta la información de un Job cargada actualmente en el sistema.</p>
            <div class="mt-6 font-semibold text-blue-600 group-hover:text-blue-500">Consultar Job →</div>
        </a>
    </div>
</div>
@endsection
