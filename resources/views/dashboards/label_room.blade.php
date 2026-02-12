@extends('layouts.app', ['title' => 'Dashboard Label Room'])

@section('content')
    <div class="bg-white rounded-2xl shadow p-6">
        <h1 class="text-2xl font-semibold text-slate-900">Label Room</h1>
        <p class="text-slate-600 mt-1">
            Operación rápida: requisiciones, impresión y retrabajos.
        </p>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="#" class="rounded-2xl bg-red-600 text-white p-6 hover:bg-red-500 transition">
                <div class="text-lg font-semibold">Nueva requisición de Etiquetas</div>
                <div class="text-sm opacity-90 mt-1">Rating / Serial / Shipping</div>
            </a>

            <a href="{{ route('master_requests.create') }}" class="rounded-2xl bg-slate-900 text-white p-6 hover:bg-slate-800 transition">
                <div class="text-lg font-semibold">Nueva requisición Master</div>
                <div class="text-sm opacity-90 mt-1">Folios, parciales, std pack</div>
            </a>

            <a href="{{ route('master_requests.index') }}" class="rounded-2xl border p-6 hover:shadow transition">
                <div class="text-lg font-semibold">Requisiciones pendientes</div>
                <div class="text-sm text-slate-600 mt-1">Retomar impresión de requisiciones guardadas</div>
            </a>

            <a href="{{ route('master_reprints.search') }}" class="rounded-2xl border p-6 hover:shadow transition">
                <div class="text-lg font-semibold">Reimprimir / Retrabajo</div>
                <div class="text-sm text-slate-600 mt-1">Vista general por job para hojas master</div>
            </a>

            <a href="#" class="rounded-2xl border p-6 hover:shadow transition">
                <div class="text-lg font-semibold">Entregas / Recepción</div>
                <div class="text-sm text-slate-600 mt-1">Cerrar requisiciones y evitar reclamos</div>
            </a>

            <a href="{{ route('oracle_jobs.index') }}"
            class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Consultar Jobs</div>
                <div class="text-sm text-slate-600 mt-1">
                    Buscar jobs para requisiciones
                </div>
            </a>

            <a href="{{ route('oracle_jobs.import_view') }}"
            class="rounded-2xl bg-red-600 text-white p-5 hover:bg-red-500 transition">
                <div class="font-semibold">Cargar Excel Oracle</div>
                <div class="text-sm opacity-90 mt-1">
                    Actualizar información de jobs
                </div>
            </a>
        </div>
    </div>
@endsection
