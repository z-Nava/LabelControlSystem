@extends('layouts.kiosk', ['title' => 'Nueva requisición de etiquetas LPK'])

@section('content')
@php
    $oldLabelGroups = old('lpk_label_groups', [[
        'label_type' => 'serial',
        'part_number' => '',
        'items' => [['job_number' => '', 'model' => '', 'quantity' => '']],
    ]]);
    $oldShippingGroups = old('lpk_shipping_groups', []);
@endphp

<div class="space-y-6">
    @include('kiosk.partials.request-guide', [
        'title' => 'Crear requisición de etiquetas LPK',
        'description' => 'Agrupa cada etiqueta física por tipo y NP, y agrega debajo todos sus modelos y Jobs.',
        'steps' => [
            ['title' => 'Identifica la operación', 'description' => 'Confirma fecha, semana, línea, turno y líder.'],
            ['title' => 'Agrupa las etiquetas', 'description' => 'Crea un grupo por tipo y NP; no repitas el NP para agregar otro modelo.'],
            ['title' => 'Revisa y envía', 'description' => 'Valida todos los Jobs en Oracle antes de enviar a Label Room.'],
        ],
        'preparationItems' => [
            'NP de cada etiqueta física.',
            'Modelo, Job y cantidad para Serial, Rating e Inner.',
            'NP, cantidad, PO, destino y lista de Modelo/Job para cada Shipping.',
            'Shipping es información de requisición; el sistema no genera la etiqueta física Shipping.',
        ],
    ])

    @include('kiosk.partials.form-errors')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <form id="kioskLpkLabelRequestCreate"
              data-lookup-url="{{ route('kiosk.lpk_label_requests.lookup_job') }}"
              class="min-w-0 space-y-5"
              method="POST"
              action="{{ route('kiosk.lpk_label_requests.store') }}">
            @csrf

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="text-base font-semibold text-slate-900">1) Datos generales</div>
                    <div class="mt-1 text-sm text-slate-500">Identifica la requisición y la operación que la solicita.</div>
                </div>

                <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label for="requestDate" class="text-sm font-medium text-slate-700">Fecha</label>
                        <input id="requestDate" type="date" name="request_date" value="{{ old('request_date', $defaultDate) }}" max="{{ $defaultDate }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                    </div>

                    <div>
                        <label for="requestWeek" class="text-sm font-medium text-slate-700">Semana</label>
                        <input id="requestWeek" type="number" name="week" min="1" max="53" value="{{ old('week', $defaultWeek) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                    </div>

                    <div>
                        <label for="leaderName" class="text-sm font-medium text-slate-700">Líder</label>
                        <input id="leaderName" type="text" name="leader_name" value="{{ old('leader_name') }}" minlength="3" maxlength="120" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                    </div>

                    <div>
                        <label for="lineTypeFilter" class="text-sm font-medium text-slate-700">Tipo de línea</label>
                        <select id="lineTypeFilter" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                            <option value="">Todos los tipos</option>
                            @foreach($lines->pluck('line_type')->filter()->unique()->sort() as $lineType)
                                <option value="{{ $lineType }}">{{ $lineType }}</option>
                            @endforeach
                        </select>
                        <p id="lineTypeHint" class="mt-2 text-xs text-slate-500">Filtra las líneas activas por tipo.</p>
                    </div>

                    <div>
                        <label for="lineSelect" class="text-sm font-medium text-slate-700">Línea</label>
                        <select id="lineSelect" name="line_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                            <option value="">Selecciona una línea</option>
                            @foreach($lines as $line)
                                <option value="{{ $line->id }}" data-line-type="{{ $line->line_type }}" @selected((string) old('line_id', $kioskUser->production_line_id) === (string) $line->id)>
                                    {{ $line->code }} · {{ $line->line_type }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="shiftSelect" class="text-sm font-medium text-slate-700">Turno</label>
                        <select id="shiftSelect" name="shift_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                            <option value="">Selecciona un turno</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}" @selected((string) old('shift_id', $kioskUser->shift_id) === (string) $shift->id)>
                                    {{ $shift->code }} · {{ $shift->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-base font-semibold text-slate-900">2) Serial, Rating e Inner</div>
                        <div class="mt-1 max-w-3xl text-sm text-slate-500">Cada tarjeta representa un NP de etiqueta física. Dentro agrega todos los modelos y Jobs que utilizarán ese NP.</div>
                    </div>
                    <button id="addLpkLabelGroup" type="button" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">+ Agregar NP</button>
                </div>

                <div class="border-b border-blue-200 bg-blue-50 px-5 py-3 text-sm text-blue-900">
                    Un Job repetido entre Serial, Rating e Inner reserva una sola vez la cantidad mayor capturada.
                </div>

                <div id="lpkLabelGroups" class="space-y-4 p-5">
                    @foreach($oldLabelGroups as $groupIndex => $group)
                        <article class="lpk-label-group rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[180px_minmax(0,1fr)_auto] sm:items-end">
                                <div>
                                    <label class="text-sm font-semibold text-slate-800">Tipo</label>
                                    <select data-field="label_type" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                                        @foreach(['serial' => 'Serial', 'rating' => 'Rating', 'inner' => 'Inner'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($group['label_type'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-slate-800">NP de la etiqueta</label>
                                    <input data-field="part_number" type="text" value="{{ $group['part_number'] ?? '' }}" maxlength="80" required placeholder="Ej: 950410000" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-red-600" />
                                </div>
                                <button type="button" class="remove-lpk-label-group inline-flex min-h-11 items-center justify-center rounded-xl border border-red-200 px-3 text-sm font-semibold text-red-700 hover:bg-red-50">Quitar NP</button>
                            </div>

                            <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                <div class="hidden grid-cols-[minmax(130px,0.8fr)_minmax(150px,1fr)_110px_auto] gap-2 border-b border-slate-200 bg-slate-100 px-3 py-2 text-xs font-semibold uppercase text-slate-500 md:grid">
                                    <span>Job</span><span>Modelo</span><span>Cantidad</span><span></span>
                                </div>
                                <div data-items class="divide-y divide-slate-200">
                                    @foreach(($group['items'] ?? []) as $item)
                                        <div class="lpk-label-item grid grid-cols-1 gap-2 p-3 md:grid-cols-[minmax(130px,0.8fr)_minmax(150px,1fr)_110px_auto] md:items-start">
                                            <div>
                                                <label class="text-xs font-semibold text-slate-500 md:hidden">Job</label>
                                                <input data-field="job_number" type="text" value="{{ $item['job_number'] ?? '' }}" maxlength="40" pattern="^[0-9A-Za-z\-]+$" required placeholder="Job" class="lpk-job-input w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-red-600" />
                                                <p data-job-status class="mt-1 text-xs text-slate-500">Pendiente de validar.</p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-slate-500 md:hidden">Modelo</label>
                                                <input data-field="model" type="text" value="{{ $item['model'] ?? '' }}" maxlength="80" placeholder="Valida el Job" class="lpk-model-input w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-red-600" />
                                                <p data-model-status class="mt-1 text-xs text-slate-500">Se consultará en Master Model Mapping.</p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-slate-500 md:hidden">Cantidad</label>
                                                <input data-field="quantity" type="number" value="{{ $item['quantity'] ?? '' }}" min="1" max="100000" required placeholder="Cant." class="lpk-item-quantity w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                            </div>
                                            <button type="button" class="remove-lpk-label-item inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <button type="button" class="add-lpk-label-item mt-3 inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">+ Agregar Modelo / Job</button>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-amber-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-amber-200 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-base font-semibold text-slate-900">3) Shipping LPK</div>
                        <div class="mt-1 max-w-3xl text-sm text-slate-500">Cada tarjeta es un NP Shipping con cantidad, PO y destino propios. Sus modelos y Jobs se conservan como información de la requisición.</div>
                    </div>
                    <button id="addLpkShippingGroup" type="button" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">+ Agregar Shipping</button>
                </div>

                <div class="border-b border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900">
                    La cantidad pertenece al grupo completo. Por ejemplo: cantidad 12 con siete modelos significa 12 etiquetas, no 84.
                </div>

                <div id="lpkShippingGroups" class="space-y-4 p-5">
                    @foreach($oldShippingGroups as $group)
                        <article class="lpk-shipping-group rounded-2xl border border-amber-200 bg-amber-50/40 p-4">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
                                <div>
                                    <label class="text-sm font-semibold text-slate-800">NP Shipping</label>
                                    <input data-field="part_number" type="text" value="{{ $group['part_number'] ?? '' }}" maxlength="80" required placeholder="Ej: 950143000" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-amber-600" />
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-slate-800">Cantidad total</label>
                                    <input data-field="quantity" type="number" value="{{ $group['quantity'] ?? '' }}" min="1" max="100000" required placeholder="Ej: 12" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-600" />
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-slate-800">PO</label>
                                    <input data-field="po_number" type="text" value="{{ $group['po_number'] ?? '' }}" maxlength="80" placeholder="Ej: 380086642" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-amber-600" />
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-slate-800">Destino</label>
                                    <input data-field="destination" type="text" value="{{ $group['destination'] ?? '' }}" maxlength="80" placeholder="Ej: BYHALA MFG" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-amber-600" />
                                </div>
                                <button type="button" class="remove-lpk-shipping-group inline-flex min-h-11 items-center justify-center rounded-xl border border-red-200 px-3 text-sm font-semibold text-red-700 hover:bg-red-50">Quitar Shipping</button>
                            </div>

                            <div class="mt-4 overflow-hidden rounded-xl border border-amber-200 bg-white">
                                <div class="hidden grid-cols-[minmax(130px,0.8fr)_minmax(150px,1fr)_auto] gap-2 border-b border-amber-200 bg-amber-100/60 px-3 py-2 text-xs font-semibold uppercase text-amber-800 md:grid">
                                    <span>Job informativo</span><span>Modelo</span><span></span>
                                </div>
                                <div data-items class="divide-y divide-amber-100">
                                    @foreach(($group['items'] ?? []) as $item)
                                        <div class="lpk-shipping-item grid grid-cols-1 gap-2 p-3 md:grid-cols-[minmax(130px,0.8fr)_minmax(150px,1fr)_auto] md:items-start">
                                            <div>
                                                <label class="text-xs font-semibold text-slate-500 md:hidden">Job</label>
                                                <input data-field="job_number" type="text" value="{{ $item['job_number'] ?? '' }}" maxlength="40" pattern="^[0-9A-Za-z\-]+$" required placeholder="Job" class="lpk-job-input w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-amber-600" />
                                                <p data-job-status class="mt-1 text-xs text-slate-500">Pendiente de validar.</p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-slate-500 md:hidden">Modelo</label>
                                                <input data-field="model" type="text" value="{{ $item['model'] ?? '' }}" maxlength="80" placeholder="Valida el Job" class="lpk-model-input w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-amber-600" />
                                                <p data-model-status class="mt-1 text-xs text-slate-500">Se consultará en Master Model Mapping.</p>
                                            </div>
                                            <button type="button" class="remove-lpk-shipping-item inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <button type="button" class="add-lpk-shipping-item mt-3 inline-flex min-h-10 items-center justify-center rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50">+ Agregar Modelo / Job</button>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <label for="notes" class="text-sm font-semibold text-slate-800">Notas (opcional)</label>
                <textarea id="notes" name="notes" rows="3" maxlength="1000" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">{{ old('notes') }}</textarea>

                <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('kiosk.dashboard') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-red-600 px-6 py-3 font-semibold text-white shadow-sm hover:bg-red-700">Crear requisición LPK</button>
                </div>
            </section>
        </form>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-slate-900 p-5 text-white shadow-sm xl:sticky xl:top-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-red-300">Resumen LPK</p>
            <h2 class="mt-2 text-xl font-semibold">Una tarjeta = una etiqueta física</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div><dt class="text-slate-400">Operación</dt><dd id="lpkPreviewOperation" class="mt-1 font-medium">Pendiente</dd></div>
                <div><dt class="text-slate-400">Grupos Serial / Rating / Inner</dt><dd id="lpkPreviewLabelGroups" class="mt-1 font-medium">1 grupo</dd></div>
                <div><dt class="text-slate-400">Grupos Shipping</dt><dd id="lpkPreviewShippingGroups" class="mt-1 font-medium">Sin Shipping</dd></div>
                <div><dt class="text-slate-400">Reserva por Jobs</dt><dd id="lpkPreviewReservations" class="mt-1 whitespace-pre-line font-medium">Captura Jobs y cantidades</dd></div>
            </dl>
            <div class="mt-5 rounded-xl border border-slate-700 bg-slate-800 p-4 text-xs leading-5 text-slate-300">
                Shipping valida sus Jobs, pero su cantidad no se descuenta de cada Job.
            </div>
        </aside>
    </div>
</div>

<template id="lpkLabelItemTemplate">
    <div class="lpk-label-item grid grid-cols-1 gap-2 p-3 md:grid-cols-[minmax(130px,0.8fr)_minmax(150px,1fr)_110px_auto] md:items-start">
        <div><label class="text-xs font-semibold text-slate-500 md:hidden">Job</label><input data-field="job_number" type="text" maxlength="40" pattern="^[0-9A-Za-z\-]+$" required placeholder="Job" class="lpk-job-input w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-red-600" /><p data-job-status class="mt-1 text-xs text-slate-500">Pendiente de validar.</p></div>
        <div><label class="text-xs font-semibold text-slate-500 md:hidden">Modelo</label><input data-field="model" type="text" maxlength="80" placeholder="Valida el Job" class="lpk-model-input w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-red-600" /><p data-model-status class="mt-1 text-xs text-slate-500">Se consultará en Master Model Mapping.</p></div>
        <div><label class="text-xs font-semibold text-slate-500 md:hidden">Cantidad</label><input data-field="quantity" type="number" min="1" max="100000" required placeholder="Cant." class="lpk-item-quantity w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" /></div>
        <button type="button" class="remove-lpk-label-item inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
    </div>
</template>

<template id="lpkLabelGroupTemplate">
    <article class="lpk-label-group rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[180px_minmax(0,1fr)_auto] sm:items-end">
            <div><label class="text-sm font-semibold text-slate-800">Tipo</label><select data-field="label_type" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600"><option value="serial">Serial</option><option value="rating">Rating</option><option value="inner">Inner</option></select></div>
            <div><label class="text-sm font-semibold text-slate-800">NP de la etiqueta</label><input data-field="part_number" type="text" maxlength="80" required placeholder="Ej: 950410000" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-red-600" /></div>
            <button type="button" class="remove-lpk-label-group inline-flex min-h-11 items-center justify-center rounded-xl border border-red-200 px-3 text-sm font-semibold text-red-700 hover:bg-red-50">Quitar NP</button>
        </div>
        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white"><div class="hidden grid-cols-[minmax(130px,0.8fr)_minmax(150px,1fr)_110px_auto] gap-2 border-b border-slate-200 bg-slate-100 px-3 py-2 text-xs font-semibold uppercase text-slate-500 md:grid"><span>Job</span><span>Modelo</span><span>Cantidad</span><span></span></div><div data-items class="divide-y divide-slate-200"></div></div>
        <button type="button" class="add-lpk-label-item mt-3 inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">+ Agregar Modelo / Job</button>
    </article>
</template>

<template id="lpkShippingItemTemplate">
    <div class="lpk-shipping-item grid grid-cols-1 gap-2 p-3 md:grid-cols-[minmax(130px,0.8fr)_minmax(150px,1fr)_auto] md:items-start">
        <div><label class="text-xs font-semibold text-slate-500 md:hidden">Job</label><input data-field="job_number" type="text" maxlength="40" pattern="^[0-9A-Za-z\-]+$" required placeholder="Job" class="lpk-job-input w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-amber-600" /><p data-job-status class="mt-1 text-xs text-slate-500">Pendiente de validar.</p></div>
        <div><label class="text-xs font-semibold text-slate-500 md:hidden">Modelo</label><input data-field="model" type="text" maxlength="80" placeholder="Valida el Job" class="lpk-model-input w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-amber-600" /><p data-model-status class="mt-1 text-xs text-slate-500">Se consultará en Master Model Mapping.</p></div>
        <button type="button" class="remove-lpk-shipping-item inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
    </div>
</template>

<template id="lpkShippingGroupTemplate">
    <article class="lpk-shipping-group rounded-2xl border border-amber-200 bg-amber-50/40 p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5 xl:items-end">
            <div><label class="text-sm font-semibold text-slate-800">NP Shipping</label><input data-field="part_number" type="text" maxlength="80" required placeholder="Ej: 950143000" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-amber-600" /></div>
            <div><label class="text-sm font-semibold text-slate-800">Cantidad total</label><input data-field="quantity" type="number" min="1" max="100000" required placeholder="Ej: 12" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-600" /></div>
            <div><label class="text-sm font-semibold text-slate-800">PO</label><input data-field="po_number" type="text" maxlength="80" placeholder="Ej: 380086642" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-amber-600" /></div>
            <div><label class="text-sm font-semibold text-slate-800">Destino</label><input data-field="destination" type="text" maxlength="80" placeholder="Ej: BYHALA MFG" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-amber-600" /></div>
            <button type="button" class="remove-lpk-shipping-group inline-flex min-h-11 items-center justify-center rounded-xl border border-red-200 px-3 text-sm font-semibold text-red-700 hover:bg-red-50">Quitar Shipping</button>
        </div>
        <div class="mt-4 overflow-hidden rounded-xl border border-amber-200 bg-white"><div class="hidden grid-cols-[minmax(130px,0.8fr)_minmax(150px,1fr)_auto] gap-2 border-b border-amber-200 bg-amber-100/60 px-3 py-2 text-xs font-semibold uppercase text-amber-800 md:grid"><span>Job informativo</span><span>Modelo</span><span></span></div><div data-items class="divide-y divide-amber-100"></div></div>
        <button type="button" class="add-lpk-shipping-item mt-3 inline-flex min-h-10 items-center justify-center rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50">+ Agregar Modelo / Job</button>
    </article>
</template>
@endsection

@push('scripts')
    @vite('resources/js/pages/kiosk-lpk-label-requests-create.js')
@endpush
