@extends('layouts.kiosk', ['title' => 'Consultar Job en Oracle'])

@section('content')
<div class="space-y-6">
    <section class="rounded-3xl bg-white p-6 shadow">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">Solo consulta</p>
                <h1 class="mt-2 text-3xl font-semibold text-slate-900">Consultar Job en Oracle</h1>
                <p class="mt-2 text-slate-600">La información corresponde a los Oracle Jobs importados en el sistema.</p>
            </div>
            <a href="{{ route('kiosk.dashboard') }}" class="rounded-xl border px-4 py-2 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">Volver al kiosko</a>
        </div>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="GET" action="{{ route('kiosk.oracle_jobs.lookup') }}" class="mt-6 flex flex-col gap-3 sm:flex-row">
            <div class="flex-1">
                <label for="job_number" class="sr-only">Número de Job</label>
                <input
                    id="job_number"
                    name="job_number"
                    value="{{ old('job_number', $jobNumber) }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-4 text-xl uppercase focus:outline-none focus:ring-2 focus:ring-blue-600"
                    placeholder="Escribe o escanea el Job"
                    maxlength="40"
                    pattern="[0-9A-Za-z\-]+"
                    autocomplete="off"
                    autofocus
                    required
                />
            </div>
            <button type="submit" class="rounded-2xl bg-blue-600 px-7 py-4 text-lg font-semibold text-white transition hover:bg-blue-500">Consultar</button>
        </form>
    </section>

    @if($hasSearched && !$job)
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
            No se encontró el Job <span class="font-semibold">{{ $jobNumber }}</span> en la información cargada en el sistema.
        </div>
    @elseif($job)
        <section class="rounded-3xl bg-white p-6 shadow">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-wide text-slate-500">Job</p>
                    <h2 class="text-3xl font-semibold text-slate-900">{{ $job->job_number }}</h2>
                </div>
                <span class="w-fit rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                    {{ $job->job_status ?: 'Estatus no disponible' }}
                </span>
            </div>

            <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    'Assembly' => $job->assembly,
                    'Descripción' => $job->part_description,
                    'Línea / local' => $job->line,
                    'Cantidad del Job' => $job->job_qty,
                    'Cantidad completada' => $job->qty_completed,
                    'Cantidad restante' => $job->quantity_remainder,
                    'Custom PO' => $job->ttl_cust_po,
                    'Destino' => $job->ship_code,
                    'Revisión BOM' => $job->bom_revision,
                ] as $label => $value)
                    <div class="rounded-2xl border bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 break-words text-lg font-semibold text-slate-900">{{ filled($value) ? $value : '—' }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                <p>Última actualización del Job: <span class="font-semibold text-slate-800">{{ $job->last_update_date?->format('d/m/Y H:i') ?? 'No disponible' }}</span></p>
                <p class="mt-1">Importado al sistema: <span class="font-semibold text-slate-800">{{ $job->imported_at?->format('d/m/Y H:i') ?? 'No disponible' }}</span></p>
            </div>
        </section>
    @endif
</div>
@endsection
