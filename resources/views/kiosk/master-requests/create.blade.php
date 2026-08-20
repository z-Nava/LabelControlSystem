@extends('layouts.kiosk', ['title' => 'Nueva requisición Master'])

@section('content')
<div class="space-y-6">
    @include('kiosk.partials.request-guide', [
        'title' => 'Crear requisición de hoja Master',
        'description' => 'Solicita las hojas Master que necesita producción. Captura la operación, consulta los Jobs y especifica los folios requeridos.',
        'steps' => [
            ['title' => 'Identifica la operación', 'description' => 'Selecciona línea, turno y escribe el nombre del líder.'],
            ['title' => 'Elige el tipo de Master', 'description' => 'Selecciona el formato que necesita la operación.'],
            ['title' => 'Consulta los Jobs', 'description' => 'Captura los Jobs necesarios y espera la respuesta de Oracle.'],
            ['title' => 'Define folios y envía', 'description' => 'Indica el rango, revisa el resumen y envía a Label Room.'],
        ],
        'preparationItems' => [
            'Línea, turno y nombre del líder.',
            'Tipo de hoja Master requerida.',
            'Job de ensamble y/o empaque.',
            'Rango de folios y piezas por pallet.',
        ],
    ])

    @include('kiosk.partials.form-errors')

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <form id="masterRequestCreate"
              data-lookup-url="{{ route('kiosk.master_requests.lookup_job') }}"
              class="min-w-0 space-y-4"
              method="POST"
              action="{{ route('kiosk.master_requests.store') }}">
        @csrf

        {{-- 1) DATOS GENERALES --}}
        <details open class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none select-none items-center justify-between px-5 py-4">
                <div>
                    <div class="text-base font-semibold text-slate-900">1) Identifica la operación</div>
                    <div class="mt-1 text-sm text-slate-500">Indica dónde se usarán las hojas Master y quién es el líder responsable.</div>
                </div>
                <span class="text-slate-400 group-open:rotate-180 transition">⌄</span>
            </summary>

            <div class="grid grid-cols-1 gap-4 border-t border-slate-200 p-5 md:grid-cols-2 2xl:grid-cols-3">
                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900 md:col-span-2 2xl:col-span-3">
                    <span class="font-semibold">Qué debes hacer:</span> selecciona el tipo de línea, después la línea y el turno. Escribe el nombre del líder de esa operación.
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Fecha</label>
                    <input id="requestDate" name="request_date" type="date"
                           value="{{ old('request_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                    <p class="mt-1 text-xs text-slate-500">Inicia con la fecha actual. Cámbiala solo si la solicitud corresponde a otra fecha.</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Semana</label>
                    <input name="week" type="number" min="1" max="53"
                           value="{{ old('week', now()->weekOfYear) }}"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                    <p class="mt-1 text-xs text-slate-500">Inicia con la semana actual.</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Tipo de línea</label>
                    <select id="lineTypeSelect"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                        <option value="">Selecciona tipo de línea...</option>
                        @foreach($lines->pluck('line_type')->filter()->unique()->sort()->values() as $lineType)
                            <option value="{{ $lineType }}">
                                {{ $lineType }}
                            </option>
                        @endforeach
                    </select>
                    @error('line_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Línea</label>
                    <select id="lineSelect" name="line_id"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                        <option value="">Selecciona linea...</option>
                        @foreach($lines as $line)
                            @php($inventoryMapping = $inventoryMappings->get(strtoupper(trim($line->code))))
                            <option value="{{ $line->id }}"
                                    data-line-type="{{ $line->line_type }}"
                                    data-line-code="{{ $line->code }}"
                                    data-stock-locator="{{ $inventoryMapping?->stock_locator ?? '' }}"
                                    data-subinventory="{{ $inventoryMapping?->subinventory ?? '' }}"
                                    @selected(old('line_id') == $line->id)>
                                {{ $line->code }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700">Turno</label>
                    <select id="shiftSelect" name="shift_id"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                        <option value="">Selecciona turno...</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>
                                {{ $shift->code }} - {{ $shift->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-slate-700">Líder</label>
                     <input name="leader_name" value="{{ old('leader_name') }}" maxlength="120" minlength="3" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s\-\.']+"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                    <p class="mt-1 text-xs text-slate-500">Escribe el nombre del líder, no tu número de empleado.</p>
                </div>
            </div>
        </details>

        {{-- 2) TIPO MASTER --}}
        <details open class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none select-none items-center justify-between px-5 py-4">
                <div>
                    <div class="text-base font-semibold text-slate-900">2) Elige el tipo de Master</div>
                    <div class="mt-1 text-sm text-slate-500">Selecciona el formato que necesita producción.</div>
                </div>
                <span class="text-slate-400 group-open:rotate-180 transition">⌄</span>
            </summary>

            <div class="border-t border-slate-200 p-5">
                <div class="mb-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    <span class="font-semibold">Qué debes hacer:</span> selecciona el tipo de línea; aquí aparecerán únicamente los tipos de hoja Master permitidos.
                </div>

                <label class="text-sm font-medium text-slate-700">Tipo de Master</label>
                <select id="requestType" name="request_type"
                        data-ort-assembly-type="{{ $ortAssemblyConfig['type'] }}"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                    <option value="">Selecciona primero el tipo de línea...</option>
                    @foreach($masterRequestTypes as $requestType => $requestTypeData)
                        <option value="{{ $requestType }}"
                                data-line-types="{{ implode('|', $requestTypeData['line_types']) }}"
                                @selected(old('request_type') === $requestType)>
                            {{ $requestTypeData['label'] }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-2">
                    Si sólo existe una opción válida, el sistema la seleccionará automáticamente.
                </p>
            </div>
        </details>

        {{-- 3) JOBS (ORACLE) --}}
        <details open class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none select-none items-center justify-between px-5 py-4">
                <div>
                    <div class="text-base font-semibold text-slate-900">3) Consulta los Jobs en Oracle</div>
                    <div class="mt-1 text-sm text-slate-500">Captura los Jobs y espera a que el sistema complete la información disponible.</div>
                </div>
                <span class="text-slate-400 group-open:rotate-180 transition">⌄</span>
            </summary>

            <div class="grid grid-cols-1 gap-4 border-t border-slate-200 p-5 md:grid-cols-2">
                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900 md:col-span-2">
                    <span class="font-semibold">Qué debes hacer:</span> escribe cada Job completo y sal del campo. Espera el mensaje de Oracle antes de revisar Local, PO y Destino.
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="text-sm text-slate-700 font-medium">Job Ensamble</label>
                    <input id="jobAssembly" name="job_assembly" value="{{ old('job_assembly') }}"  maxlength="40" pattern="^[0-9A-Za-z\-]+$"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                           placeholder="Ej: 393383">
                    <p id="jobAssemblyQty" class="text-md text-slate-600 mt-2">Cantidad del job: —</p>
                    <p id="jobAssemblyHint" class="text-xs text-slate-500 mt-2"></p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="text-sm text-slate-700 font-medium">Job Empaque (si aplica)</label>
                    <input id="jobPackaging" name="job_packaging" value="{{ old('job_packaging') }}"  maxlength="40" pattern="^[0-9A-Za-z\-]+$"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                           placeholder="Opcional">
                    <p id="jobPackagingQty" class="text-md text-slate-600 mt-2">Cantidad del job: —</p>
                    <p id="jobPackagingHint" class="text-xs text-slate-500 mt-2"></p>
                </div>

                <div id="lineMatchStatus"
                     class="hidden rounded-xl border px-4 py-3 text-sm md:col-span-2"
                     role="status"
                     aria-live="polite">
                    <p id="lineMatchStatusTitle" class="font-semibold"></p>
                    <p id="lineMatchStatusMessage" class="mt-1"></p>
                </div>

                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Stock Locator (Local)</label>
                        <input id="localInput" name="local" value="{{ old('local') }}" maxlength="20" pattern="^[A-Za-z0-9\-._]+$"
                               data-ort-default-value="{{ $ortAssemblyConfig['default_local'] }}" readonly
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 uppercase text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se resolverá desde Locals by Oracle Line">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Subinventory</label>
                        <input id="subinventoryInput" name="subinventory" value="{{ old('subinventory') }}" maxlength="20" pattern="^[A-Za-z0-9\-._]+$"
                               data-ort-default-value="{{ $ortAssemblyConfig['default_subinventory'] }}" readonly
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 uppercase text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se resolverá desde Locals by Oracle Line">
                    </div>
                </div>

                <div id="inventoryDestinationWarning"
                     class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 md:col-span-2"
                     role="status"
                     aria-live="polite">
                    <span class="font-semibold">Aviso:</span>
                    <span id="inventoryDestinationWarningMessage"></span>
                </div>

                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                            <label class="text-sm text-slate-600">Custom PO</label>
                        <input id="poNumber" name="po_number" value="{{ old('po_number') }}" maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+" readonly aria-readonly="true"
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se tomará del Job Empaque">
                    </div>

                    <div>
                            <label class="text-sm text-slate-600">Destino (Ship Code)</label>
                        <input id="destination" name="destination" value="{{ old('destination') }}" maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+" readonly aria-readonly="true"
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se tomará del Job Empaque">
                    </div>
                </div>
                <p class="text-xs text-slate-500 md:col-span-2">PO y Destino se toman exclusivamente del Job Empaque registrado en Oracle.</p>
            </div>
        </details>

        {{-- 4) FOLIOS Y CANTIDADES --}}
        <details open class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none select-none items-center justify-between px-5 py-4">
                <div>
                    <div class="text-base font-semibold text-slate-900">4) Define folios y cantidades</div>
                    <div class="mt-1 text-sm text-slate-500">Indica exactamente qué folios necesita producción.</div>
                </div>
                <span class="text-slate-400 group-open:rotate-180 transition">⌄</span>
            </summary>

            <div class="space-y-4 border-t border-slate-200 p-5">
                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    <span class="font-semibold">Qué debes hacer:</span> captura el primer y último folio del rango. Ejemplo: del 1 al 10 solicita diez folios.
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm text-slate-600">Folios del</label>
                        <input name="folios_from" type="number" min="1" value="{{ old('folios_from') }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">al</label>
                        <input name="folios_to" type="number" min="{{ old('folios_from', 1) }}" value="{{ old('folios_to') }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Std pack (pzas/pallet)</label>
                        <input name="std_pack_qty" type="number" min="1" value="{{ old('std_pack_qty') }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Tipo</label>
                        <select name="kind"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                            <option value="new" @selected(old('kind','new')=='new')>Nuevo</option>
                            <option value="reposition" @selected(old('kind')=='reposition')>Reposición</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Usa “Reposición” si reemplaza hojas ya solicitadas.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-sm font-semibold text-slate-800">Parcial (opcional)</div>
                    <p class="text-xs text-slate-500 mt-1">
                        Úsalo cuando el último pallet no está completo (folio y piezas parciales).
                    </p>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm text-slate-600">Folio parcial</label>
                            <input name="partial_folio" type="number" min="1" value="{{ old('partial_folio') }}"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                        </div>

                        <div>
                            <label class="text-sm text-slate-600">Pzas pallet parcial</label>
                            <input name="partial_qty" type="number" min="1" value="{{ old('partial_qty') }}"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                        </div>
                    </div>
                </div>
            </div>
        </details>

        {{-- 5) EXTRAS --}}
        <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none select-none items-center justify-between px-5 py-4">
                <div>
                    <div class="text-base font-semibold text-slate-900">5) Agrega observaciones (opcional)</div>
                    <div class="mt-1 text-sm text-slate-500">Abre esta sección solo si Label Room necesita información adicional.</div>
                </div>
                <span class="text-slate-400 group-open:rotate-180 transition">⌄</span>
            </summary>

            <div class="border-t border-slate-200 p-5">
                <label class="text-sm font-medium text-slate-700">Notas</label>
                <textarea name="notes" rows="3" maxlength="1000"
                          placeholder="Ej: prioridad, aclaración del folio o información útil para Label Room"
                          class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">{{ old('notes') }}</textarea>
            </div>
        </details>

        {{-- ACCIONES --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="font-semibold text-slate-900">Última revisión</div>
                    <p class="mt-1 text-sm text-slate-600">Confirma el resumen. Al enviar, Label Room recibirá la solicitud y el kiosco imprimirá un comprobante.</p>
                </div>

                <button class="min-h-12 shrink-0 rounded-xl bg-red-600 px-6 py-3 font-semibold text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                    Revisar y enviar requisición
                </button>
            </div>
        </div>
        </form>

        <aside class="space-y-4 xl:sticky xl:top-28 xl:self-start">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="font-semibold text-slate-900">Resumen en vivo</h2>
                            <p class="text-sm text-slate-500">Confirma antes de enviar.</p>
                        </div>
                    </div>
                </div>

                <dl class="space-y-3 p-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha</dt>
                        <dd id="previewDate" class="mt-1 font-semibold text-slate-900">—</dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Línea / Turno</dt>
                        <dd id="previewLineShift" class="mt-1 font-semibold text-slate-900">—</dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de Master</dt>
                        <dd id="previewType" class="mt-1 font-semibold capitalize text-slate-900">—</dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Job(s)</dt>
                        <dd id="previewJobs" class="mt-1 break-words font-semibold text-slate-900">—</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl bg-slate-900 p-5 text-slate-100 shadow-sm">
                <h2 class="font-semibold">Antes de enviar</h2>
                <ul class="mt-3 space-y-3 text-sm leading-5 text-slate-300">
                    <li><span class="font-semibold text-white">Oracle:</span> espera el resultado de cada Job.</li>
                    <li><span class="font-semibold text-white">Folios:</span> revisa que el inicio sea menor o igual al final.</li>
                    <li><span class="font-semibold text-white">Reposición:</span> úsala solo para reemplazar hojas solicitadas.</li>
                </ul>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/master-requests-create.js')
@endpush
