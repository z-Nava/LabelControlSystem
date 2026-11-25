@extends('layouts.app', ['title' => 'Requisiciones de Etiquetas', 'mainClass' => 'max-w-[1600px]'])

@section('content')
<div class="rounded-2xl bg-white p-4 shadow sm:p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Requisiciones de etiquetas</h1>
            <p class="mt-1 text-slate-600">Usa el filtro de estatus para consultar las requisiciones entregadas o canceladas.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Dashboard</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('label_requests.index') }}" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Filtros de búsqueda</h2>
                <p class="text-xs text-slate-500">Combina los campos para acotar las requisiciones mostradas.</p>
            </div>
            <div class="text-xs font-medium text-slate-500">
                {{ number_format($totalResults) }} {{ $totalResults === 1 ? 'resultado' : 'resultados' }}
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-12">
            <div class="md:col-span-2 lg:col-span-5">
                <label for="labelRequestSearch" class="text-sm font-medium text-slate-700">Buscar por Job, NP o modelo</label>
                <input id="labelRequestSearch" type="text" name="sku_np" value="{{ $filters['sku_np'] }}" maxlength="80" placeholder="Ej. Job 123456, NP 48-11-1850 o modelo M18" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" />
                <p class="mt-1 text-xs text-slate-500">Busca también NP y modelos de Serial, Rating, Inner y Shipping.</p>
            </div>

            <div class="lg:col-span-3">
                <label for="labelRequestStatus" class="text-sm font-medium text-slate-700">Estatus</label>
                <select id="labelRequestStatus" name="status" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    @foreach($statusOptions as $status => $label)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label for="labelRequestKind" class="text-sm font-medium text-slate-700">Tipo</label>
                <select id="labelRequestKind" name="request_kind" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    <option value="">Todos</option>
                    <option value="standard" @selected($filters['request_kind'] === 'standard')>Estándar</option>
                    <option value="lpk" @selected($filters['request_kind'] === 'lpk')>LPK</option>
                </select>
            </div>

            <div class="flex items-end gap-2 md:col-span-2 lg:col-span-2">
                <button class="flex-1 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Aplicar</button>
                <a href="{{ route('label_requests.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Limpiar</a>
            </div>

            <div class="lg:col-span-3">
                <label for="labelRequestDateFrom" class="text-sm font-medium text-slate-700">Fecha desde</label>
                <input id="labelRequestDateFrom" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" />
            </div>

            <div class="lg:col-span-3">
                <label for="labelRequestDateTo" class="text-sm font-medium text-slate-700">Fecha hasta</label>
                <input id="labelRequestDateTo" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" />
            </div>

            <div class="lg:col-span-3">
                <label for="labelRequestLine" class="text-sm font-medium text-slate-700">Línea</label>
                <select id="labelRequestLine" name="line_id" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    <option value="">Todas</option>
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}" @selected((string) $filters['line_id'] === (string) $line->id)>{{ $line->code }} · {{ $line->line_type }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-3">
                <label for="labelRequestShift" class="text-sm font-medium text-slate-700">Turno</label>
                <select id="labelRequestShift" name="shift_id" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    <option value="">Todos</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" @selected((string) $filters['shift_id'] === (string) $shift->id)>{{ $shift->code }} · {{ $shift->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div @class(['mt-6 grid gap-4', 'lg:grid-cols-3' => $showActiveBoard])>
        @foreach($columns as $status => $requests)
            <section id="requests-{{ $status }}" aria-labelledby="heading-{{ $status }}" class="min-w-0 rounded-xl border border-slate-200 bg-slate-50">
                <header class="flex items-center gap-2.5 border-b border-slate-200 px-4 py-3">
                    <span aria-hidden="true" @class(['size-2.5 shrink-0 rounded-full', match ($status) {
                        \App\Models\LabelRequest::STATUS_REQUESTED => 'bg-sky-500',
                        \App\Models\LabelRequest::STATUS_IN_PROGRESS => 'bg-violet-500',
                        \App\Models\LabelRequest::STATUS_READY_FOR_DELIVERY => 'bg-amber-500',
                        \App\Models\LabelRequest::STATUS_COMPLETED => 'bg-emerald-500',
                        \App\Models\LabelRequest::STATUS_CANCELLED => 'bg-red-500',
                        default => 'bg-slate-500',
                    }])></span>
                    <h2 id="heading-{{ $status }}" class="font-semibold text-slate-900">{{ $statusOptions[$status] }}</h2>
                    <span class="ml-auto rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-sm font-semibold tabular-nums text-slate-700">{{ number_format($requests->total()) }}</span>
                </header>

                <div @class(['grid items-start gap-3 p-3', 'md:grid-cols-2 xl:grid-cols-3' => ! $showActiveBoard])>
                    @forelse($requests as $row)
                        @include('label_requests.partials.request-card', ['labelRequest' => $row['labelRequest']])
                    @empty
                        <p class="col-span-full rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">No hay requisiciones con los filtros seleccionados.</p>
                    @endforelse
                </div>

                @if($requests->hasPages())
                    <div class="border-t border-slate-200 p-3">
                        {{ $requests->links($showActiveBoard ? 'pagination::simple-tailwind' : 'pagination::tailwind') }}
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</div>
@endsection
