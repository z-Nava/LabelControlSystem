@extends('layouts.app', ['title' => 'Requisiciones de Etiquetas', 'mainClass' => 'max-w-[1600px]'])

@section('content')
@php
    $statusOptions = [
        'active' => 'Pendientes (todas)',
        'requested' => 'Pendiente',
        'in_progress' => 'Requisición impresa',
        'attended' => 'Atendida',
        'completed' => 'Entregada',
        'cancelled' => 'Cancelada',
        'all' => 'Todas',
    ];
@endphp

<div class="rounded-2xl bg-white p-6 shadow">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Requisiciones de etiquetas pendientes</h1>
            <p class="mt-1 text-slate-600">La vista inicia con Pendientes, Impresas y Atendidas. Usa los filtros para consultar el historial.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('dashboard') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Dashboard</a>
            <a href="{{ route('label_requests.create') }}" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">Nueva requisición</a>
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
                {{ number_format($labelRequests->total()) }} {{ $labelRequests->total() === 1 ? 'resultado' : 'resultados' }}
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-12">
            <div class="md:col-span-2 lg:col-span-5">
                <label for="labelRequestSearch" class="text-sm font-medium text-slate-700">Buscar por Job, NP o modelo</label>
                <input id="labelRequestSearch" type="text" name="sku_np" value="{{ $filters['sku_np'] }}" maxlength="80" placeholder="Ej. Job 123456, NP 48-11-1850 o modelo M18" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" />
                <p class="mt-1 text-xs text-slate-500">Busca también NP de Serial/Rating y modelos o herramientas de Shipping.</p>
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

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full min-w-[1260px] table-fixed text-sm">
            <colgroup>
                <col class="w-[92px]" />
                <col class="w-[112px]" />
                <col class="w-[132px]" />
                <col />
                <col class="w-[150px]" />
                <col class="w-[155px]" />
                <col class="w-[150px]" />
                <col class="w-[180px]" />
            </colgroup>
            <thead class="bg-slate-50">
                <tr class="border-b border-slate-200 text-left text-slate-500">
                    <th class="px-4 py-3">Requisición</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Línea / Turno</th>
                    <th class="px-4 py-3">Job / Modelo</th>
                    <th class="px-4 py-3">Tipos</th>
                    <th class="px-4 py-3">Cantidad / Folios</th>
                    <th class="px-4 py-3">Estatus</th>
                    <th class="sticky right-0 z-10 border-l border-slate-200 bg-slate-50 px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($labelRequests as $request)
                    @php
                        $serialPartNumbers = $request->requestedSerialPartNumbers();
                        $ratingPartNumbers = $request->requestedRatingPartNumbers();
                        $shippingItems = $request->requestedShippingItemReferences();
                    @endphp
                    <tr class="group align-top hover:bg-slate-50">
                        <td class="px-4 py-3 font-semibold">
                            <div>#{{ $request->id }}</div>
                            @if($request->isLpk())
                                <span class="mt-1 inline-flex rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800">LPK</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $request->request_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            <div class="font-medium text-slate-800">{{ $request->line?->code ?: 'Sin línea' }}</div>
                            <div class="mt-0.5 text-xs">Turno: {{ $request->shift?->code ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3 break-words">
                            <div class="font-semibold text-slate-900">{{ $request->job_number ?: 'Sin Job' }}</div>
                            <div class="mt-0.5 text-xs text-slate-500">Modelo: {{ $request->model ?: 'Sin modelo' }}</div>

                            @if($request->include_serial && $serialPartNumbers !== [])
                                <div class="mt-2 text-xs text-slate-600" title="{{ implode(', ', $serialPartNumbers) }}">
                                    <span class="font-semibold text-slate-700">NP Serial:</span>
                                    {{ implode(', ', array_slice($serialPartNumbers, 0, 2)) }}
                                    @if(count($serialPartNumbers) > 2)
                                        <span class="ml-1 inline-flex rounded-full bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-600">+{{ count($serialPartNumbers) - 2 }}</span>
                                    @endif
                                </div>
                            @endif

                            @if($request->include_rating && $ratingPartNumbers !== [])
                                <div class="mt-1 text-xs text-slate-600" title="{{ implode(', ', $ratingPartNumbers) }}">
                                    <span class="font-semibold text-slate-700">NP Rating:</span>
                                    {{ implode(', ', array_slice($ratingPartNumbers, 0, 2)) }}
                                    @if(count($ratingPartNumbers) > 2)
                                        <span class="ml-1 inline-flex rounded-full bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-600">+{{ count($ratingPartNumbers) - 2 }}</span>
                                    @endif
                                </div>
                            @endif

                            @if($request->isLpk() && $request->include_shipping && $shippingItems !== [])
                                <div class="mt-1 text-xs text-amber-700" title="{{ implode(', ', $shippingItems) }}">
                                    <span class="font-semibold text-amber-800">Shipping:</span>
                                    {{ implode(', ', array_slice($shippingItems, 0, 2)) }}
                                    @if(count($shippingItems) > 2)
                                        <span class="ml-1 inline-flex rounded-full bg-amber-100 px-1.5 py-0.5 font-semibold text-amber-800">+{{ count($shippingItems) - 2 }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ implode(' + ', $request->requestedLabelTypes()) ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">General: {{ number_format($request->quantity_requested) }}</div>
                            @if($request->include_shipping)
                                <div class="text-xs text-slate-500">Shipping: {{ number_format($request->shipping_quantity ?? $request->quantity_requested) }}</div>
                            @endif
                            <div class="text-xs text-slate-500">
                                {{ $request->folio_start !== null ? $request->folio_start.' – '.$request->folio_end : 'Sin folios' }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full border px-2 py-1 text-xs font-semibold {{ $request->status_badge_classes }}">{{ $request->status_label }}</span>
                        </td>
                        <td class="sticky right-0 z-10 border-l border-slate-200 bg-white px-4 py-3 group-hover:bg-slate-50">
                            <a href="{{ route('label_requests.show', $request) }}" class="mb-2 inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Ver detalle</a>
                            @include('label_requests.partials.workflow-actions', ['labelRequest' => $request, 'compact' => true])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">No hay requisiciones con los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $labelRequests->links() }}</div>
</div>
@endsection
