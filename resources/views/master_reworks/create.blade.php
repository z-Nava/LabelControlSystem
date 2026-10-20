@extends(($kioskMode ?? false) ? 'layouts.kiosk' : 'layouts.app', ['title' => 'Retrabajar requisición Master'])

@section('content')
@php
    $action = $action ?? null;
    $kioskMode = $kioskMode ?? false;
    $requestedNumbers = $action?->selectedFolios->pluck('folio_number')->all();
    $initialNumbers = $requestedNumbers ?? $masterRequest->folios->pluck('folio_number')->all();
    $selectedFolios = collect(old('folio_selection_submitted') ? old('selected_folio_numbers', []) : $initialNumbers)
        ->map(fn ($folio) => (int) $folio);
    $originalPartial = $action ? null : $masterRequest->folios->firstWhere('is_partial', true);
@endphp

<div class="rounded-2xl bg-white p-6 shadow">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ $kioskMode ? 'Preparar retrabajo Master #'.$masterRequest->id : ($action ? 'Preparar retrabajo solicitado desde Kiosk #'.$action->id : 'Retrabajar requisición Master #'.$masterRequest->id) }}</h1>
            <p class="mt-1 text-slate-600">{{ $kioskMode ? 'Ajusta los datos y las piezas por folio. Label Room validará el retrabajo y después imprimirá los folios.' : 'Ajusta los datos y las piezas por folio antes de confirmar. La requisición y sus impresiones anteriores permanecerán intactas.' }}</p>
        </div>
        <a href="{{ $kioskMode ? route('kiosk.master_action_requests.create', ['type' => 'rework', 'q' => $masterRequest->job_assembly ?: $masterRequest->job_packaging]) : ($action ? route('master_action_requests.show', $action) : route('master_reprints.search', ['job' => $masterRequest->job_assembly ?: $masterRequest->job_packaging])) }}"
           class="rounded-xl border px-4 py-2 text-center text-sm hover:bg-slate-50">Volver</a>
    </div>

    @if($action)
        <section class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
            <p><strong>Solicitó:</strong> {{ $action->requested_by_name }} · <strong>Requisición origen:</strong> #{{ $masterRequest->id }}</p>
            <p class="mt-2"><strong>Motivo:</strong> {{ $action->reason }}</p>
            <p class="mt-2"><strong>Folios originales solicitados:</strong> {{ collect($requestedNumbers)->map(fn ($number) => 'F'.$number)->join(', ') ?: 'Ninguno' }} · <strong>Folios adicionales solicitados:</strong> {{ $action->additional_folios_count }}</p>
            <p class="mt-2">Puedes ajustar todos los datos del retrabajo antes de confirmar. La solicitud original de Kiosk se conserva para consulta.</p>
        </section>
    @endif

    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Información original</div>
        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div><div class="text-xs text-slate-500">Jobs</div><div class="font-semibold">{{ $masterRequest->job_assembly ?: '—' }} / {{ $masterRequest->job_packaging ?: '—' }}</div></div>
            <div><div class="text-xs text-slate-500">Línea</div><div class="font-semibold">{{ $masterRequest->line?->code ?: '—' }}</div></div>
            <div><div class="text-xs text-slate-500">Local / Subinventory</div><div class="font-semibold">{{ $masterRequest->local ?: '—' }} / {{ $masterRequest->subinventory ?: '—' }}</div></div>
            <div><div class="text-xs text-slate-500">Modelo</div><div class="font-semibold">{{ $initialModel ?: '—' }}</div></div>
            <div><div class="text-xs text-slate-500">Std pack</div><div class="font-semibold">{{ $masterRequest->std_pack_qty ?: '—' }}</div></div>
            <div><div class="text-xs text-slate-500">Custom PO</div><div class="font-semibold">{{ $masterRequest->po_number ?: '—' }}</div></div>
            <div><div class="text-xs text-slate-500">Destino</div><div class="font-semibold">{{ $masterRequest->destination ?: '—' }}</div></div>
            <div><div class="text-xs text-slate-500">Origen</div><div class="font-semibold">{{ $masterRequest->request_source_label }}</div></div>
        </div>
    </section>

    <form id="masterReworkForm"
          data-lookup-url="{{ $kioskMode ? route('kiosk.master_requests.lookup_job') : route('oracle.lookup_job') }}"
          data-kiosk-mode="{{ $kioskMode ? '1' : '0' }}"
          data-original-max-folio="{{ $masterRequest->folios->max('folio_number') }}"
          data-ort-type="{{ $ortAssemblyConfig['type'] }}"
          data-ort-local="{{ $ortAssemblyConfig['default_local'] }}"
          data-ort-subinventory="{{ $ortAssemblyConfig['default_subinventory'] }}"
          data-preserve-final-values="{{ $errors->any() || $kioskMode ? '1' : '0' }}"
          class="mt-5 space-y-5"
          method="POST"
          action="{{ $kioskMode ? route('kiosk.master_action_requests.rework.store', $masterRequest) : ($action ? route('master_action_requests.rework.store', $action) : route('master_reworks.store', $masterRequest)) }}">
        @csrf
        <input type="hidden" name="folio_selection_submitted" value="1">

        <section class="rounded-2xl border">
            <div class="border-b px-4 py-3">
                <h2 class="font-semibold text-slate-900">1) Jobs y tipo de Master</h2>
                <p class="text-xs text-slate-500">Los Jobs se validan contra Oracle igual que en una requisición nueva.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
                <div class="rounded-xl border bg-slate-50 p-4">
                    <label for="jobAssembly" class="text-sm font-medium text-slate-700">Job Ensamble</label>
                    <input id="jobAssembly" name="job_assembly" value="{{ old('job_assembly', $masterRequest->job_assembly) }}"
                           maxlength="40" pattern="^[0-9A-Za-z\-]+$"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-red-600">
                    <p id="jobAssemblyQty" class="mt-2 text-sm text-slate-600">Cantidad del Job: —</p>
                    <p id="jobAssemblyHint" class="mt-1 text-xs text-slate-500"></p>
                    <div id="jobAssemblyContext" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                </div>

                <div class="rounded-xl border bg-slate-50 p-4">
                    <label for="jobPackaging" class="text-sm font-medium text-slate-700">Job Empaque (si aplica)</label>
                    <input id="jobPackaging" name="job_packaging" value="{{ old('job_packaging', $masterRequest->job_packaging) }}"
                           maxlength="40" pattern="^[0-9A-Za-z\-]+$"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-red-600">
                    <p id="jobPackagingQty" class="mt-2 text-sm text-slate-600">Cantidad del Job: —</p>
                    <p id="jobPackagingHint" class="mt-1 text-xs text-slate-500"></p>
                    <div id="jobPackagingContext" class="mt-3 hidden rounded-lg border p-3 text-sm"></div>
                </div>

                <div class="md:col-span-2">
                    <label for="requestType" class="text-sm text-slate-600">Tipo de Master</label>
                    <select id="requestType" name="request_type" data-initial-value="{{ old('request_type', $masterRequest->request_type) }}"
                            required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-red-600">
                        <option value="">Selecciona el tipo de hoja Master...</option>
                        @foreach($masterRequestTypes as $requestType => $requestTypeData)
                            <option value="{{ $requestType }}" @selected(old('request_type', $masterRequest->request_type) === $requestType)>
                                {{ $requestTypeData['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <p id="requestTypeHint" class="mt-1 text-xs text-slate-500">Las opciones se filtrarán con la línea oficial de cada Job.</p>
                </div>

                <div>
                    <label for="poNumber" class="text-sm text-slate-600">Custom PO</label>
                    <input id="poNumber" name="po_number" value="{{ old('po_number', $masterRequest->po_number) }}"
                           maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+" autocomplete="off"
                           class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-700 focus:ring-2 focus:ring-red-600">
                </div>
                <div>
                    <label for="destination" class="text-sm text-slate-600">Destino</label>
                    <input id="destination" name="destination" value="{{ old('destination', $masterRequest->destination) }}"
                           maxlength="80" pattern="[A-Za-z0-9,\-\/_\s]+" autocomplete="off"
                           class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-700 focus:ring-2 focus:ring-red-600">
                </div>
                <p class="text-xs text-slate-500 md:col-span-2">Oracle propone estos valores desde el Job Empaque; puedes ajustarlos para esta revisión.</p>
            </div>
        </section>

        <section class="rounded-2xl border">
            <div class="border-b px-4 py-3">
                <h2 class="font-semibold text-slate-900">2) Contexto resuelto y valor final</h2>
                <p class="text-xs text-slate-500">Oracle propone los valores; confirma los campos finales del retrabajo.</p>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-3"><div class="text-xs text-blue-700">Línea oficial Oracle</div><div id="resolvedLine" class="font-semibold">—</div></div>
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-3"><div class="text-xs text-blue-700">Local sugerido</div><div id="resolvedLocal" class="font-semibold">—</div></div>
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-3"><div class="text-xs text-blue-700">Subinventory sugerido</div><div id="resolvedSubinventory" class="font-semibold">—</div></div>
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-3"><div class="text-xs text-blue-700">Modelo sugerido</div><div id="resolvedModel" class="font-semibold">—</div></div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label for="finalLine" class="text-sm text-slate-600">Línea final</label>
                        <select id="finalLine" name="line_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-red-600">
                            <option value="">Selecciona una línea...</option>
                            @foreach($lines as $line)
                                <option value="{{ $line->id }}"
                                        data-code="{{ $line->code }}"
                                        data-local="{{ $line->suggested_local }}"
                                        data-subinventory="{{ $line->suggested_subinventory }}"
                                        @selected((int) old('line_id', $masterRequest->line_id) === $line->id)>
                                    {{ $line->code }} · {{ $line->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="localInput" class="text-sm text-slate-600">Local final</label>
                        <input id="localInput" name="local" value="{{ old('local', $masterRequest->local) }}" maxlength="20"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:ring-2 focus:ring-red-600">
                    </div>
                    <div>
                        <label for="subinventoryInput" class="text-sm text-slate-600">Subinventory final</label>
                        <input id="subinventoryInput" name="subinventory" value="{{ old('subinventory', $masterRequest->subinventory) }}" maxlength="20"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:ring-2 focus:ring-red-600">
                    </div>
                    <div>
                        <label for="modelInput" class="text-sm text-slate-600">Modelo final</label>
                        <input id="modelInput" name="model" value="{{ old('model', $initialModel) }}" maxlength="120"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:ring-2 focus:ring-red-600">
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border">
            <div class="border-b px-4 py-3">
                <h2 class="font-semibold text-slate-900">3) Folios y cantidades</h2>
                <p class="text-xs text-slate-500">Puedes conservar folios sueltos. Los nuevos continuarán después del máximo original.</p>
            </div>
            <div class="p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="selectAllFolios" class="rounded-lg border px-3 py-1.5 text-sm hover:bg-slate-50">Seleccionar todos</button>
                    <button type="button" id="clearAllFolios" class="rounded-lg border px-3 py-1.5 text-sm hover:bg-slate-50">Limpiar</button>
                    <div id="folioSummary" class="ml-auto text-sm font-medium text-slate-700"></div>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($masterRequest->folios as $folio)
                        @php
                            $folioQuantity = $folio->qty_for_folio !== null
                                ? number_format((int) $folio->qty_for_folio).' pzas'
                                : 'Cantidad no registrada';
                        @endphp
                        <label class="flex cursor-pointer items-start gap-2 rounded-xl border p-3 hover:bg-slate-50">
                            <input type="checkbox" name="selected_folio_numbers[]" value="{{ $folio->folio_number }}"
                                   data-folio-number="{{ $folio->folio_number }}"
                                   data-is-partial="{{ $folio->is_partial ? '1' : '0' }}"
                                   class="original-folio mt-0.5 shrink-0 rounded border-slate-300"
                                   @checked($selectedFolios->contains((int) $folio->folio_number))>
                            <span class="min-w-0 text-sm">
                                <strong class="block">F{{ str_pad((string) $folio->folio_number, 2, '0', STR_PAD_LEFT) }}</strong>
                                @if($masterRequest->request_type === \App\Models\MasterModelMapping::TYPE_ASSEMBLY_PACKAGING)
                                    <span class="block text-slate-700">Ensamble: {{ $folioQuantity }}</span>
                                    <span class="block text-slate-700">Empaque: {{ $folioQuantity }}</span>
                                @else
                                    <span class="block text-slate-700">Cantidad: {{ $folioQuantity }}</span>
                                @endif
                                @if($folio->is_partial)<span class="block text-xs text-purple-700">Parcial</span>@endif
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label for="additionalFolios" class="text-sm text-slate-600">Folios nuevos a agregar</label>
                        <input id="additionalFolios" name="additional_folios_count" type="number" min="0" max="500"
                               value="{{ old('additional_folios_count', $action?->additional_folios_count ?? 0) }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    </div>
                    <div>
                        <label for="stdPack" class="text-sm text-slate-600">Std pack (pzas/pallet)</label>
                        <input id="stdPack" name="std_pack_qty" type="number" min="1" value="{{ old('std_pack_qty', $masterRequest->std_pack_qty) }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label for="partialFolio" class="text-sm text-slate-600">Folio parcial (opcional)</label>
                        <input id="partialFolio" name="partial_folio" type="number" min="1"
                               value="{{ old('partial_folio', $originalPartial?->folio_number) }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label for="partialQty" class="text-sm text-slate-600">Pzas del folio parcial</label>
                        <input id="partialQty" name="partial_qty" type="number" min="1"
                               value="{{ old('partial_qty', $originalPartial?->qty_for_folio) }}"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                    </div>
                </div>
                @if($action)
                    <p class="mt-3 text-sm text-blue-900">La cantidad parcial original no se copia automáticamente. Si todos los folios llevarán la misma cantidad, escribe esa cantidad en Std pack y deja vacíos los campos de folio parcial.</p>
                @endif
                <div id="newFoliosPreview" class="mt-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600"></div>
            </div>
        </section>

        <section class="rounded-2xl border p-4">
            <label for="reworkReason" class="text-sm font-medium text-slate-700">Motivo del retrabajo</label>
            <textarea id="reworkReason" name="rework_reason" rows="3" maxlength="500" required
                      class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-red-600"
                      placeholder="Describe por qué se modifica la requisición...">{{ old('rework_reason', $action?->reason) }}</textarea>

            @if(filled($masterRequest->notes))
                <details class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <summary class="cursor-pointer text-sm font-medium text-slate-700">Consultar notas de la requisición base #{{ $masterRequest->id }}</summary>
                    <div class="mt-2 whitespace-pre-line break-words text-sm text-slate-600">{{ $masterRequest->notes }}</div>
                </details>
            @endif

            <label for="notes" class="mt-4 block text-sm text-slate-600">Notas de solicitud / entrega del retrabajo (opcional)</label>
            <textarea id="notes" name="notes" rows="2" maxlength="1000"
                      aria-describedby="notesHelp"
                      placeholder="Solicitó el retrabajo: nombre / número de empleado. Se entregó a: nombre / número de empleado. Fecha y hora de entrega."
                      class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea>
            <p id="notesHelp" class="mt-1 text-xs text-slate-500">Registra los datos de esta entrega. Quedarán visibles en el resumen del retrabajo; las notas de la requisición base se conservan por separado.</p>
        </section>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ $kioskMode ? route('kiosk.master_action_requests.create', ['type' => 'rework']) : ($action ? route('master_action_requests.show', $action) : route('master_reprints.search')) }}" class="rounded-xl border px-5 py-3 text-center text-sm hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="rounded-xl bg-red-600 px-5 py-3 font-semibold text-white hover:bg-red-500">
                {{ $kioskMode ? 'Enviar retrabajo a Label Room' : ($action ? 'Confirmar retrabajo y ver resumen' : 'Guardar revisión y ver resumen') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/master-reworks-create.js')
@endpush
