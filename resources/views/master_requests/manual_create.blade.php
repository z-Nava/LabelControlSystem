@extends('layouts.app', ['title' => 'Nueva requisición Master Manual'])

@section('content')
<div class="rounded-2xl bg-white p-6 shadow">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Nueva requisición Master Manual</h1>
            <p class="mt-1 text-slate-600">Captura los Jobs del formato físico; el sistema propondrá los datos y podrás modificarlos.</p>
        </div>

        <a href="{{ route('dashboard') }}"
           class="shrink-0 rounded-xl border px-4 py-2 text-sm transition hover:bg-slate-50">
            Volver
        </a>
    </div>

    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
        <span class="font-semibold">Flujo Master Manual:</span>
        estás creando una requisición con datos operativos editables. Verifica cuidadosamente la información antes de guardarla o imprimirla.
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form id="manualMasterRequestCreate"
          data-lookup-url="{{ route('oracle.lookup_job') }}"
          data-after-print-url="{{ route('manual_master_requests.create') }}"
          class="mt-6 space-y-4"
          method="POST"
          action="{{ route('manual_master_requests.store') }}">
        @csrf

        {{-- 1) JOBS ORACLE --}}
        <details open class="group rounded-2xl border">
            <summary class="flex cursor-pointer select-none items-center justify-between px-4 py-3">
                <div>
                    <div class="font-semibold text-slate-900">1) Jobs Oracle</div>
                    <div class="text-xs text-slate-500">Consulta los Jobs para validar su existencia y clasificación.</div>
                </div>
                <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
            </summary>

            <div class="grid grid-cols-1 gap-4 border-t p-4 md:grid-cols-2">
                <div class="rounded-xl border bg-slate-50 p-4">
                    <label for="jobAssembly" class="text-sm font-medium text-slate-700">Job Ensamble</label>
                    <input id="jobAssembly"
                           name="job_assembly"
                           value="{{ old('job_assembly') }}"
                           maxlength="40"
                           pattern="^[0-9A-Za-z\-]+$"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                           placeholder="Ej: 393383">
                    <p id="jobAssemblyQty" class="mt-2 text-sm text-slate-600">Cantidad del Job: —</p>
                    <p id="jobAssemblyHint" class="mt-2 text-xs text-slate-500" aria-live="polite"></p>
                    @error('job_assembly')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border bg-slate-50 p-4">
                    <label for="jobPackaging" class="text-sm font-medium text-slate-700">Job Empaque (si aplica)</label>
                    <input id="jobPackaging"
                           name="job_packaging"
                           value="{{ old('job_packaging') }}"
                           maxlength="40"
                           pattern="^[0-9A-Za-z\-]+$"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                           placeholder="Opcional">
                    <p id="jobPackagingQty" class="mt-2 text-sm text-slate-600">Cantidad del Job: —</p>
                    <p id="jobPackagingHint" class="mt-2 text-xs text-slate-500" aria-live="polite"></p>
                    @error('job_packaging')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 md:col-span-2 md:grid-cols-3">
                    <div>
                        <label for="poNumber" class="text-sm text-slate-600">Custom PO</label>
                        <input id="poNumber"
                               name="po_number"
                               value="{{ old('po_number') }}"
                               maxlength="80"
                               pattern="[A-Za-z0-9\-\/_\s]+"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se propondrá desde el Job Empaque">
                    </div>

                    <div>
                        <label for="destination" class="text-sm text-slate-600">Destino (Ship Code)</label>
                        <input id="destination"
                               name="destination"
                               value="{{ old('destination') }}"
                               maxlength="80"
                               pattern="[A-Za-z0-9\-\/_\s]+"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se propondrá desde el Job Empaque">
                    </div>

                    <div>
                        <label for="modelDisplay" class="text-sm text-slate-600">Modelo</label>
                        <input id="modelDisplay"
                               name="model"
                               value="{{ old('model') }}"
                               required
                               maxlength="120"
                               aria-describedby="modelMappingWarning"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se propondrá desde Master Model Mapping">
                        <p id="modelMappingWarning"
                           class="mt-1 hidden text-xs font-medium text-amber-700"
                           role="status"
                           aria-live="polite"></p>
                    </div>
                </div>

                <p class="text-xs text-slate-500 md:col-span-2">PO y Destino se proponen desde el Job Empaque. El Modelo se propone desde Master Model Mapping. En este flujo los tres campos permanecen editables.</p>
            </div>
        </details>

        {{-- 2) TIPO Y CONTEXTO --}}
        <details open class="group rounded-2xl border">
            <summary class="flex cursor-pointer select-none items-center justify-between px-4 py-3">
                <div>
                    <div class="font-semibold text-slate-900">2) Tipo de Master</div>
                    <div class="text-xs text-slate-500">Selecciona el formato y revisa los datos propuestos desde Oracle.</div>
                </div>
                <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
            </summary>

            <div class="space-y-4 border-t p-4">
                <div>
                    <label for="requestType" class="text-sm text-slate-600">Tipo de Master</label>
                    <select id="requestType"
                            name="request_type"
                            data-ort-assembly-type="{{ $ortAssemblyConfig['type'] }}"
                            required
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                        <option value="">Selecciona el tipo de hoja Master...</option>
                        @foreach($masterRequestTypes as $requestType => $requestTypeData)
                            <option value="{{ $requestType }}" @selected(old('request_type') === $requestType)>
                                {{ $requestTypeData['label'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('request_type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="jobValidationStatus"
                     class="hidden rounded-xl border px-4 py-3 text-sm"
                     role="status"
                     aria-live="polite"></div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Línea oficial</div>
                        <div id="officialLineDisplay" class="mt-1 font-semibold text-slate-900">—</div>
                    </div>

                    <div>
                        <label for="oracleLineInput" class="text-sm text-slate-600">Línea para la requisición</label>
                        <input id="oracleLineInput"
                               name="oracle_line"
                               value="{{ old('oracle_line') }}"
                               required
                               maxlength="40"
                               pattern="^[A-Za-z0-9\-._\s]+$"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se propondrá desde Oracle">
                    </div>

                    <div>
                        <label for="localInput" class="text-sm text-slate-600">Stock Locator (Local)</label>
                        <input id="localInput"
                               name="local"
                               value="{{ old('local') }}"
                               maxlength="20"
                               data-ort-default-value="{{ $ortAssemblyConfig['default_local'] }}"
                               pattern="^[A-Za-z0-9\-._\s]+$"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se propondrá desde la línea oficial">
                    </div>

                    <div>
                        <label for="subinventoryInput" class="text-sm text-slate-600">Subinventory</label>
                        <input id="subinventoryInput"
                               name="subinventory"
                               value="{{ old('subinventory') }}"
                               maxlength="20"
                               data-ort-default-value="{{ $ortAssemblyConfig['default_subinventory'] }}"
                               pattern="^[A-Za-z0-9\-._\s]+$"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se propondrá desde la línea oficial">
                    </div>
                </div>

                <p class="text-xs text-slate-500">La línea oficial se conserva como referencia. Línea, Stock Locator y Subinventory pueden modificarse para esta requisición.</p>
            </div>
        </details>

        {{-- 3) FOLIOS Y CANTIDADES --}}
        <details open class="group rounded-2xl border">
            <summary class="flex cursor-pointer select-none items-center justify-between px-4 py-3">
                <div>
                    <div class="font-semibold text-slate-900">3) Folios y cantidades</div>
                    <div class="text-xs text-slate-500">Define el rango, las cantidades y si se trata de una reposición.</div>
                </div>
                <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
            </summary>

            <div class="space-y-4 border-t p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="text-sm text-slate-600">Folios del</label>
                        <input name="folios_from"
                               type="number"
                               min="1"
                               value="{{ old('folios_from') }}"
                               required
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">al</label>
                        <input name="folios_to"
                               type="number"
                               min="{{ old('folios_from', 1) }}"
                               value="{{ old('folios_to') }}"
                               required
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Std pack (pzas/pallet)</label>
                        <input name="std_pack_qty"
                               type="number"
                               min="1"
                               value="{{ old('std_pack_qty') }}"
                               required
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Tipo</label>
                        <select name="kind"
                                required
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                            <option value="new" @selected(old('kind', 'new') === 'new')>Nuevo</option>
                            <option value="reposition" @selected(old('kind') === 'reposition')>Reposición</option>
                        </select>
                    </div>
                </div>

                <div class="rounded-xl border bg-slate-50 p-3">
                    <div class="text-sm font-semibold text-slate-800">Parcial (opcional)</div>
                    <p class="mt-1 text-xs text-slate-500">Úsalo cuando el último pallet no está completo.</p>

                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm text-slate-600">Folio parcial</label>
                            <input name="partial_folio"
                                   type="number"
                                   min="1"
                                   value="{{ old('partial_folio') }}"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                        </div>

                        <div>
                            <label class="text-sm text-slate-600">Pzas pallet parcial</label>
                            <input name="partial_qty"
                                   type="number"
                                   min="1"
                                   value="{{ old('partial_qty') }}"
                                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                        </div>
                    </div>
                </div>

                <div id="folioLiveValidation"
                     class="hidden space-y-3"
                     role="status"
                     aria-live="polite"></div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <label for="manualReason" class="text-sm font-semibold text-amber-950">Motivo del Master Manual</label>
                    <textarea id="manualReason"
                              name="manual_reason"
                              rows="3"
                              required
                              minlength="3"
                              maxlength="1000"
                              class="mt-2 w-full rounded-xl border border-amber-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500"
                              placeholder="Describe por qué se requiere crear la requisición mediante el flujo Master Manual...">{{ old('manual_reason') }}</textarea>
                    <p class="mt-1 text-xs text-amber-800">El motivo quedará visible en el detalle de la requisición.</p>
                </div>

                <div>
                    <label class="text-sm text-slate-600">Notas (opcional)</label>
                    <textarea name="notes"
                              rows="3"
                              maxlength="1000"
                              class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">{{ old('notes') }}</textarea>
                </div>
            </div>
        </details>

        {{-- 4) RESUMEN Y CONFIRMACIÓN --}}
        <section class="rounded-2xl border">
            <div class="px-4 py-3">
                <div class="font-semibold text-slate-900">4) Resumen y confirmación</div>
                <div class="text-xs text-slate-500">Verifica la información que se guardará antes de enviar la requisición.</div>
            </div>

            <div class="grid grid-cols-1 gap-3 border-t p-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl border bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jobs</div>
                    <div id="summaryJobs" class="mt-1 text-sm font-semibold text-slate-900">—</div>
                </div>
                <div class="rounded-xl border bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de Master</div>
                    <div id="summaryType" class="mt-1 text-sm font-semibold text-slate-900">—</div>
                </div>
                <div class="rounded-xl border bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Línea</div>
                    <div id="summaryLine" class="mt-1 text-sm font-semibold text-slate-900">—</div>
                </div>
                <div class="rounded-xl border bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Destino de inventario</div>
                    <div id="summaryInventory" class="mt-1 text-sm font-semibold text-slate-900">—</div>
                </div>
                <div class="rounded-xl border bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Folios</div>
                    <div id="summaryFolios" class="mt-1 text-sm font-semibold text-slate-900">—</div>
                </div>
                <div class="rounded-xl border bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Flujo</div>
                    <div id="summaryRequest" class="mt-1 text-sm font-semibold text-slate-900">Master Manual</div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-3 pt-2 sm:grid-cols-2">
            <button type="submit"
                    name="submission_action"
                    value="save"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-wait disabled:opacity-60">
                Guardar solamente
            </button>

            <button type="submit"
                    name="submission_action"
                    value="save_and_print"
                    formtarget="_blank"
                    class="rounded-xl bg-red-600 px-4 py-3 font-semibold text-white transition hover:bg-red-500 disabled:cursor-wait disabled:opacity-60">
                Guardar e imprimir
            </button>
        </div>

        <p class="text-center text-xs text-slate-500">
            Guardar e imprimir creará un batch con todos los folios de esta requisición, una copia por folio.
        </p>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/manual-master-requests-create.js')
@endpush
