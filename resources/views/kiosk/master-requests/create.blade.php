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

    {{-- Resumen rápido --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="font-semibold text-slate-900">Resumen de tu solicitud</div>
        <p class="mt-1 text-sm text-slate-500">Se actualizará mientras completas el formulario.</p>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Fecha</div>
            <div id="previewDate" class="font-semibold text-slate-900">—</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Línea / Turno</div>
            <div id="previewLineShift" class="font-semibold text-slate-900">—</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Job(s)</div>
            <div id="previewJobs" class="font-semibold text-slate-900">—</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Tipo de Master</div>
            <div id="previewType" class="font-semibold text-slate-900">—</div>
        </div>
        </div>
    </section>

    <form id="masterRequestCreate"
          data-lookup-url="{{ route('kiosk.master_requests.lookup_job') }}"
          class="space-y-4"
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

            <div class="grid grid-cols-1 gap-4 border-t border-slate-200 p-5 md:grid-cols-2">
                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900 md:col-span-2">
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
                    <label class="text-sm font-medium text-slate-700">Línea</label>
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
                    <span class="font-semibold">Qué debes hacer:</span> elige una opción según el área y el proceso que aparecerán en la hoja Master.
                </div>

                <label class="text-sm font-medium text-slate-700">Tipo de Master</label>
                <select id="requestType" name="request_type"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                    <option value="">Selecciona...</option>
                    <option value="assembly" @selected(old('request_type')=='assembly')>HOJA MASTER - ENSAMBLE</option>
                    <option value="batteries_assembly" @selected(old('request_type')=='batteries_assembly')>HOJA MASTER - ENSAMBLE BATERÍAS</option>
                    <option value="assembly_packaging" @selected(old('request_type')=='assembly_packaging')>HOJA MASTER - ENSAMBLE Y EMPAQUE</option>
                    <option value="motors_molding" @selected(old('request_type')=='motors_molding')>HOJA MASTER - MOTORES Y MOLDEO</option>
                </select>
                <p class="text-xs text-slate-500 mt-2">
                    Para “Ensamble y Empaque”, debes capturar el Job de Empaque. El Job de Ensamble es opcional.
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

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-slate-700">Local</label>
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
                <p class="text-xs text-slate-500 md:col-span-2">Revisa los datos autollenados. Si Oracle no proporciona alguno, captura únicamente la información que conozcas.</p>
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
                    <p class="mt-1 text-sm text-slate-600">Confirma el resumen. Al enviar, Label Room recibirá la solicitud; no se imprimirá automáticamente.</p>
                </div>

                <button class="shrink-0 rounded-xl bg-red-600 px-5 py-3 font-semibold text-white transition hover:bg-red-500">
                    Revisar y enviar requisición
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/master-requests-create.js')
@endpush
