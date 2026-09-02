@extends('layouts.app', ['title' => 'Requisiciones de Etiquetas', 'mainClass' => 'max-w-[1600px]'])

@section('content')
<div class="rounded-2xl bg-white p-6 shadow">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Requisiciones de etiquetas pendientes</h1>
            <p class="mt-1 text-slate-600">La vista inicia con requisiciones Pendientes, En preparación y Listas para entregar. Usa los filtros para consultar el historial.</p>
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
                    <th class="px-4 py-3">Job / Detalle</th>
                    <th class="px-4 py-3">Tipos</th>
                    <th class="px-4 py-3">Cantidad / Folios</th>
                    <th class="px-4 py-3">Estatus</th>
                    <th class="sticky right-0 z-10 border-l border-slate-200 bg-slate-50 px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($labelRequestRows as $row)
                    <tr class="group align-top hover:bg-slate-50">
                        <td class="px-4 py-3 font-semibold">
                            <div>#{{ $row['labelRequest']->id }}</div>
                            @if($row['labelRequest']->isLpk())
                                <span class="mt-1 inline-flex rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800">LPK</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $row['labelRequest']->request_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            <div class="font-medium text-slate-800">{{ $row['labelRequest']->line?->code ?: 'Sin línea' }}</div>
                            <div class="mt-0.5 text-xs">Turno: {{ $row['labelRequest']->shift?->code ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3 break-words">
                            @if($row['hasGroupedLpkDetails'])
                                <div class="font-semibold text-slate-900">{{ $row['lpkProductionJobs']->count() }} Job(s) de producción</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $row['lpkProductionJobs']->take(3)->implode(', ') ?: 'Sólo Shipping' }}@if($row['lpkProductionJobs']->count() > 3) · +{{ $row['lpkProductionJobs']->count() - 3 }}@endif</div>

                                @foreach($row['labelRequest']->lpkLabelGroups->take(3) as $group)
                                    <div class="mt-1 text-xs text-slate-600"><span class="font-semibold">{{ $group->type_label }} {{ $group->part_number }}:</span> {{ $group->items->count() }} renglón(es)</div>
                                @endforeach
                                @if($row['labelRequest']->lpkLabelGroups->count() > 3)
                                    <div class="mt-1 text-xs text-slate-500">+{{ $row['labelRequest']->lpkLabelGroups->count() - 3 }} grupo(s) adicional(es)</div>
                                @endif
                                @foreach($row['labelRequest']->lpkShippingGroups->take(2) as $group)
                                    <div class="mt-1 text-xs text-amber-700"><span class="font-semibold text-amber-800">Shipping {{ $group->part_number }}:</span> {{ number_format($group->quantity) }} etiqueta(s), {{ $group->items->count() }} modelo(s)</div>
                                @endforeach
                                @if($row['labelRequest']->lpkShippingGroups->count() > 2)
                                    <div class="mt-1 text-xs text-amber-700">+{{ $row['labelRequest']->lpkShippingGroups->count() - 2 }} Shipping adicional(es)</div>
                                @endif
                            @else
                                <div class="font-semibold text-slate-900">{{ $row['labelRequest']->job_number ?: 'Sin Job' }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">Modelo general: {{ $row['labelRequest']->model ?: 'Sin modelo' }}</div>

                            @if($row['labelRequest']->include_serial && $row['serialPartNumbers'] !== [])
                                <div class="mt-2 text-xs text-slate-600" title="{{ implode(', ', $row['serialPartNumbers']) }}">
                                    <span class="font-semibold text-slate-700">Serial NP / modelo:</span>
                                    {{ implode(', ', array_slice($row['serialPartNumbers'], 0, 2)) }}
                                    @if(count($row['serialPartNumbers']) > 2)
                                        <span class="ml-1 inline-flex rounded-full bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-600">+{{ count($row['serialPartNumbers']) - 2 }}</span>
                                    @endif
                                </div>
                            @endif

                            @if($row['labelRequest']->include_rating && $row['ratingPartNumbers'] !== [])
                                <div class="mt-1 text-xs text-slate-600" title="{{ implode(', ', $row['ratingPartNumbers']) }}">
                                    <span class="font-semibold text-slate-700">Rating NP / modelo:</span>
                                    {{ implode(', ', array_slice($row['ratingPartNumbers'], 0, 2)) }}
                                    @if(count($row['ratingPartNumbers']) > 2)
                                        <span class="ml-1 inline-flex rounded-full bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-600">+{{ count($row['ratingPartNumbers']) - 2 }}</span>
                                    @endif
                                </div>
                            @endif

                            @if($row['labelRequest']->include_inner)
                                <div class="mt-1 text-xs text-slate-600" title="{{ $row['innerItem'] ?: 'Sin NP capturado' }}">
                                    <span class="font-semibold text-slate-700">Inner NP / modelo:</span>
                                    {{ $row['innerItem'] ?: 'Sin NP capturado' }}
                                </div>
                            @endif

                            @if($row['labelRequest']->include_shipping)
                                <div class="mt-1 text-xs text-amber-700" title="{{ $row['shippingItems'] !== [] ? implode(', ', $row['shippingItems']) : $row['shippingItemSummary'] }}">
                                    <span class="font-semibold text-amber-800">Shipping NP / modelo:</span>
                                    {{ $row['shippingItemSummary'] }}
                                    @if(count($row['shippingItems']) > 2)
                                        <span class="ml-1 inline-flex rounded-full bg-amber-100 px-1.5 py-0.5 font-semibold text-amber-800">+{{ count($row['shippingItems']) - 2 }}</span>
                                    @endif
                                </div>
                            @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ implode(' + ', $row['labelRequest']->requestedLabelTypes()) ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $row['hasGroupedLpkDetails'] ? 'Reserva Jobs' : 'General' }}: {{ number_format($row['labelRequest']->quantity_requested) }}</div>
                            @if($row['hasGroupedLpkDetails'])
                                <div class="text-xs text-slate-500">Shipping: {{ $row['labelRequest']->lpkShippingGroups->count() }} grupo(s)</div>
                            @elseif($row['labelRequest']->include_shipping)
                                <div class="text-xs text-slate-500">Shipping: {{ number_format($row['labelRequest']->shipping_quantity ?? $row['labelRequest']->quantity_requested) }}</div>
                            @endif
                            <div class="text-xs text-slate-500">
                                {{ $row['labelRequest']->folio_start !== null ? $row['labelRequest']->folio_start.' – '.$row['labelRequest']->folio_end : 'Sin folios' }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full border px-2 py-1 text-xs font-semibold {{ $row['labelRequest']->status_badge_classes }}">{{ $row['labelRequest']->status_label }}</span>
                        </td>
                        <td class="sticky right-0 z-10 border-l border-slate-200 bg-white px-4 py-3 group-hover:bg-slate-50">
                            <a href="{{ route('label_requests.show', $row['labelRequest']) }}" class="mb-2 inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Ver detalle</a>
                            @include('label_requests.partials.workflow-actions', ['labelRequest' => $row['labelRequest'], 'compact' => true])
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
