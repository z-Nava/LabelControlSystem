@extends('layouts.kiosk', ['title' => 'Nueva requisición de etiquetas LPK'])

@section('content')
@php
    $oldSerialPartNumbers = old('serial_part_numbers', ['']);
    $oldSerialPartNumbers = is_array($oldSerialPartNumbers) && $oldSerialPartNumbers !== []
        ? $oldSerialPartNumbers
        : [''];
    $oldRatingPartNumbers = old('rating_part_numbers', ['']);
    $oldRatingPartNumbers = is_array($oldRatingPartNumbers) && $oldRatingPartNumbers !== []
        ? $oldRatingPartNumbers
        : [''];
    $oldShippingItems = old('shipping_items', ['']);
    $oldShippingItems = is_array($oldShippingItems) && $oldShippingItems !== []
        ? $oldShippingItems
        : [''];
@endphp
<div class="space-y-6">
    @include('kiosk.partials.request-guide', [
        'title' => 'Crear requisición de etiquetas LPK',
        'description' => 'Registra una solicitud LPK y detalla los modelos o herramientas que integran sus etiquetas Shipping.',
        'steps' => [
            ['title' => 'Identifica la operación', 'description' => 'Confirma fecha, semana, línea, turno y líder.'],
            ['title' => 'Define la requisición', 'description' => 'Valida el Job y captura modelo, cantidad y tipos de etiqueta.'],
            ['title' => 'Revisa y envía', 'description' => 'Comprueba el resumen y envía la requisición a Label Room.'],
        ],
        'preparationItems' => [
            'Job de Empaque disponible en Oracle.',
            'Ensamble final, cada NP de Serial y cantidad general requerida.',
            'Cada NP de Rating cuando la Job maneje un combo.',
            'Cada NP, modelo o herramienta que deba incluirse en Shipping.',
            'LabelRoom asignará los folios después de recibir la requisición.',
        ],
    ])

    @include('kiosk.partials.form-errors')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <form id="kioskLpkLabelRequestCreate"
              data-lookup-url="{{ route('kiosk.lpk_label_requests.lookup_job') }}"
              class="min-w-0 space-y-4"
              method="POST"
              action="{{ route('kiosk.lpk_label_requests.store') }}">
            @csrf

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="text-base font-semibold text-slate-900">1) Datos generales de la requisición</div>
                    <div class="mt-1 text-sm text-slate-500">Identifica cuándo se solicita, desde qué línea y quién es responsable.</div>
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
                                <option value="{{ $line->id }}"
                                        data-line-type="{{ $line->line_type }}"
                                        @selected((string) old('line_id', $kioskUser->production_line_id) === (string) $line->id)>
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
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="text-base font-semibold text-slate-900">2) Datos del Job y etiquetas requeridas</div>
                    <div class="mt-1 text-sm text-slate-500">El Job es obligatorio. Oracle mostrará el assembly y la cantidad realmente disponible.</div>
                </div>

                <div class="space-y-6 p-5">
                    <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-700 text-sm font-bold text-white">2.1</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Identifica el Job y el ensamble final</h3>
                                <p class="mt-1 text-sm text-slate-600">Captura el Job primero y espera su validación. Oracle completará el Assembly, PO, destino y disponibilidad.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <label for="jobNumber" class="text-sm font-semibold text-slate-800">Job</label>
                                <input id="jobNumber" type="text" name="job_number" value="{{ old('job_number') }}" maxlength="40" pattern="^[0-9A-Za-z\-]+$" placeholder="Ej: 393383" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                <p id="jobHint" class="mt-2 text-xs text-slate-500">Pendiente de validar en Oracle.</p>
                            </div>

                            <div>
                                <label for="assemblyInfo" class="text-sm font-semibold text-slate-800">Assembly encontrado en Oracle</label>
                                <input id="assemblyInfo" type="text" value="" placeholder="Se mostrará después de validar" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-slate-700" />
                                <p class="mt-2 text-xs text-slate-500">Dato informativo del Job; no necesitas escribirlo.</p>
                            </div>

                            <div>
                                <label for="modelInput" class="text-sm font-semibold text-slate-800">Modelo</label>
                                <input id="modelInput" type="text" name="model" value="{{ old('model') }}" maxlength="80" placeholder="Ej: ENSAMBLE LPK M18" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                <p class="mt-2 text-xs text-slate-500">Es el producto completo que contiene los modelos o herramientas de Shipping.</p>
                            </div>

                            <div>
                                <label for="quantityRequested" class="text-sm font-semibold text-slate-800">Cantidad general de etiquetas</label>
                                <input id="quantityRequested" type="number" name="quantity_requested" min="1" max="100000" value="{{ old('quantity_requested') }}" placeholder="Ej: 250" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                <p id="quantityHint" class="mt-2 text-xs text-slate-500">Se aplica a cada Serial, cada Rating e Inner.</p>
                            </div>

                            <div>
                                <label for="poNumber" class="text-sm font-semibold text-slate-800">PO</label>
                                <input id="poNumber" type="text" name="po_number" value="{{ old('po_number') }}" maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+" placeholder="Autollenado desde Oracle" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            </div>

                            <div>
                                <label for="destination" class="text-sm font-semibold text-slate-800">Destino</label>
                                <input id="destination" type="text" name="destination" value="{{ old('destination') }}" maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+" placeholder="Autollenado desde Oracle" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            </div>
                        </div>

                        <div id="jobCapacitySummary" class="mt-4 hidden grid-cols-1 gap-3 rounded-2xl border border-blue-200 bg-white p-4 text-sm sm:grid-cols-3">
                            <div><span class="block text-xs uppercase text-slate-500">Job Qty</span><strong id="jobQtyValue">—</strong></div>
                            <div><span class="block text-xs uppercase text-slate-500">Ya solicitado</span><strong id="reservedQuantityValue">—</strong></div>
                            <div><span class="block text-xs uppercase text-slate-500">Disponible</span><strong id="availableQuantityValue" class="text-emerald-700">—</strong></div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-800 text-sm font-bold text-white">2.2</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Selecciona las etiquetas que necesitas</h3>
                                <p class="mt-1 text-sm text-slate-600">Al marcar una opción aparecerá debajo su bloque de captura. Puedes seleccionar varias.</p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            @foreach([
                                ['id' => 'includeSerial', 'name' => 'include_serial', 'label' => 'Serial', 'description' => 'Uno o varios NP. Usa la cantidad general.'],
                                ['id' => 'includeRating', 'name' => 'include_rating', 'label' => 'Rating', 'description' => 'Uno o varios NP para el combo. Usa la cantidad general.'],
                                ['id' => 'includeInner', 'name' => 'include_inner', 'label' => 'Inner', 'description' => 'No requiere NP. Usa la cantidad general.'],
                                ['id' => 'includeShipping', 'name' => 'include_shipping', 'label' => 'Shipping', 'description' => 'Tiene cantidad propia y sus modelos o herramientas.'],
                            ] as $type)
                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-red-300 hover:bg-red-50/40">
                                    <input id="{{ $type['id'] }}" type="checkbox" name="{{ $type['name'] }}" value="1" @checked(old($type['name'])) class="mt-0.5 h-6 w-6 rounded border-slate-300 text-red-600 focus:ring-red-600" />
                                    <span>
                                        <span class="block font-semibold text-slate-900">{{ $type['label'] }}</span>
                                        <span class="mt-1 block text-sm leading-5 text-slate-500">{{ $type['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p id="typeHint" class="mt-3 text-xs text-slate-500">Selecciona al menos un tipo.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-600 text-sm font-bold text-white">2.3</span>
                            <div>
                                <h3 class="font-semibold text-slate-900">Completa el detalle de cada etiqueta</h3>
                                <p class="mt-1 text-sm text-slate-600">Cada bloque corresponde a una opción seleccionada arriba. Captura un elemento por renglón.</p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div id="serialFields" @class(['rounded-2xl border border-slate-200 bg-white p-4', 'hidden' => !old('include_serial')])>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">1</span>
                                            <div class="font-semibold text-slate-900">Etiqueta Serial</div>
                                        </div>
                                        <p class="mt-2 text-sm text-slate-600">Agrega un NP por cada etiqueta Serial diferente.</p>
                                        <p class="mt-1 text-xs font-medium text-blue-700">Cantidad por cada NP: utiliza la cantidad general capturada en 2.1.</p>
                                    </div>
                                    <button id="addSerialPartNumber" type="button" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-300 hover:bg-red-50">
                                        + Agregar NP Serial
                                    </button>
                                </div>

                                <div id="serialPartNumbers" class="mt-4 max-w-2xl space-y-2">
                                    @foreach($oldSerialPartNumbers as $serialPartNumber)
                                        <div class="serial-part-number-row flex items-center gap-2">
                                            <input type="text" name="serial_part_numbers[]" value="{{ $serialPartNumber }}" maxlength="80" placeholder="NP de Serial" class="serial-part-number-input min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                            <button type="button" class="remove-serial-part-number inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <template id="serialPartNumberTemplate">
                                <div class="serial-part-number-row flex items-center gap-2">
                                    <input type="text" name="serial_part_numbers[]" maxlength="80" placeholder="NP de Serial" class="serial-part-number-input min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                    <button type="button" class="remove-serial-part-number inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                                </div>
                            </template>

                            <div id="ratingFields" @class(['rounded-2xl border border-slate-200 bg-white p-4', 'hidden' => !old('include_rating')])>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">2</span>
                                            <div class="font-semibold text-slate-900">Etiqueta Rating</div>
                                        </div>
                                        <p class="mt-2 text-sm text-slate-600">Agrega un NP por cada Rating diferente que integre el combo.</p>
                                        <p class="mt-1 text-xs font-medium text-blue-700">Cantidad por cada NP: utiliza la cantidad general capturada en 2.1.</p>
                                    </div>
                                    <button id="addRatingPartNumber" type="button" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-300 hover:bg-red-50">
                                        + Agregar NP Rating
                                    </button>
                                </div>

                                <div id="ratingPartNumbers" class="mt-4 max-w-2xl space-y-2">
                                    @foreach($oldRatingPartNumbers as $ratingPartNumber)
                                        <div class="rating-part-number-row flex items-center gap-2">
                                            <input type="text" name="rating_part_numbers[]" value="{{ $ratingPartNumber }}" maxlength="80" placeholder="NP de Rating" class="rating-part-number-input min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                            <button type="button" class="remove-rating-part-number inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <template id="ratingPartNumberTemplate">
                                <div class="rating-part-number-row flex items-center gap-2">
                                    <input type="text" name="rating_part_numbers[]" maxlength="80" placeholder="NP de Rating" class="rating-part-number-input min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                                    <button type="button" class="remove-rating-part-number inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-3 text-sm font-medium text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                                </div>
                            </template>

                            <div id="innerFields" @class(['rounded-2xl border border-slate-200 bg-white p-4', 'hidden' => !old('include_inner')])>
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">3</span>
                                    <div>
                                        <div class="font-semibold text-slate-900">Etiqueta Inner</div>
                                        <p class="mt-1 text-sm text-slate-600">No necesita NP ni campos adicionales.</p>
                                        <p class="mt-1 text-xs font-medium text-blue-700">Cantidad: utiliza la cantidad general capturada en 2.1.</p>
                                    </div>
                                </div>
                            </div>

                            <div id="shippingFields" @class(['rounded-2xl border border-amber-300 bg-amber-50/70 p-4', 'hidden' => !old('include_shipping')])>
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-600 text-xs font-bold text-white">4</span>
                                    <div>
                                        <div class="font-semibold text-slate-900">Etiqueta Shipping LPK</div>
                                        <p class="mt-1 text-sm text-slate-600">Shipping usa una cantidad propia; no toma la cantidad general.</p>
                                    </div>
                                </div>

                                <div class="mt-4 max-w-md rounded-xl border border-amber-200 bg-white p-4">
                                    <label for="shippingQuantity" class="text-sm font-semibold text-slate-800">Cantidad de etiquetas Shipping</label>
                                    <input id="shippingQuantity" type="number" name="shipping_quantity" min="1" max="100000" value="{{ old('shipping_quantity') }}" placeholder="Ej: 25" @disabled(!old('include_shipping')) class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-600" />
                                    <p class="mt-2 text-xs text-slate-500">Esta cantidad se aplicará a cada elemento agregado debajo.</p>
                                </div>

                                <div class="mt-4 border-t border-amber-200 pt-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">¿Qué modelos o herramientas van en Shipping?</div>
                                            <p class="mt-1 text-sm text-slate-600">Agrega un renglón por cada NP, modelo o herramienta que integra el ensamble final LPK.</p>
                                        </div>
                                        <button id="addShippingItem" type="button" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-amber-400 bg-white px-4 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">
                                            + Agregar modelo o herramienta
                                        </button>
                                    </div>

                                    <div id="shippingItems" class="mt-4 max-w-2xl space-y-2">
                                        @foreach($oldShippingItems as $shippingItem)
                                            <div class="shipping-item-row flex items-center gap-2">
                                                <input type="text" name="shipping_items[]" value="{{ $shippingItem }}" maxlength="120" placeholder="Ej: NP 48-11-1850 o HERRAMIENTA M18" class="shipping-item-input min-w-0 flex-1 rounded-xl border border-amber-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-600" />
                                                <button type="button" class="remove-shipping-item inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-300 bg-white px-3 text-sm font-medium text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <template id="shippingItemTemplate">
                                    <div class="shipping-item-row flex items-center gap-2">
                                        <input type="text" name="shipping_items[]" maxlength="120" placeholder="Ej: NP 48-11-1850 o HERRAMIENTA M18" class="shipping-item-input min-w-0 flex-1 rounded-xl border border-amber-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-600" />
                                        <button type="button" class="remove-shipping-item inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-300 bg-white px-3 text-sm font-medium text-slate-600 hover:border-red-300 hover:bg-red-50 hover:text-red-700">Quitar</button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="text-base font-semibold text-slate-900">3) Resumen y envío</div>
                    <div class="mt-1 text-sm text-slate-500">Agrega notas opcionales y confirma los datos antes de enviar.</div>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <label for="notesInput" class="text-sm font-medium text-slate-700">Notas</label>
                        <textarea id="notesInput" name="notes" rows="4" maxlength="1000" placeholder="Información adicional para Label Room" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm text-slate-600">Al enviar, Label Room recibirá la solicitud como <strong>Pendiente</strong>. No se imprimirán etiquetas automáticamente.</p>
                        <button class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-xl bg-red-600 px-6 py-3 font-semibold text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                            Revisar y enviar requisición
                        </button>
                    </div>
                </div>
            </section>
        </form>

        <aside class="space-y-4 xl:sticky xl:top-28 xl:self-start">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-base font-semibold text-slate-900">Resumen en vivo</div>
                <p class="mt-1 text-sm text-slate-500">Se actualiza conforme completas el formulario.</p>

                <div class="mt-4 space-y-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Operación</div>
                        <div id="previewLineShift" class="mt-1 font-semibold text-slate-900">Selecciona línea y turno</div>
                        <div id="previewLeader" class="mt-1 text-sm text-slate-600">Sin líder capturado</div>
                        <div id="previewDateWeek" class="mt-1 text-sm text-slate-600">Fecha y semana pendientes</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Job</div>
                        <div id="previewJob" class="mt-1 font-semibold text-slate-900">Job no capturado</div>
                        <div id="previewAssembly" class="mt-1 text-sm text-slate-600">Assembly pendiente</div>
                        <div id="previewModel" class="mt-1 text-sm text-slate-600">Ensamble final pendiente</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Etiquetas</div>
                        <div id="previewTypes" class="mt-1 font-semibold text-slate-900">Tipo pendiente</div>
                        <div id="previewQuantity" class="mt-1 text-sm text-slate-600">Cantidad no definida</div>
                        <div id="previewSerialPartNumbers" class="mt-1 text-sm text-slate-600">NP de Serial no requerido</div>
                        <div id="previewRatingPartNumber" class="mt-1 text-sm text-slate-600">NP de Rating no requerido</div>
                        <div id="previewShippingQuantity" class="mt-1 text-sm text-slate-600">Shipping no requerido</div>
                        <div id="previewShippingItems" class="mt-1 text-sm text-slate-600">Elementos de Shipping no requeridos</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Oracle</div>
                        <div id="previewExtras" class="mt-1 text-sm text-slate-600">PO y destino pendientes.</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-900 p-5 text-slate-100 shadow-sm">
                <div class="text-base font-semibold">Reglas importantes</div>
                <ul class="mt-3 space-y-3 text-sm text-slate-300">
                    <li><span class="font-semibold text-white">Cantidad general:</span> se aplica a cada Serial, cada Rating e Inner.</li>
                    <li><span class="font-semibold text-white">Serial:</span> agrega un NP por cada etiqueta Serial requerida.</li>
                    <li><span class="font-semibold text-white">Rating:</span> agrega un NP por cada variante del combo.</li>
                    <li><span class="font-semibold text-white">Inner:</span> utiliza la cantidad general.</li>
                    <li><span class="font-semibold text-white">Folios:</span> serán asignados y registrados manualmente por LabelRoom.</li>
                    <li><span class="font-semibold text-white">Shipping:</span> utiliza su propia cantidad y requiere al menos un NP, modelo o herramienta.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/kiosk-lpk-label-requests-create.js')
@endpush
