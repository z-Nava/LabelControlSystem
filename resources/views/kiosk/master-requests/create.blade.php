@extends('layouts.kiosk', ['title' => 'Nueva requisición Master'])

@php($initialLocal = strtoupper(trim((string) old('local', ''))))

@section('content')
<div class="space-y-6">
    @include('kiosk.partials.request-guide', [
        'title' => 'Crear requisición Master',
        'description' => 'Captura los Jobs del formato físico. Oracle confirmará su línea y mostrará los tipos de hoja Master disponibles antes de enviar la solicitud.',
        'steps' => [
            ['title' => 'Valida los Jobs', 'description' => 'Captura Ensamble y, si aplica, Empaque; espera la respuesta de Oracle.'],
            ['title' => 'Elige el tipo', 'description' => 'Selecciona la hoja Master según la línea confirmada.'],
            ['title' => 'Define los folios', 'description' => 'Indica el rango, std pack y cualquier pallet parcial.'],
            ['title' => 'Revisa y envía', 'description' => 'Verifica el resumen antes de enviar a Label Room.'],
        ],
        'preparationItems' => [
            'Job de Ensamble y Job de Empaque, si aplica.',
            'Rango de folios y piezas por pallet.',
            'Folio y cantidad del pallet parcial, si existe.',
        ],
        'requestGuideNote' => 'El Kiosko imprimirá el comprobante al crear la solicitud; Label Room atenderá las hojas Master.',
    ])

    @include('kiosk.partials.form-errors')

    <form id="masterRequestCreate"
          data-lookup-url="{{ route('kiosk.master_requests.lookup_job') }}"
          data-request-source="kiosk"
          data-validation-flow="job-driven"
          class="mx-auto max-w-7xl min-w-0 space-y-4"
          method="POST"
          action="{{ route('kiosk.master_requests.store') }}">
        @csrf

        {{-- 1) JOBS ORACLE --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-start gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-sm font-bold text-white">1</span>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Valida los Jobs en Oracle</h2>
                    <p class="mt-1 text-sm text-slate-500">Consulta los Jobs para identificar automáticamente sus líneas de producción.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label for="jobAssembly" class="text-sm font-semibold text-slate-700">Job Ensamble</label>
                    <input id="jobAssembly"
                           name="job_assembly"
                           value="{{ old('job_assembly') }}"
                           maxlength="40"
                           pattern="^[0-9A-Za-z\-]+$"
                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
                           placeholder="Ej: 393383">
                    <p id="jobAssemblyQty" class="mt-2 text-sm text-slate-600">Cantidad del Job: —</p>
                    <p id="jobAssemblyHint" class="mt-2 text-xs text-slate-500"></p>

                    <div id="jobAssemblyContext" class="mt-3 hidden rounded-lg border border-slate-200 bg-white p-3 text-sm">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contexto de producción</div>
                        <div class="mt-1 font-semibold text-slate-900">
                            <span id="jobAssemblyLine">—</span>
                            <span id="jobAssemblyLineType" class="font-normal text-slate-500"></span>
                        </div>
                        <p id="jobAssemblyInventory" class="mt-1 text-xs text-slate-600"></p>
                    </div>

                    @error('job_assembly')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label for="jobPackaging" class="text-sm font-semibold text-slate-700">Job Empaque <span class="font-normal text-slate-500">(si aplica)</span></label>
                    <input id="jobPackaging"
                           name="job_packaging"
                           value="{{ old('job_packaging') }}"
                           maxlength="40"
                           pattern="^[0-9A-Za-z\-]+$"
                           class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
                           placeholder="Opcional">
                    <p id="jobPackagingQty" class="mt-2 text-sm text-slate-600">Cantidad del Job: —</p>
                    <p id="jobPackagingHint" class="mt-2 text-xs text-slate-500"></p>

                    <div id="jobPackagingContext" class="mt-3 hidden rounded-lg border border-slate-200 bg-white p-3 text-sm">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contexto de producción</div>
                        <div class="mt-1 font-semibold text-slate-900">
                            <span id="jobPackagingLine">—</span>
                            <span id="jobPackagingLineType" class="font-normal text-slate-500"></span>
                        </div>
                        <p id="jobPackagingInventory" class="mt-1 text-xs text-slate-600"></p>
                    </div>

                    @error('job_packaging')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900 md:col-span-2">
                    <span class="font-semibold">Qué debes hacer:</span> escribe los Jobs completos y espera a que Oracle muestre la línea y la cantidad. El Job de Empaque se usa para consultar la PO y el destino.
                </div>

                <div class="grid grid-cols-1 gap-4 md:col-span-2 md:grid-cols-3">
                    <div>
                        <label for="poNumber" class="text-sm font-semibold text-slate-700">Custom PO <span class="font-normal text-slate-500">(Oracle)</span></label>
                        <input id="poNumber"
                               name="po_number"
                               value="{{ old('po_number') }}"
                               maxlength="80"
                               pattern="[A-Za-z0-9\-\/_\s]+"
                               readonly
                               aria-readonly="true"
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2.5 text-slate-700"
                               placeholder="Se tomará del Job Empaque">
                    </div>

                    <div>
                        <label for="destination" class="text-sm font-semibold text-slate-700">Destino <span class="font-normal text-slate-500">(Ship Code)</span></label>
                        <input id="destination"
                               name="destination"
                               value="{{ old('destination') }}"
                               maxlength="80"
                               pattern="[A-Za-z0-9\-\/_\s]+"
                               readonly
                               aria-readonly="true"
                               aria-describedby="destinationWarning"
                               @if($errors->has('destination')) aria-invalid="true" @endif
                               class="mt-1 w-full rounded-xl border px-3 py-2.5 {{ $errors->has('destination') ? 'border-red-500 bg-red-50 text-red-900 ring-1 ring-red-500' : 'border-slate-300 bg-slate-100 text-slate-700' }}"
                               placeholder="Se tomará del Job Empaque">
                        <p id="destinationWarning"
                           class="mt-1 text-xs font-medium text-red-700 @unless($errors->has('destination')) hidden @endunless"
                           role="alert"
                           aria-live="polite">{{ $errors->first('destination') }}</p>
                    </div>

                    <div>
                        <label for="modelDisplay" class="text-sm font-semibold text-slate-700">Modelo <span class="font-normal text-slate-500">(informativo)</span></label>
                        <input id="modelDisplay"
                               value=""
                               readonly
                               aria-readonly="true"
                               aria-describedby="modelMappingWarning"
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2.5 text-slate-700"
                               placeholder="Se resolverá desde Master Model Mapping">
                        <p id="modelMappingWarning"
                           class="mt-1 hidden text-xs font-medium text-red-700"
                           role="alert"
                           aria-live="polite"></p>
                    </div>
                </div>

                <p class="text-xs text-slate-500 md:col-span-2">PO y Destino se toman exclusivamente del Job Empaque registrado en Oracle. El Modelo corresponde al Job Empaque cuando está capturado; de lo contrario, al Job Ensamble.</p>
            </div>
        </section>

        {{-- 2) TIPO DE MASTER --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-start gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-600 text-sm font-bold text-white">2</span>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Selecciona el tipo de Master</h2>
                    <p class="mt-1 text-sm text-slate-500">Las opciones disponibles se calculan con las líneas encontradas en Oracle.</p>
                </div>
            </div>

            <div class="space-y-4 p-5">
                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    <span class="font-semibold">Qué debes hacer:</span> cuando Oracle confirme los Jobs, elige el tipo de hoja Master y revisa la línea oficial y el destino de inventario.
                </div>

                <div>
                    <label for="requestType" class="text-sm font-semibold text-slate-700">Tipo de Master <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="requestType"
                            name="request_type"
                            data-ort-assembly-type="{{ $ortAssemblyConfig['type'] }}"
                            data-initial-value="{{ old('request_type') }}"
                            disabled
                            required
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                        <option value="">Captura y valida primero los Jobs...</option>
                        @foreach($masterRequestTypes as $requestType => $requestTypeData)
                            <option value="{{ $requestType }}"
                                    data-line-types="{{ implode('|', $requestTypeData['line_types']) }}"
                                    @selected(old('request_type') === $requestType)>
                                {{ $requestTypeData['label'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('request_type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="productionContextStatus"
                     class="hidden rounded-xl border px-4 py-3 text-sm"
                     role="status"
                     aria-live="polite">
                    <p id="productionContextStatusTitle" class="font-semibold"></p>
                    <p id="productionContextStatusMessage" class="mt-1"></p>
                </div>

                <div id="lineDifferenceStatus"
                     class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                     role="alert">
                    <p class="font-semibold">Los Jobs tienen líneas diferentes</p>
                    <p id="lineDifferenceMessage" class="mt-1"></p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Línea oficial</div>
                        <div id="officialLineDisplay" class="mt-1 font-semibold text-slate-900">—</div>
                    </div>

                    <div>
                        <label for="localInput" class="text-sm font-semibold text-slate-700">Stock Locator (Local)</label>
                        <select id="localInput"
                                name="local"
                                data-ort-default-value="{{ $ortAssemblyConfig['default_local'] }}"
                                data-alternate-value="{{ $alternateStockLocator }}"
                                data-initial-value="{{ $initialLocal }}"
                                class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 uppercase text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-600">
                            <option value="" @selected($initialLocal === '')>Se resolverá desde la línea oficial</option>
                            @if($initialLocal !== '')
                                <option value="{{ $initialLocal }}" selected>{{ $initialLocal }}</option>
                            @endif
                            @if($initialLocal !== $alternateStockLocator)
                                <option value="{{ $alternateStockLocator }}">{{ $alternateStockLocator }}</option>
                            @endif
                        </select>
                        @error('local')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="subinventoryInput" class="text-sm font-semibold text-slate-700">Subinventory</label>
                        <input id="subinventoryInput"
                               name="subinventory"
                               value="{{ old('subinventory') }}"
                               maxlength="20"
                               pattern="^[A-Za-z0-9\-._]+$"
                               data-ort-default-value="{{ $ortAssemblyConfig['default_subinventory'] }}"
                               data-initial-value="{{ old('request_type') === $ortAssemblyConfig['type'] ? old('subinventory') : '' }}"
                               readonly
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2.5 uppercase text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se resolverá desde la línea oficial">
                        @error('subinventory')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- 3) FOLIOS Y CANTIDADES --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-start gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-sm font-bold text-white">3</span>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Define folios y cantidades</h2>
                    <p class="mt-1 text-sm text-slate-500">Captura el rango, las piezas por pallet y si se trata de una reposición.</p>
                </div>
            </div>

            <div class="space-y-4 p-5">
                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    <span class="font-semibold">Qué debes hacer:</span> indica el primer y último folio del rango. Si el último pallet tiene menos piezas, completa también los dos campos de parcial.
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label for="foliosFrom" class="text-sm font-semibold text-slate-700">Folio inicial <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input id="foliosFrom" name="folios_from"
                               type="number"
                               min="1"
                               value="{{ old('folios_from') }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600"
                               required>
                        @error('folios_from')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="foliosTo" class="text-sm font-semibold text-slate-700">Folio final <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input id="foliosTo" name="folios_to"
                               type="number"
                               min="{{ old('folios_from', 1) }}"
                               value="{{ old('folios_to') }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600"
                               required>
                        @error('folios_to')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="stdPackQty" class="text-sm font-semibold text-slate-700">Std pack (pzas/pallet) <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input id="stdPackQty" name="std_pack_qty"
                               type="number"
                               min="1"
                               value="{{ old('std_pack_qty') }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600"
                               required>
                        @error('std_pack_qty')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="requestKind" class="text-sm font-semibold text-slate-700">Tipo de solicitud <span class="text-red-600" aria-hidden="true">*</span></label>
                        <select id="requestKind" name="kind"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600"
                                required>
                            <option value="new" @selected(old('kind', 'new') === 'new')>Nuevo</option>
                            <option value="reposition" @selected(old('kind') === 'reposition')>Reposición</option>
                        </select>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-sm font-semibold text-slate-800">Pallet parcial <span class="font-normal text-slate-500">(opcional)</span></div>
                    <p class="mt-1 text-sm text-slate-500">Úsalo cuando el último pallet no está completo.</p>

                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="partialFolio" class="text-sm font-semibold text-slate-700">Folio parcial</label>
                            <input id="partialFolio" name="partial_folio"
                                   type="number"
                                   min="1"
                                   value="{{ old('partial_folio') }}"
                                   class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                            @error('partial_folio')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="partialQty" class="text-sm font-semibold text-slate-700">Pzas pallet parcial</label>
                            <input id="partialQty" name="partial_qty"
                                   type="number"
                                   min="1"
                                   value="{{ old('partial_qty') }}"
                                   class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">
                            @error('partial_qty')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div id="folioLiveValidation"
                     class="hidden space-y-3"
                     role="status"
                     aria-live="polite"></div>

                <div>
                    <label for="requestNotes" class="text-sm font-semibold text-slate-700">Notas <span class="font-normal text-slate-500">(opcional)</span></label>
                    <textarea id="requestNotes" name="notes"
                              rows="3"
                              maxlength="1000"
                              class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>

        {{-- 4) RESUMEN Y CONFIRMACIÓN --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-start gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-600 text-sm font-bold text-white">4</span>
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Revisa la requisición</h2>
                    <p class="mt-1 text-sm text-slate-500">Este resumen se actualiza conforme completas el formulario.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-2 xl:grid-cols-3" aria-live="polite">
                <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jobs</div>
                    <div id="summaryJobs" class="mt-1 break-words text-sm font-semibold text-slate-900">—</div>
                </div>
                <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de Master</div>
                    <div id="summaryType" class="mt-1 break-words text-sm font-semibold text-slate-900">—</div>
                </div>
                <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Línea oficial</div>
                    <div id="summaryOfficialLine" class="mt-1 break-words text-sm font-semibold text-slate-900">—</div>
                </div>
                <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Destino de inventario</div>
                    <div id="summaryInventory" class="mt-1 break-words text-sm font-semibold text-slate-900">—</div>
                </div>
                <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Folios</div>
                    <div id="summaryFolios" class="mt-1 break-words text-sm font-semibold text-slate-900">—</div>
                </div>
                <div class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Solicitud</div>
                    <div id="summaryRequest" class="mt-1 break-words text-sm font-semibold text-slate-900">—</div>
                </div>
            </div>
        </section>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <button
                type="submit"
                class="min-h-12 w-full rounded-xl bg-red-600 px-4 py-3 font-semibold text-white transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                Revisar y enviar requisición
            </button>
            <p class="mt-3 text-center text-xs text-slate-500">
                Label Room recibirá la solicitud y el Kiosko imprimirá el comprobante con el nombre de quien solicita.
            </p>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/kiosk-master-requests-create.js')
@endpush
