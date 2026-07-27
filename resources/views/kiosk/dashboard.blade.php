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

    <section class="overflow-hidden rounded-3xl bg-slate-900 text-white shadow-lg">
        <div class="flex flex-col gap-5 px-6 py-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-300">Kiosko de Producción</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight lg:text-4xl">¿Qué necesitas solicitar?</h1>
                <p class="mt-2 text-slate-300">Elige una opción para comenzar. Tu perfil ya está vinculado a esta requisición.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 lg:max-w-xl lg:justify-end">
                <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white">
                    {{ $kioskUser->name }} · #{{ $kioskUser->employee_no }}
                </span>
                <span class="rounded-full bg-red-500/20 px-4 py-2 text-sm font-semibold text-red-100">
                    {{ $kioskUser->productionLine->code }}
                </span>
                <span class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-slate-100">
                    {{ $kioskUser->shift->name }}
                </span>
                <span class="rounded-full bg-blue-500/20 px-4 py-2 text-sm font-semibold text-blue-100">
                    {{ $kioskUser->position_label }}
                </span>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('kiosk.master_requests.create') }}" class="group flex min-h-64 flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-red-300 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-600" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h8.5L19.25 7.5v12A1.75 1.75 0 0 1 17.5 21h-11a1.75 1.75 0 0 1-1.75-1.75V5.5A1.75 1.75 0 0 1 6.5 3.75H7Z" />
                    <path stroke-linecap="round" d="M8.5 12h7M8.5 16h7" />
                </svg>
            </div>
            <div class="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-red-600">Master</div>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Requisición Master</h2>
            <p class="mt-2 leading-6 text-slate-600">Hojas Master para ensamble, empaque, baterías, motores o moldeo.</p>
            <div class="mt-auto pt-5 font-semibold text-red-600 group-hover:text-red-500">Crear requisición →</div>
        </a>

        <a href="{{ route('kiosk.label_requests.create') }}" class="group flex min-h-64 flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-red-300 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-600" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 7.5v9A1.75 1.75 0 0 0 6.5 18.25h11a1.75 1.75 0 0 0 1.75-1.75v-9A1.75 1.75 0 0 0 17.5 5.75h-11A1.75 1.75 0 0 0 4.75 7.5Z" />
                    <path stroke-linecap="round" d="M8 9v6M11 9v6M15 9v6" />
                </svg>
            </div>
            <div class="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-red-600">Serial / Rating</div>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Requisición de etiquetas</h2>
            <p class="mt-2 leading-6 text-slate-600">Etiquetas por Job, Label PN, estándar y cantidad requerida.</p>
            <div class="mt-auto pt-5 font-semibold text-red-600 group-hover:text-red-500">Crear requisición →</div>
        </a>

        <a href="{{ route('kiosk.dummy_requests.create') }}" class="group flex min-h-64 flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-red-300 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-600" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M5 5h5v5H5zM14 5h5v5h-5zM5 14h5v5H5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 14h2v2h3v3h-5v-2M19 14v1" />
                </svg>
            </div>
            <div class="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-red-600">Dummy QR</div>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Requisición Dummy QR</h2>
            <p class="mt-2 leading-6 text-slate-600">Dummys de primera vez o reimpresión con consecutivo controlado.</p>
            <div class="mt-auto pt-5 font-semibold text-red-600 group-hover:text-red-500">Crear requisición →</div>
        </a>

        <a href="{{ route('kiosk.oracle_jobs.index') }}" class="group flex min-h-64 flex-col rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-blue-300 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="10.5" cy="10.5" r="5.75" />
                    <path stroke-linecap="round" d="m15 15 4.25 4.25" />
                </svg>
            </div>
            <div class="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">Solo consulta</div>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Consultar Job en Oracle</h2>
            <p class="mt-2 leading-6 text-slate-600">Información de un Job cargada actualmente en el sistema.</p>
            <div class="mt-auto pt-5 font-semibold text-blue-600 group-hover:text-blue-500">Consultar Job →</div>
        </a>
    </div>
</div>
@endsection
