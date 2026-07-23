@extends('layouts.kiosk', ['title' => 'Nueva requisición Dummy QR'])

@section('content')
<div class="space-y-6">
    @include('kiosk.partials.request-guide', [
        'title' => 'Crear requisición Dummy QR',
        'description' => 'Solicita los Dummy QR que necesita producción. El sistema consultará el Job en Oracle y Label Room recibirá la requisición para atenderla.',
        'steps' => [
            ['title' => 'Identifica la operación', 'description' => 'Selecciona línea, turno y escribe el nombre del líder.'],
            ['title' => 'Consulta el Job', 'description' => 'Captura el Job y espera a que Oracle confirme la información.'],
            ['title' => 'Indica la cantidad', 'description' => 'Define cuántos Dummy QR necesitas y el tipo de solicitud.'],
            ['title' => 'Revisa y envía', 'description' => 'Confirma los datos y envía la requisición a Label Room.'],
        ],
        'preparationItems' => [
            'Línea, turno y nombre del líder.',
            'Número de Job de producción.',
            'Cantidad de Dummy QR necesaria.',
            'Confirmación de primera vez o reimpresión.',
        ],
    ])

    @include('kiosk.partials.form-errors')

    <form method="POST" action="{{ route('kiosk.dummy_requests.store') }}" data-lookup-url="{{ route('kiosk.dummy_requests.lookup_job') }}" id="dummyRequestCreate" class="space-y-4">
        @csrf

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="text-base font-semibold text-slate-900">1) Identifica la operación</h2>
                <p class="mt-1 text-sm text-slate-500">Indica dónde se usarán los Dummy QR y quién es el líder responsable.</p>
            </div>

            <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                <span class="font-semibold">Qué debes hacer:</span> selecciona el tipo de línea, después la línea y el turno. Escribe el nombre del líder de esa operación.
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="text-sm font-medium text-slate-700">Fecha</label>
                    <input type="date" name="request_date" max="{{ now()->toDateString() }}" value="{{ old('request_date', $defaultDate) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                    <p class="mt-1 text-xs text-slate-500">Inicia con la fecha actual. Cámbiala solo si la solicitud corresponde a otra fecha.</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Semana</label>
                    <input type="number" name="week" min="1" max="53" value="{{ old('week', $defaultWeek) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                    <p class="mt-1 text-xs text-slate-500">Inicia con la semana actual.</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Líder</label>
                    <input type="text" name="leader_name" value="{{ old('leader_name') }}" minlength="3" maxlength="120" placeholder="Ej: Juan Pérez" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                    <p class="mt-1 text-xs text-slate-500">Escribe el nombre del líder, no tu número de empleado.</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Tipo de línea</label>
                    @php
                        $lineTypes = $lines
                            ->pluck('line_type')
                            ->filter()
                            ->unique()
                            ->sort()
                            ->values();
                        $selectedLineType = old('line_type');
                    @endphp
                    <select id="lineTypeSelect" name="line_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        <option value="">Todas las líneas</option>
                        @foreach($lineTypes as $lineType)
                            <option value="{{ $lineType }}" @selected((string) $selectedLineType === (string) $lineType)>{{ $lineType }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Línea</label>
                    <select id="lineIdSelect" name="line_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        <option value="">Selecciona una línea</option>
                        @foreach($lines as $line)
                            <option
                                value="{{ $line->id }}"
                                data-line-type="{{ $line->line_type }}"
                                @selected((string) old('line_id') === (string) $line->id)
                            >
                                {{ $line->code }} · {{ $line->line_type }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Turno</label>
                    <select name="shift_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        <option value="">Selecciona un turno</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}" @selected((string) old('shift_id') === (string) $shift->id)>{{ $shift->code }} · {{ $shift->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="text-base font-semibold text-slate-900">2) Consulta el Job y define la cantidad</h2>
                <p class="mt-1 text-sm text-slate-500">Oracle validará el Job y mostrará los datos encontrados.</p>
            </div>

            <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                <span class="font-semibold">Qué debes hacer:</span> escribe el Job completo y sal del campo. Espera el mensaje de Oracle antes de capturar la cantidad.
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="text-sm font-medium text-slate-700">Job producción</label>
                    <input id="jobNumber" type="text" name="job_number" value="{{ old('job_number') }}" maxlength="40" placeholder="Ej: 393383" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-red-600" />
                    <p class="mt-1 text-xs text-slate-500">El FG se obtiene automáticamente desde Oracle usando este Job.</p>
                    <p id="jobInfoHint" class="mt-1 text-xs text-emerald-700"></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Assembly (informativo)</label>
                    <input id="jobAssembly" type="text" readonly tabindex="-1" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-slate-700" />
                    <p class="mt-1 text-xs text-slate-500">Se muestra automáticamente al validar el Job de producción.</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Línea Oracle (informativo)</label>
                    <input id="jobLine" type="text" readonly tabindex="-1" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-slate-700" />
                    <p class="mt-1 text-xs text-slate-500">Se muestra automáticamente desde Oracle al validar el Job.</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Cantidad solicitada</label>
                    <input id="quantityRequested" type="number" name="quantity_requested" min="1" max="100000" value="{{ old('quantity_requested') }}" placeholder="Ej: 50" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600" />
                    <p id="quantityHint" class="mt-1 text-xs text-slate-500">La cantidad no puede exceder el Job Qty de Oracle.</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Tipo de requisición</label>
                    <select name="request_type" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        @foreach($requestTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('request_type', 'first_time') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Selecciona reimpresión solo si los Dummy QR ya se habían solicitado.</p>
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    <label class="text-sm font-medium text-slate-700">Notas (opcional)</label>
                    <textarea name="notes" rows="3" maxlength="1000" placeholder="Ej: motivo de reimpresión o información útil para Label Room" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-base font-semibold text-slate-900">Última revisión</div>
                    <p class="mt-1 text-sm text-slate-600">Confirma el Job y la cantidad. Al enviar, Label Room recibirá la solicitud; no se imprimirá automáticamente.</p>
                </div>

                <button class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-500">
                    Revisar y enviar requisición
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/dummy-requests-create.js')
@endpush
