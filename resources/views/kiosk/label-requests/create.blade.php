@extends('layouts.kiosk', ['title' => 'Nueva requisición de etiquetas'])

@section('content')
@php
    $oldSerialItems = old('serial_items', [['part_number' => '', 'model' => '']]);
    $oldSerialItems = is_array($oldSerialItems) && $oldSerialItems !== []
        ? $oldSerialItems
        : [['part_number' => '', 'model' => '']];
    $oldRatingItems = old('rating_items', [['part_number' => '', 'model' => '']]);
    $oldRatingItems = is_array($oldRatingItems) && $oldRatingItems !== []
        ? $oldRatingItems
        : [['part_number' => '', 'model' => '']];
@endphp
<div class="space-y-6">
    @include('kiosk.partials.request-guide', [
        'title' => 'Crear requisición de etiquetas',
        'description' => 'Captura únicamente las etiquetas que necesita producción. La vista te indicará qué datos completar y validará el Job antes del envío.',
        'steps' => [
            ['title' => 'Identifica la operación', 'description' => 'Confirma fecha, semana, línea, turno y líder.'],
            ['title' => 'Valida el Job', 'description' => 'Espera la confirmación de Oracle y revisa la disponibilidad.'],
            ['title' => 'Indica las etiquetas', 'description' => 'Selecciona los tipos y captura solamente sus NP.'],
            ['title' => 'Confirma y envía', 'description' => 'Revisa la confirmación final antes de crear la requisición.'],
        ],
    ])

    @include('kiosk.partials.form-errors')

    <div class="mx-auto max-w-7xl">
        <form id="kioskLabelRequestCreate"
              data-lookup-url="{{ route('kiosk.label_requests.lookup_job') }}"
              class="min-w-0 space-y-4"
              method="POST"
              action="{{ route('kiosk.label_requests.store') }}">
            @csrf

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-sm font-bold text-white">1</span>
                    <div>
                        <div class="text-base font-semibold text-slate-900">Identifica la operación</div>
                        <div class="mt-1 text-sm text-slate-500">Confirma los datos prellenados y completa quién solicita las etiquetas.</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label for="requestDate" class="text-sm font-semibold text-slate-700">Fecha <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input id="requestDate" type="date" name="request_date" value="{{ old('request_date', $defaultDate) }}" max="{{ $defaultDate }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        <p class="mt-2 text-xs text-slate-500">No puede ser posterior al día de hoy.</p>
                    </div>

                    <div>
                        <label for="requestWeek" class="text-sm font-semibold text-slate-700">Semana <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input id="requestWeek" type="number" name="week" min="1" max="53" value="{{ old('week', $defaultWeek) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        <p class="mt-2 text-xs text-slate-500">Verifica que corresponda a la fecha seleccionada.</p>
                    </div>

                    <div>
                        <label for="leaderName" class="text-sm font-semibold text-slate-700">Líder <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input id="leaderName" type="text" name="leader_name" value="{{ old('leader_name') }}" minlength="3" maxlength="120" placeholder="Nombre y apellido" autocomplete="name" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        <p class="mt-2 text-xs text-slate-500">Escribe el nombre de la persona responsable.</p>
                    </div>

                    <div>
                        <label for="lineTypeFilter" class="text-sm font-semibold text-slate-700">Tipo de línea <span class="font-normal text-slate-400">(filtro)</span></label>
                        <select id="lineTypeFilter" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                            <option value="">Todos los tipos</option>
                            @foreach($lines->pluck('line_type')->filter()->unique()->sort() as $lineType)
                                <option value="{{ $lineType }}">{{ $lineType }}</option>
                            @endforeach
                        </select>
                        <p id="lineTypeHint" class="mt-2 text-xs text-slate-500">Filtra las líneas activas por tipo.</p>
                    </div>

                    <div>
                        <label for="lineSelect" class="text-sm font-semibold text-slate-700">Línea <span class="text-red-600" aria-hidden="true">*</span></label>
                        <select id="lineSelect" name="line_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                            <option value="">Selecciona una línea</option>
                            @foreach($lines as $line)
                                <option value="{{ $line->id }}"
                                        data-line-type="{{ $line->line_type }}"
                                        @selected((string) old('line_id', $kioskUser->production_line_id) === (string) $line->id)>
                                    {{ $line->code }} · {{ $line->line_type }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="shiftSelect" class="text-sm font-semibold text-slate-700">Turno <span class="text-red-600" aria-hidden="true">*</span></label>
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

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-600 text-sm font-bold text-white">2</span>
                    <div>
                        <div class="text-base font-semibold text-slate-900">Valida el Job y define sus etiquetas</div>
                        <div class="mt-1 text-sm text-slate-500">Captura primero el Job; después selecciona los tipos y completa únicamente sus datos.</div>
                    </div>
                </div>

                <div class="space-y-6 p-5">
                    <div class="border-t border-slate-200 pt-5">
                        <div class="text-sm font-semibold text-slate-800">B. Valida el Job y define las cantidades</div>
                        <p class="mt-1 text-sm text-slate-500">Escribe el Job completo y espera a ver <strong class="text-emerald-700">Job válido</strong> antes de capturar la información de cada etiqueta.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <label for="jobNumber" class="text-sm font-semibold text-slate-700">Job de Empaque <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="jobNumber" type="text" name="job_number" value="{{ old('job_number') }}" maxlength="40" pattern="^[0-9A-Za-z\-]+$" placeholder="Ej: 393383" autocomplete="off" spellcheck="false" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <p id="jobHint" class="mt-2 text-xs text-slate-500">Pendiente de validar en Oracle.</p>
                        </div>

                        <div>
                            <label for="assemblyInfo" class="flex items-center justify-between gap-2 text-sm font-semibold text-slate-700">Assembly del Job <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Automático</span></label>
                            <input id="assemblyInfo" type="text" value="" placeholder="Se mostrará después de validar" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-slate-700" />
                        </div>

                        <div>
                            <label for="modelInput" class="text-sm font-semibold text-slate-700">Modelo general <span class="font-normal text-slate-400">(opcional)</span></label>
                            <input id="modelInput" type="text" name="model" value="{{ old('model') }}" maxlength="80" placeholder="Valida el Job para consultar el modelo" class="mapped-model-input mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <p id="modelMappingHint" class="mt-2 text-xs text-slate-500">Se consultará en Master Model Mapping.</p>
                        </div>

                        <div>
                            <label for="poNumber" class="text-sm font-semibold text-slate-700">PO <span class="font-normal text-slate-400">(Oracle)</span></label>
                            <input id="poNumber" type="text" name="po_number" value="{{ old('po_number') }}" maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+" placeholder="Autollenado desde Oracle" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>

                        <div>
                            <label for="destination" class="text-sm font-semibold text-slate-700">Destino <span class="font-normal text-slate-400">(Oracle)</span></label>
                            <input id="destination" type="text" name="destination" value="{{ old('destination') }}" maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+" placeholder="Autollenado desde Oracle" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>

                        <div>
                            <label for="quantityRequested" class="text-sm font-semibold text-slate-700">Cantidad general <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input id="quantityRequested" type="number" name="quantity_requested" min="1" max="100000" value="{{ old('quantity_requested') }}" placeholder="Ej: 250" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <p id="quantityHint" class="mt-2 text-xs text-slate-500">Primero valida el Job para conocer la disponibilidad.</p>
                            <p class="mt-1 text-xs text-slate-500">Se usa para Serial, Rating e Inner. Shipping conserva su propia cantidad.</p>
                        </div>

                        <div>
                            <label for="shippingQuantity" class="text-sm font-semibold text-slate-700">Cantidad Shipping <span class="font-normal text-slate-400">(solo si aplica)</span></label>
                            <input id="shippingQuantity" type="number" name="shipping_quantity" min="1" max="100000" value="{{ old('shipping_quantity') }}" placeholder="No requerida" @disabled(!old('include_shipping')) class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <p class="mt-1 text-xs text-slate-500">Independiente de la cantidad general; déjala vacía si no solicitas Shipping.</p>
                        </div>
                    </div>

                    <div id="jobCapacitySummary" class="hidden grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm sm:grid-cols-3">
                        <div><span class="block text-xs uppercase text-slate-500">Job Qty</span><strong id="jobQtyValue">—</strong></div>
                        <div><span class="block text-xs uppercase text-slate-500">Ya solicitado</span><strong id="reservedQuantityValue">—</strong></div>
                        <div><span class="block text-xs uppercase text-slate-500">Disponible</span><strong id="availableQuantityValue" class="text-emerald-700">—</strong></div>
                    </div>

                    <div class="border-t border-slate-200 pt-5">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <label class="text-sm font-semibold text-slate-800">A. Selecciona los tipos requeridos <span class="text-red-600" aria-hidden="true">*</span></label>
                            <span class="text-xs font-medium text-slate-500">Puedes elegir más de uno</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">No selecciones un tipo si producción no necesita esa etiqueta.</p>

                        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            @foreach([
                                ['id' => 'includeSerial', 'name' => 'include_serial', 'label' => 'Serial', 'description' => 'Etiqueta normal con uno o varios NP; LabelRoom asignará los folios.'],
                                ['id' => 'includeRating', 'name' => 'include_rating', 'label' => 'Rating', 'description' => 'Nameplate con uno o varios NP para combos.'],
                                ['id' => 'includeInner', 'name' => 'include_inner', 'label' => 'Inner', 'description' => 'Etiqueta interior con la cantidad general.'],
                                ['id' => 'includeShipping', 'name' => 'include_shipping', 'label' => 'Shipping', 'description' => 'Etiqueta con cantidad independiente.'],
                            ] as $type)
                                <label data-label-type-card class="relative flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-red-300 hover:bg-red-50/40">
                                    <input id="{{ $type['id'] }}" type="checkbox" name="{{ $type['name'] }}" value="1" @checked(old($type['name'])) class="mt-0.5 h-6 w-6 rounded border-slate-300 text-red-600 focus:ring-red-600" />
                                    <span class="min-w-0 pr-16">
                                        <span class="block font-medium text-slate-900">{{ $type['label'] }}</span>
                                        <span class="mt-1 block text-sm text-slate-500">{{ $type['description'] }}</span>
                                    </span>
                                    <span data-label-type-state class="absolute right-3 top-3 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-500">Elegir</span>
                                </label>
                            @endforeach
                        </div>
                        <p id="typeHint" class="mt-2 text-xs text-slate-500">Selecciona al menos un tipo.</p>
                    </div>

                    <div class="border-t border-slate-200 pt-5">
                        <div class="text-sm font-semibold text-slate-800">C. Captura el NP de cada etiqueta seleccionada</div>
                        <p class="mt-1 text-sm text-slate-500">Usaremos el modelo encontrado para completar los campos; si no existe un mapeo, podrás capturarlo manualmente.</p>
                    </div>

                    <div id="serialFields" class="space-y-3 rounded-2xl border border-red-100 bg-red-50/40 p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">Serial <span class="text-red-600" aria-hidden="true">*</span></div>
                                <p class="mt-1 text-xs text-slate-500">Agrega una fila por cada NP Serial distinto.</p>
                            </div>
                            <button id="addSerialPartNumber" type="button" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-300 hover:bg-red-50">
                                + Agregar Serial
                            </button>
                        </div>

                        <div id="serialPartNumbers" class="space-y-2">
                            @foreach($oldSerialItems as $index => $serialItem)
                                <div class="serial-part-number-row grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                    <input type="text" name="serial_items[{{ $index }}][part_number]" value="{{ is_array($serialItem) ? ($serialItem['part_number'] ?? '') : $serialItem }}" maxlength="80" placeholder="NP de Serial" autocomplete="off" spellcheck="false" class="serial-part-number-input min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                    <input type="text" name="serial_items[{{ $index }}][model]" value="{{ is_array($serialItem) ? ($serialItem['model'] ?? '') : '' }}" maxlength="80" placeholder="Modelo (opcional)" class="mapped-model-input part-model-input min-w-0 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                    <button type="button" class="remove-serial-part-number inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                                </div>
                            @endforeach
                        </div>
                        <p id="serialItemsHint" class="text-xs text-slate-500">No repitas el mismo NP Serial.</p>
                    </div>

                    <template id="serialPartNumberTemplate">
                        <div class="serial-part-number-row grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                            <input type="text" maxlength="80" placeholder="NP de Serial" autocomplete="off" spellcheck="false" class="serial-part-number-input min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <input type="text" maxlength="80" placeholder="Modelo (opcional)" class="mapped-model-input part-model-input min-w-0 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <button type="button" class="remove-serial-part-number inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                        </div>
                    </template>

                    <div id="ratingFields" class="space-y-3 rounded-2xl border border-violet-100 bg-violet-50/40 p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">Rating <span class="text-red-600" aria-hidden="true">*</span></div>
                                <p class="mt-1 text-xs text-slate-500">Agrega una fila por cada NP Rating distinto.</p>
                            </div>
                            <button id="addRatingPartNumber" type="button" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-300 hover:bg-red-50">
                                + Agregar rating
                            </button>
                        </div>

                        <div id="ratingPartNumbers" class="space-y-2">
                            @foreach($oldRatingItems as $index => $ratingItem)
                                <div class="rating-part-number-row grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                    <input type="text" name="rating_items[{{ $index }}][part_number]" value="{{ is_array($ratingItem) ? ($ratingItem['part_number'] ?? '') : $ratingItem }}" maxlength="80" placeholder="NP de Rating" autocomplete="off" spellcheck="false" class="rating-part-number-input min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                    <input type="text" name="rating_items[{{ $index }}][model]" value="{{ is_array($ratingItem) ? ($ratingItem['model'] ?? '') : '' }}" maxlength="80" placeholder="Modelo (opcional)" class="mapped-model-input part-model-input min-w-0 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                    <button type="button" class="remove-rating-part-number inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                                </div>
                            @endforeach
                        </div>
                        <p id="ratingItemsHint" class="text-xs text-slate-500">No repitas el mismo NP Rating.</p>
                    </div>

                    <template id="ratingPartNumberTemplate">
                        <div class="rating-part-number-row grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                            <input type="text" maxlength="80" placeholder="NP de Rating" autocomplete="off" spellcheck="false" class="rating-part-number-input min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <input type="text" maxlength="80" placeholder="Modelo (opcional)" class="mapped-model-input part-model-input min-w-0 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <button type="button" class="remove-rating-part-number inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                        </div>
                    </template>

                    <div id="innerFields" class="rounded-2xl border border-sky-100 bg-sky-50/50 p-4">
                        <div class="text-sm font-semibold text-slate-800">Inner <span class="text-red-600" aria-hidden="true">*</span></div>
                        <p class="mt-1 text-xs text-slate-500">Captura el NP; el modelo es opcional y puede completarse al validar el Job.</p>
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <input id="innerPartNumber" type="text" name="inner_part_number" value="{{ old('inner_part_number') }}" maxlength="80" placeholder="NP de Inner" autocomplete="off" spellcheck="false" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <input id="innerModel" type="text" name="inner_model" value="{{ old('inner_model') }}" maxlength="80" placeholder="Modelo (opcional)" class="mapped-model-input rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>
                    </div>

                    <div id="shippingFields" class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <div class="text-sm font-semibold text-slate-800">Shipping <span class="text-red-600" aria-hidden="true">*</span></div>
                        <p class="mt-1 text-xs text-slate-500">Captura el NP; la cantidad de Shipping se indica por separado después de validar el Job.</p>
                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <input id="shippingPartNumber" type="text" name="shipping_part_number" value="{{ old('shipping_part_number') }}" maxlength="80" placeholder="NP de Shipping" autocomplete="off" spellcheck="false" class="rounded-xl border border-amber-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-600" />
                            <input id="shippingModel" type="text" name="shipping_model" value="{{ old('shipping_model') }}" maxlength="80" placeholder="Modelo (opcional)" class="mapped-model-input rounded-xl border border-amber-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-600" />
                        </div>
                    </div>

                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-sm font-bold text-white">3</span>
                    <div>
                        <div class="text-base font-semibold text-slate-900">Confirma y envía</div>
                        <div class="mt-1 text-sm text-slate-500">Agrega notas solo si Label Room necesita información adicional.</div>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <label for="notesInput" class="text-sm font-semibold text-slate-700">Notas <span class="font-normal text-slate-400">(opcional)</span></label>
                        <textarea id="notesInput" name="notes" rows="3" maxlength="1000" placeholder="Ejemplo: prioridad, aclaración del NP o indicación para Label Room" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex flex-col gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white" aria-hidden="true">✓</span>
                            <div>
                                <div class="font-semibold text-emerald-950">Habrá una última revisión antes de guardar</div>
                                <p class="mt-1 text-sm text-emerald-800">El botón mostrará todos los datos capturados. La requisición solo se creará cuando confirmes.</p>
                            </div>
                        </div>
                        <button type="submit" class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-xl bg-red-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                            Revisar datos antes de enviar
                        </button>
                    </div>
                </div>
            </section>
        </form>

    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/kiosk-label-requests-create.js')
@endpush
