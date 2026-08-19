@extends('layouts.app', ['title' => 'Nueva requisición Master'])

@section('content')
<div class="rounded-2xl bg-white p-6 shadow">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Nueva requisición Master</h1>
            <p class="mt-1 text-slate-600">Captura los Jobs del formato físico y el sistema resolverá la línea desde Oracle.</p>
        </div>

        <a href="{{ route('dashboard') }}"
           class="shrink-0 rounded-xl border px-4 py-2 text-sm transition hover:bg-slate-50">
            Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form id="masterRequestCreate"
          data-lookup-url="{{ route('oracle.lookup_job') }}"
          data-request-source="label_room"
          data-after-print-url="{{ route('master_requests.create') }}"
          class="mt-6 space-y-4"
          method="POST"
          action="{{ route('master_requests.store') }}">
        @csrf

        {{-- 1) JOBS ORACLE --}}
        <details open class="group rounded-2xl border">
            <summary class="flex cursor-pointer select-none items-center justify-between px-4 py-3">
                <div>
                    <div class="font-semibold text-slate-900">1) Jobs Oracle</div>
                    <div class="text-xs text-slate-500">Consulta los Jobs para identificar automáticamente sus líneas de producción.</div>
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

                <div class="grid grid-cols-1 gap-4 md:col-span-2 md:grid-cols-3">
                    <div>
                        <label for="poNumber" class="text-sm text-slate-600">Custom PO</label>
                        <input id="poNumber"
                               name="po_number"
                               value="{{ old('po_number') }}"
                               maxlength="80"
                               pattern="[A-Za-z0-9\-\/_\s]+"
                               readonly
                               aria-readonly="true"
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700"
                               placeholder="Se tomará del Job Empaque">
                    </div>

                    <div>
                        <label for="destination" class="text-sm text-slate-600">Destino (Ship Code)</label>
                        <input id="destination"
                               name="destination"
                               value="{{ old('destination') }}"
                               maxlength="80"
                               pattern="[A-Za-z0-9\-\/_\s]+"
                               readonly
                               aria-readonly="true"
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700"
                               placeholder="Se tomará del Job Empaque">
                    </div>

                    <div>
                        <label for="modelDisplay" class="text-sm text-slate-600">Modelo</label>
                        <input id="modelDisplay"
                               value=""
                               readonly
                               aria-readonly="true"
                               aria-describedby="modelMappingWarning"
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700"
                               placeholder="Se resolverá desde Master Model Mapping">
                        <p id="modelMappingWarning"
                           class="mt-1 hidden text-xs font-medium text-red-700"
                           role="alert"
                           aria-live="polite"></p>
                    </div>
                </div>

                <p class="text-xs text-slate-500 md:col-span-2">PO y Destino se toman exclusivamente del Job Empaque registrado en Oracle. El Modelo corresponde al Job Empaque cuando está capturado; de lo contrario, al Job Ensamble.</p>
            </div>
        </details>

        {{-- 2) TIPO DE MASTER --}}
        <details open class="group rounded-2xl border">
            <summary class="flex cursor-pointer select-none items-center justify-between px-4 py-3">
                <div>
                    <div class="font-semibold text-slate-900">2) Tipo de Master filtrado</div>
                    <div class="text-xs text-slate-500">Las opciones disponibles se calculan con las líneas encontradas en Oracle.</div>
                </div>
                <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
            </summary>

            <div class="space-y-4 border-t p-4">
                <div>
                    <label for="requestType" class="text-sm text-slate-600">Tipo de Master</label>
                    <select id="requestType"
                            name="request_type"
                            data-ort-assembly-type="{{ $ortAssemblyConfig['type'] }}"
                            data-initial-value="{{ old('request_type') }}"
                            disabled
                            required
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
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
                    <div class="rounded-xl border bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Línea oficial</div>
                        <div id="officialLineDisplay" class="mt-1 font-semibold text-slate-900">—</div>
                    </div>

                    <div>
                        <label for="localInput" class="text-sm text-slate-600">Stock Locator (Local)</label>
                        <input id="localInput"
                               name="local"
                               value="{{ old('local') }}"
                               maxlength="20"
                               pattern="^[A-Za-z0-9\-._]+$"
                               data-ort-default-value="{{ $ortAssemblyConfig['default_local'] }}"
                               data-initial-value="{{ old('request_type') === $ortAssemblyConfig['type'] ? old('local') : '' }}"
                               readonly
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 uppercase text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se resolverá desde la línea oficial">
                        @error('local')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="subinventoryInput" class="text-sm text-slate-600">Subinventory</label>
                        <input id="subinventoryInput"
                               name="subinventory"
                               value="{{ old('subinventory') }}"
                               maxlength="20"
                               pattern="^[A-Za-z0-9\-._]+$"
                               data-ort-default-value="{{ $ortAssemblyConfig['default_subinventory'] }}"
                               data-initial-value="{{ old('request_type') === $ortAssemblyConfig['type'] ? old('subinventory') : '' }}"
                               readonly
                               class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 uppercase text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se resolverá desde la línea oficial">
                        @error('subinventory')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
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
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                               required>
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">al</label>
                        <input name="folios_to"
                               type="number"
                               min="{{ old('folios_from', 1) }}"
                               value="{{ old('folios_to') }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                               required>
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Std pack (pzas/pallet)</label>
                        <input name="std_pack_qty"
                               type="number"
                               min="1"
                               value="{{ old('std_pack_qty') }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Tipo</label>
                        <select name="kind"
                                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                                required>
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
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Línea oficial</div>
                    <div id="summaryOfficialLine" class="mt-1 text-sm font-semibold text-slate-900">—</div>
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
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Solicitud</div>
                    <div id="summaryRequest" class="mt-1 text-sm font-semibold text-slate-900">—</div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-3 pt-2 sm:grid-cols-2">
            <button
                type="submit"
                name="submission_action"
                value="save"
                class="rounded-xl border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-wait disabled:opacity-60">
                Guardar solamente
            </button>

            <button
                type="submit"
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
    @vite('resources/js/pages/master-requests-create.js')
@endpush
