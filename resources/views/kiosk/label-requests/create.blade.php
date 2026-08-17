@extends('layouts.kiosk', ['title' => 'Nueva requisición de etiquetas'])

@section('content')
@php
    $oldRatingPartNumbers = old('rating_part_numbers', ['']);
    $oldRatingPartNumbers = is_array($oldRatingPartNumbers) && $oldRatingPartNumbers !== []
        ? $oldRatingPartNumbers
        : [''];
@endphp
<div class="space-y-6">
    @include('kiosk.partials.request-guide', [
        'title' => 'Crear requisición de etiquetas',
        'description' => 'Registra la solicitud en tres pasos. El Job se valida con Oracle antes de enviarla a Label Room.',
        'steps' => [
            ['title' => 'Identifica la operación', 'description' => 'Confirma fecha, semana, línea, turno y líder.'],
            ['title' => 'Define la requisición', 'description' => 'Valida el Job y captura modelo, cantidad y tipos de etiqueta.'],
            ['title' => 'Revisa y envía', 'description' => 'Comprueba el resumen y envía la requisición a Label Room.'],
        ],
        'preparationItems' => [
            'Job de Empaque disponible en Oracle.',
            'Modelo, NP de Serial y cantidad general requerida.',
            'Cada NP de Rating cuando la Job maneje un combo.',
            'LabelRoom asignará los folios después de recibir la requisición.',
        ],
    ])

    @include('kiosk.partials.form-errors')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <form id="kioskLabelRequestCreate"
              data-lookup-url="{{ route('kiosk.label_requests.lookup_job') }}"
              class="min-w-0 space-y-4"
              method="POST"
              action="{{ route('kiosk.label_requests.store') }}">
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

                <div class="space-y-5 p-5">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Tipo de etiqueta</label>
                        <p class="mt-1 text-xs text-slate-500">Elige primero todos los tipos requeridos. La cantidad general se aplica a Serial, cada Rating e Inner.</p>

                        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            @foreach([
                                ['id' => 'includeSerial', 'name' => 'include_serial', 'label' => 'Serial', 'description' => 'Etiqueta normal en blanco; LabelRoom asignará los folios.'],
                                ['id' => 'includeRating', 'name' => 'include_rating', 'label' => 'Rating', 'description' => 'Nameplate con uno o varios NP para combos.'],
                                ['id' => 'includeInner', 'name' => 'include_inner', 'label' => 'Inner', 'description' => 'Etiqueta interior con la cantidad general.'],
                                ['id' => 'includeShipping', 'name' => 'include_shipping', 'label' => 'Shipping', 'description' => 'Etiqueta con cantidad independiente.'],
                            ] as $type)
                                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:border-red-300 hover:bg-red-50/40">
                                    <input id="{{ $type['id'] }}" type="checkbox" name="{{ $type['name'] }}" value="1" @checked(old($type['name'])) class="mt-0.5 h-6 w-6 rounded border-slate-300 text-red-600 focus:ring-red-600" />
                                    <span>
                                        <span class="block font-medium text-slate-900">{{ $type['label'] }}</span>
                                        <span class="mt-1 block text-sm text-slate-500">{{ $type['description'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p id="typeHint" class="mt-2 text-xs text-slate-500">Selecciona al menos un tipo.</p>
                    </div>

                    <div id="ratingFields" class="max-w-2xl space-y-3">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <div class="text-sm font-medium text-slate-700">NP de Rating</div>
                                <p class="mt-1 text-xs text-slate-500">Agrega un renglón por cada rating del combo.</p>
                            </div>
                            <button id="addRatingPartNumber" type="button" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-red-300 hover:bg-red-50">
                                + Agregar rating
                            </button>
                        </div>

                        <div id="ratingPartNumbers" class="space-y-2">
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

                    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                        Captura el Job completo y espera la confirmación de Oracle. La disponibilidad se calcula con el Job Qty menos las requisiciones no canceladas.
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <label for="jobNumber" class="text-sm font-medium text-slate-700">Job</label>
                            <input id="jobNumber" type="text" name="job_number" value="{{ old('job_number') }}" maxlength="40" pattern="^[0-9A-Za-z\-]+$" placeholder="Ej: 393383" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <p id="jobHint" class="mt-2 text-xs text-slate-500">Pendiente de validar en Oracle.</p>
                        </div>

                        <div>
                            <label for="assemblyInfo" class="text-sm font-medium text-slate-700">Assembly del Job</label>
                            <input id="assemblyInfo" type="text" value="" placeholder="Se mostrará después de validar" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-slate-700" />
                        </div>

                        <div>
                            <label for="modelInput" class="text-sm font-medium text-slate-700">Modelo</label>
                            <input id="modelInput" type="text" name="model" value="{{ old('model') }}" maxlength="80" placeholder="Ej: M18 FUEL" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>

                        <div>
                            <label for="serialPartNumber" class="text-sm font-medium text-slate-700">NP de Serial</label>
                            <input id="serialPartNumber" type="text" name="serial_part_number" value="{{ old('serial_part_number') }}" maxlength="80" placeholder="Ej: 12-34-5678" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <p class="mt-1 text-xs text-slate-500">Obligatorio y único para toda la requisición.</p>
                        </div>

                        <div>
                            <label for="poNumber" class="text-sm font-medium text-slate-700">PO</label>
                            <input id="poNumber" type="text" name="po_number" value="{{ old('po_number') }}" maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+" placeholder="Autollenado desde Oracle" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>

                        <div>
                            <label for="destination" class="text-sm font-medium text-slate-700">Destino</label>
                            <input id="destination" type="text" name="destination" value="{{ old('destination') }}" maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+" placeholder="Autollenado desde Oracle" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>

                        <div>
                            <label for="quantityRequested" class="text-sm font-medium text-slate-700">Cantidad general</label>
                            <input id="quantityRequested" type="number" name="quantity_requested" min="1" max="100000" value="{{ old('quantity_requested') }}" placeholder="Ej: 250" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <p id="quantityHint" class="mt-2 text-xs text-slate-500">Primero valida el Job para conocer la disponibilidad.</p>
                        </div>

                        <div>
                            <label for="shippingQuantity" class="text-sm font-medium text-slate-700">Cantidad Shipping</label>
                            <input id="shippingQuantity" type="number" name="shipping_quantity" min="1" max="100000" value="{{ old('shipping_quantity') }}" placeholder="No requerida" @disabled(!old('include_shipping')) class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <p class="mt-1 text-xs text-slate-500">Independiente de la cantidad general; déjala vacía si no solicitas Shipping.</p>
                        </div>
                    </div>

                    <div id="jobCapacitySummary" class="hidden grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm sm:grid-cols-3">
                        <div><span class="block text-xs uppercase text-slate-500">Job Qty</span><strong id="jobQtyValue">—</strong></div>
                        <div><span class="block text-xs uppercase text-slate-500">Ya solicitado</span><strong id="reservedQuantityValue">—</strong></div>
                        <div><span class="block text-xs uppercase text-slate-500">Disponible</span><strong id="availableQuantityValue" class="text-emerald-700">—</strong></div>
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
                        <div id="previewModel" class="mt-1 text-sm text-slate-600">Modelo pendiente</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Etiquetas</div>
                        <div id="previewTypes" class="mt-1 font-semibold text-slate-900">Tipo pendiente</div>
                        <div id="previewQuantity" class="mt-1 text-sm text-slate-600">Cantidad no definida</div>
                        <div id="previewSerialPartNumber" class="mt-1 text-sm text-slate-600">NP de Serial pendiente</div>
                        <div id="previewRatingPartNumber" class="mt-1 text-sm text-slate-600">NP de Rating no requerido</div>
                        <div id="previewShippingQuantity" class="mt-1 text-sm text-slate-600">Shipping no requerido</div>
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
                    <li><span class="font-semibold text-white">Cantidad general:</span> se aplica a Serial, cada Rating e Inner.</li>
                    <li><span class="font-semibold text-white">Rating:</span> agrega un NP por cada variante del combo.</li>
                    <li><span class="font-semibold text-white">Inner:</span> utiliza la cantidad general.</li>
                    <li><span class="font-semibold text-white">Folios:</span> serán asignados y registrados manualmente por LabelRoom.</li>
                    <li><span class="font-semibold text-white">Shipping:</span> utiliza su propia cantidad y no necesita NP.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/kiosk-label-requests-create.js')
@endpush
