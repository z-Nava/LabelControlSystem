@extends('layouts.app', ['title' => 'Nueva requisición Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Nueva requisición Master</h1>
            <p class="text-slate-600 mt-1">Captura la requisición del papel y autollenamos con Oracle Jobs.</p>
        </div>

        <a href="{{ route('dashboard')}}"
           class="shrink-0 rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">
            Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Resumen rápido --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="rounded-xl border bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Fecha</div>
            <div id="previewDate" class="font-semibold text-slate-900">—</div>
        </div>
        <div class="rounded-xl border bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Línea / Turno</div>
            <div id="previewLineShift" class="font-semibold text-slate-900">—</div>
        </div>
        <div class="rounded-xl border bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Job(s)</div>
            <div id="previewJobs" class="font-semibold text-slate-900">—</div>
        </div>
        <div class="rounded-xl border bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Tipo de Master</div>
            <div id="previewType" class="font-semibold text-slate-900">—</div>
        </div>
    </div>

     <form id="masterRequestCreate"
          data-lookup-url="{{ route('oracle.lookup_job') }}"
          class="mt-6 space-y-4"
          method="POST"
          action="{{ route('master_requests.store') }}">
        @csrf

        {{-- 1) DATOS GENERALES --}}
        <details open class="group rounded-2xl border">
            <summary class="cursor-pointer select-none px-4 py-3 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-slate-900">1) Datos generales</div>
                    <div class="text-xs text-slate-500">Fecha, semana, línea, turno y líder.</div>
                </div>
                <span class="text-slate-400 group-open:rotate-180 transition">⌄</span>
            </summary>

            <div class="border-t p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-600">Fecha</label>
                    <input id="requestDate" name="request_date" type="date"
                           value="{{ old('request_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                </div>

                <div>
                    <label class="text-sm text-slate-600">Semana</label>
                    <input name="week" type="number" min="1" max="53"
                           value="{{ old('week', now()->weekOfYear) }}"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                </div>

                <div>
                    <label class="text-sm text-slate-600">Tipo de línea</label>
                    <select id="lineTypeSelect"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                        <option value="">Selecciona tipo de línea...</option>
                        @foreach($lines->pluck('line_type')->filter()->unique()->sort()->values() as $lineType)
                            <option value="{{ $lineType }}">
                                {{ $lineType }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm text-slate-600">Línea</label>
                    <select id="lineSelect" name="line_id"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                        <option value="">Selecciona linea...</option>
                        @foreach($lines as $line)
                            <option value="{{ $line->id }}" data-line-type="{{ $line->line_type }}" data-line-code="{{ $line->code }}" @selected(old('line_id') == $line->id)>
                                {{ $line->code }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm text-slate-600">Turno</label>
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
                    <label class="text-sm text-slate-600">Líder</label>
                     <input name="leader_name" value="{{ old('leader_name') }}" maxlength="120" minlength="3" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s\-\.']+"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                </div>
            </div>
        </details>

        {{-- 2) TIPO MASTER --}}
        <details open class="group rounded-2xl border">
            <summary class="cursor-pointer select-none px-4 py-3 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-slate-900">2) Tipo de Master</div>
                    <div class="text-xs text-slate-500">Define el template que se imprimirá.</div>
                </div>
                <span class="text-slate-400 group-open:rotate-180 transition">⌄</span>
            </summary>

            <div class="border-t p-4">
                <label class="text-sm text-slate-600">Tipo de Master</label>
                <select id="requestType" name="request_type"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                    <option value="">Selecciona...</option>
                    <option value="assembly" @selected(old('request_type')=='assembly')>HOJA MASTER - ENSAMBLE</option>
                    <option value="batteries_assembly" @selected(old('request_type')=='batteries_assembly')>HOJA MASTER - ENSAMBLE BATERÍAS</option>
                    <option value="assembly_packaging" @selected(old('request_type')=='assembly_packaging')>HOJA MASTER - ENSAMBLE Y EMPAQUE</option>
                    <option value="motors_molding" @selected(old('request_type')=='motors_molding')>HOJA MASTER - MOTORES Y MOLDEO</option>
                </select>
                <p class="text-xs text-slate-500 mt-2">
                    Tip: para “Ensamble y Empaque”, el Job de Empaque es obligatorio y el de Ensamble es opcional.
                </p>
            </div>
        </details>

        {{-- 3) JOBS (ORACLE) --}}
        <details open class="group rounded-2xl border">
            <summary class="cursor-pointer select-none px-4 py-3 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-slate-900">3) Jobs (Oracle)</div>
                    <div class="text-xs text-slate-500">Captura Job(s) y autollenamos información.</div>
                </div>
                <span class="text-slate-400 group-open:rotate-180 transition">⌄</span>
            </summary>

            <div class="border-t p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border bg-slate-50 p-3">
                    <label class="text-sm text-slate-700 font-medium">Job Ensamble</label>
                    <input id="jobAssembly" name="job_assembly" value="{{ old('job_assembly') }}"  maxlength="40" pattern="^[0-9A-Za-z\-]+$"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                           placeholder="Ej: 393383">
                    <p id="jobAssemblyQty" class="text-md text-slate-600 mt-2">Cantidad del job: —</p>
                    <p id="jobAssemblyHint" class="text-xs text-slate-500 mt-2"></p>
                </div>

                <div class="rounded-xl border bg-slate-50 p-3">
                    <label class="text-sm text-slate-700 font-medium">Job Empaque (si aplica)</label>
                    <input id="jobPackaging" name="job_packaging" value="{{ old('job_packaging') }}"  maxlength="40" pattern="^[0-9A-Za-z\-]+$"
                           class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                           placeholder="Opcional">
                    <p id="jobPackagingQty" class="text-md text-slate-600 mt-2">Cantidad del job: —</p>
                    <p id="jobPackagingHint" class="text-xs text-slate-500 mt-2"></p>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-slate-600">Local</label>
                    <input id="localInput" name="local" value="{{ old('local') }}" maxlength="20"
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
                           placeholder="Se autollenará según el tipo de hoja master y la línea">
                </div>

                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-slate-600">Custom PO</label>
                        <input id="poNumber" name="po_number" value="{{ old('po_number') }}" maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se autollenará si Oracle lo trae">
                    </div>

                    <div>
                        <label class="text-sm text-slate-600">Destino (Ship Code)</label>
                        <input id="destination" name="destination" value="{{ old('destination') }}" maxlength="80" pattern="[A-Za-z0-9\-\/_\s]+"
                               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                               placeholder="Se autollenará si Oracle lo trae">
                    </div>
                </div>
            </div>
        </details>

        {{-- 4) FOLIOS Y CANTIDADES --}}
        <details open class="group rounded-2xl border">
            <summary class="cursor-pointer select-none px-4 py-3 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-slate-900">4) Folios y cantidades</div>
                    <div class="text-xs text-slate-500">Rango de folios, std pack y tipo (nuevo/reposición).</div>
                </div>
                <span class="text-slate-400 group-open:rotate-180 transition">⌄</span>
            </summary>

            <div class="border-t p-4 space-y-4">
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
                    </div>
                </div>

                <div class="rounded-xl border bg-slate-50 p-3">
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
        <details class="group rounded-2xl border">
            <summary class="cursor-pointer select-none px-4 py-3 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-slate-900">5) Extras</div>
                    <div class="text-xs text-slate-500">Notas internas / observaciones.</div>
                </div>
                <span class="text-slate-400 group-open:rotate-180 transition">⌄</span>
            </summary>

            <div class="border-t p-4">
                <label class="text-sm text-slate-600">Notas</label>
                <textarea name="notes" rows="3" maxlength="1000"
                          class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">{{ old('notes') }}</textarea>
            </div>
        </details>

        {{-- ACCIONES --}}
        <div class="pt-2">
            <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">
                Guardar requisición Master
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/master-requests-create.js')
@endpush
