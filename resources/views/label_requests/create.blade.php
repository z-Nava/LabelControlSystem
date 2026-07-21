@extends('layouts.app', ['title' => 'Nueva requisición de etiquetas'])

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl shadow p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    Flujo guiado para captura de etiquetas
                </div>
                <h1 class="mt-3 text-2xl font-semibold text-slate-900">Crear requisición de etiquetas</h1>
                <p class="mt-2 max-w-3xl text-slate-600">
                    Completa la solicitud paso a paso: primero define la operación, después valida el Job en Oracle,
                    luego selecciona el SKU y la cantidad, y finalmente agrega datos complementarios antes de guardar.
                </p>
            </div>

            <a href="{{ route('label_requests.index') }}" class="shrink-0 rounded-xl border px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Volver al listado
            </a>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Inicio</div>
                <div class="mt-1 font-semibold text-slate-900">Datos generales</div>
                <p class="mt-1 text-sm text-slate-600">Fecha, semana, línea, turno y líder.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paso 2</div>
                <div class="mt-1 font-semibold text-slate-900">Validación Oracle</div>
                <p class="mt-1 text-sm text-slate-600">Job, PO y destino con autollenado.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paso 3</div>
                <div class="mt-1 font-semibold text-slate-900">Etiqueta solicitada</div>
                <p class="mt-1 text-sm text-slate-600">SKU / Label PN, cantidad y tipo de etiqueta.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paso 4</div>
                <div class="mt-1 font-semibold text-slate-900">Confirmación</div>
                <p class="mt-1 text-sm text-slate-600">Modelo, notas y revisión final antes de guardar.</p>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <form id="labelRequestCreate"
              data-lookup-url="{{ route('label_requests.lookup_job') }}"
              class="space-y-4"
              method="POST"
              action="{{ route('label_requests.store') }}">
            @csrf

            <details open class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <div class="text-base font-semibold text-slate-900">1) Datos generales de la requisición</div>
                        <div class="mt-1 text-sm text-slate-500">Identifica cuándo se solicita, desde qué línea y quién es responsable.</div>
                    </div>
                    <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                </summary>

                <div class="border-t border-slate-200 p-5">
                    <div class="mb-4 rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        <span class="font-semibold">Sugerencia:</span> captura primero esta sección para que el resumen lateral refleje la operación correctamente.
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div>
                            <label class="text-sm font-medium text-slate-700">Fecha</label>
                            <input id="requestDate"
                                   type="date"
                                   name="request_date"
                                   max="{{ now()->toDateString() }}"
                                   value="{{ old('request_date', $defaultDate) }}"
                                   required
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">Semana</label>
                            <input id="requestWeek"
                                   type="number"
                                   name="week"
                                   min="1"
                                   max="53"
                                   value="{{ old('week', $defaultWeek) }}"
                                   required
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">Líder</label>
                            <input id="leaderName"
                                   type="text"
                                   name="leader_name"
                                   value="{{ old('leader_name') }}"
                                   minlength="3"
                                   maxlength="120"
                                   pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s\-\.']+"
                                   placeholder="Ej: Juan Pérez"
                                   required
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">Tipo de línea</label>
                            <select id="lineTypeSelect"
                                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                                <option value="">Mostrar todos los tipos</option>
                                @foreach($lines->pluck('line_type')->filter()->unique()->sort() as $lineType)
                                    <option value="{{ $lineType }}" @selected(old('line_type') === $lineType)>
                                        {{ $lineType }}
                                    </option>
                                @endforeach
                            </select>
                            <p id="lineTypeHint" class="mt-2 text-xs text-slate-500">
                                Selecciona un tipo para acotar las líneas disponibles.
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">Línea</label>
                            <select id="lineSelect" name="line_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                                <option value="">Selecciona una línea</option>
                                @foreach($lines as $line)
                                    <option value="{{ $line->id }}"
                                            data-line-type="{{ $line->line_type }}"
                                            @selected((string) old('line_id') === (string) $line->id)>
                                        {{ $line->code }} · {{ $line->line_type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">Turno</label>
                            <select id="shiftSelect" name="shift_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                                <option value="">Selecciona un turno</option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}" @selected((string) old('shift_id') === (string) $shift->id)>
                                        {{ $shift->code }} · {{ $shift->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </details>

            <details open class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <div class="text-base font-semibold text-slate-900">2) Datos del Job y autollenado Oracle</div>
                        <div class="mt-1 text-sm text-slate-500">Ingresa el Job para consultar Oracle y recuperar PO / destino automáticamente cuando exista coincidencia.</div>
                    </div>
                    <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                </summary>

                <div class="border-t border-slate-200 p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="text-sm font-medium text-slate-700">Job</label>
                            <input id="jobNumber"
                                   type="text"
                                   name="job_number"
                                   value="{{ old('job_number') }}"
                                   maxlength="40"
                                   pattern="^[0-9A-Za-z\-]+$"
                                   placeholder="Ej: 393383"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                            <p id="jobHint" class="mt-2 text-xs text-slate-500"></p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">PO</label>
                            <input id="poNumber"
                                   type="text"
                                   name="po_number"
                                   value="{{ old('po_number') }}"
                                   maxlength="80"
                                   pattern="[A-Za-z0-9\-\/_\s]+"
                                   placeholder="Se autollenará si Oracle lo trae"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">Destino</label>
                            <input id="destination"
                                   type="text"
                                   name="destination"
                                   value="{{ old('destination') }}"
                                   maxlength="80"
                                   pattern="[A-Za-z0-9\-\/_\s]+"
                                   placeholder="Se autollenará si Oracle lo trae"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>
                    </div>
                </div>
            </details>

            <details open class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <div class="text-base font-semibold text-slate-900">3) Selección de etiqueta</div>
                        <div class="mt-1 text-sm text-slate-500">Elige el Label PN activo, indica la cantidad y marca el tipo de impresión requerido.</div>
                    </div>
                    <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                </summary>

                <div class="border-t border-slate-200 p-5 space-y-4">
                    <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Solo se muestran SKUs con formato de serial activo para evitar capturas inválidas.
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700">Estándar serial</label>
                            <select id="serialStandard" name="serial_standard" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                                @foreach(($serialStandards ?? ['UL', 'EMEA', 'ANZ']) as $standard)
                                    <option value="{{ $standard }}" @selected(old('serial_standard', $defaultStandard ?? 'UL') === $standard)>
                                        {{ $standard }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">SKU / Label PN</label>
                            <select id="labelPartNumber" name="label_part_number" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                                <option value="">Selecciona SKU / Label PN disponible</option>
                                @foreach($labelSkus as $sku)
                                    <option value="{{ $sku->label_part_number }}"
                                            data-sku="{{ $sku->sku }}"
                                            data-standard="{{ $sku->serial_standard ?? 'UL' }}"
                                            data-description="{{ $sku->description }}"
                                            data-assembly-part-number="{{ $sku->assembly_part_number }}"
                                            data-packaging-part-number="{{ $sku->packaging_part_number }}"
                                            @selected(old('label_part_number') === $sku->label_part_number)>
                                        {{ $sku->serial_standard ?? 'UL' }} · {{ $sku->sku }} · {{ $sku->label_part_number }} · {{ $sku->description }}
                                    </option>
                                @endforeach
                           </select>
                            <p id="labelHint" class="mt-2 text-xs text-slate-500">Selecciona un registro para mostrar su descripción en el resumen.</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">Cantidad</label>
                            <input id="quantityRequested"
                                   type="number"
                                   name="quantity_requested"
                                   min="1"
                                   value="{{ old('quantity_requested') }}"
                                   placeholder="Ej: 250"
                                   required
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">Tipo de etiqueta</label>
                        <p class="mt-1 text-xs text-slate-500">Selecciona al menos una opción para indicar qué impresión necesita producción.</p>

                        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:border-red-300 hover:bg-red-50/40">
                                <input id="includeSerial" type="checkbox" name="include_serial" value="1" @checked(old('include_serial')) class="mt-1 h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-600" />
                                <div>
                                    <div class="font-medium text-slate-900">Serial</div>
                                    <p class="mt-1 text-sm text-slate-500">Incluye numeración serial para identificación y trazabilidad.</p>
                                </div>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:border-red-300 hover:bg-red-50/40">
                                <input id="includeRating" type="checkbox" name="include_rating" value="1" @checked(old('include_rating')) class="mt-1 h-4 w-4 rounded border-slate-300 text-red-600 focus:ring-red-600" />
                                <div>
                                    <div class="font-medium text-slate-900">Rating</div>
                                    <p class="mt-1 text-sm text-slate-500">Agrega la etiqueta con información técnica y especificaciones del producto.</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </details>

            <details open class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <div class="text-base font-semibold text-slate-900">4) Información adicional y confirmación</div>
                        <div class="mt-1 text-sm text-slate-500">Agrega modelo y observaciones para dejar contexto claro antes de guardar.</div>
                    </div>
                    <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                </summary>

                <div class="border-t border-slate-200 p-5 space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700">Modelo</label>
                            <input id="modelInput"
                                   type="text"
                                   name="model"
                                   value="{{ old('model') }}"
                                   maxlength="80"
                                   placeholder="Ej: M18 FUEL"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm font-semibold text-slate-900">Antes de guardar</div>
                            <ul class="mt-2 space-y-2 text-sm text-slate-600">
                                <li>• Verifica que la línea y el turno correspondan a la operación.</li>
                                <li>• Confirma la cantidad de etiquetas requerida.</li>
                                <li>• Si capturas Job, revisa que Oracle lo marque como válido.</li>
                            </ul>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">Notas</label>
                        <textarea id="notesInput" name="notes" rows="4" maxlength="1000" placeholder="Información adicional para el equipo de impresión o producción" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </details>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="text-base font-semibold text-slate-900">Listo para guardar la requisición</div>
                        <p class="mt-1 text-sm text-slate-500">Cuando toda la información sea correcta, registra la solicitud para continuar con la impresión.</p>
                    </div>

                    <button class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-500">
                        Crear requisición
                    </button>
                </div>
            </div>
        </form>

        <aside class="space-y-4 xl:sticky xl:top-6 xl:self-start">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-base font-semibold text-slate-900">Resumen en vivo</div>
                <p class="mt-1 text-sm text-slate-500">Se actualiza conforme completas el formulario.</p>

                <div class="mt-4 space-y-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Operación</div>
                        <div id="previewLineShift" class="mt-1 font-semibold text-slate-900">Selecciona línea y turno</div>
                        <div id="previewLeader" class="mt-1 text-sm text-slate-600">Sin líder capturado</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Solicitud</div>
                        <div id="previewDateWeek" class="mt-1 font-semibold text-slate-900">Fecha y semana pendientes</div>
                        <div id="previewQuantity" class="mt-1 text-sm text-slate-600">Cantidad no definida</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Etiqueta</div>
                        <div id="previewLabel" class="mt-1 font-semibold text-slate-900">Sin SKU / Label PN</div>
                        <div id="previewLabelDescription" class="mt-1 text-sm text-slate-600">Selecciona una opción para ver el detalle.</div>
                        <div id="previewTypes" class="mt-2 text-xs text-slate-500">Tipo pendiente</div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Oracle / Extras</div>
                        <div id="previewJob" class="mt-1 font-semibold text-slate-900">Job no capturado</div>
                        <div id="previewExtras" class="mt-1 text-sm text-slate-600">PO, destino y modelo pendientes.</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-900 p-5 text-slate-100 shadow-sm">
                <div class="text-base font-semibold">¿Cómo usar esta vista?</div>
                <ol class="mt-3 space-y-3 text-sm text-slate-300">
                    <li><span class="font-semibold text-white">1.</span> Completa primero la información general.</li>
                    <li><span class="font-semibold text-white">2.</span> Captura el Job para validar datos en Oracle.</li>
                    <li><span class="font-semibold text-white">3.</span> Elige el Label PN y el tipo de etiqueta requerido.</li>
                    <li><span class="font-semibold text-white">4.</span> Revisa el resumen lateral y guarda la requisición.</li>
                </ol>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/label-requests-create.js')
@endpush
