@extends('layouts.app', ['title' => 'Dashboard Label Room'])

@section('content')
    <div class="bg-white rounded-2xl shadow p-6">
        <div class="border-b border-slate-200 pb-4">
            <h1 class="text-2xl font-semibold text-slate-900">Label Room</h1>
            <p class="text-slate-600 mt-1">
                Operación organizada por área para trabajar más rápido y con menos errores.
            </p>
        </div>

        <div class="mt-6 space-y-8">
            {{-- Masters --}}
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-700 text-sm">M</span>
                    <h2 class="text-lg font-semibold text-slate-900">Masters</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <a href="{{ route('master_requests.create') }}"
                        class="rounded-2xl bg-slate-900 text-white p-6 hover:bg-slate-800 transition">
                        <div class="text-lg font-semibold">Nueva requisición Master</div>
                        <div class="text-sm opacity-90 mt-1">Crear folios, parciales y std pack</div>
                    </a>

                    <a href="{{ route('master_requests.index') }}"
                        class="rounded-2xl border p-6 hover:shadow transition">
                        <div class="text-lg font-semibold">Requisiciones pendientes</div>
                        <div class="text-sm text-slate-600 mt-1">Retomar impresión de requisiciones guardadas</div>
                    </a>

                    <a href="{{ route('master_reprints.search') }}"
                        class="rounded-2xl border p-6 hover:shadow transition">
                        <div class="text-lg font-semibold">Reimprimir / Retrabajo</div>
                        <div class="text-sm text-slate-600 mt-1">Vista general por job para hojas master</div>
                    </a>
                </div>
            </section>

            {{-- Seriales y etiquetas --}}
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-sm">S</span>
                    <h2 class="text-lg font-semibold text-slate-900">Seriales y etiquetas</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <a href="{{ route('label_requests.create') }}" class="rounded-2xl bg-red-600 text-white p-6 hover:bg-red-500 transition">
                        <div class="text-xs font-semibold uppercase tracking-wide opacity-80">Paso 1</div>
                        <div class="text-lg font-semibold mt-1">Nueva requisición de Etiquetas</div>
                        <div class="text-sm opacity-90 mt-1">Captura inicial de Rating / Serial / Shipping</div>
                    </a>

                    <a href="{{ route('label_reworks.search') }}" class="rounded-2xl border p-6 hover:shadow transition">
                        <div class="text-lg font-semibold text-slate-800">Retrabajo etiquetas</div>
                        <div class="text-sm text-slate-600 mt-1">Reimprime seriales y ratings por job</div>
                    </a>

                </div>
            </section>


            {{-- Dummy QR --}}
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-sm">D</span>
                    <h2 class="text-lg font-semibold text-slate-900">Dummy QR</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <a href="{{ route('dummy_requests.create') }}" class="rounded-2xl bg-indigo-600 text-white p-6 hover:bg-indigo-500 transition">
                        <div class="text-xs font-semibold uppercase tracking-wide opacity-80">Paso 1</div>
                        <div class="text-lg font-semibold mt-1">Nueva requisición Dummy QR</div>
                        <div class="text-sm opacity-90 mt-1">Genera lote de dummys con consecutivo único por Job</div>
                    </a>

                    <a href="{{ route('dummy_requests.index') }}" class="rounded-2xl border p-6 hover:shadow transition">
                        <div class="text-lg font-semibold text-slate-800">Historial Dummy QR</div>
                        <div class="text-sm text-slate-600 mt-1">Consulta requisiciones, rangos y estatus por job</div>
                    </a>
                </div>
            </section>

            {{-- Oracle --}}
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-sm">O</span>
                    <h2 class="text-lg font-semibold text-slate-900">Oracle</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
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
            </section>
        </div>
    </div>
@endsection
