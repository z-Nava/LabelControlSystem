@extends('layouts.app', ['title' => 'Nueva requisición Dummy QR'])

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl shadow p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    Flujo Dummy QR
                </div>
                <h1 class="mt-3 text-2xl font-semibold text-slate-900">Crear requisición Dummy QR</h1>
                <p class="mt-2 max-w-3xl text-slate-600">
                    Este flujo genera automáticamente el lote de dummys con consecutivo único por Job,
                    construye el QR estándar <span class="font-mono">^DM^FG^JOB^CONSECUTIVO^</span>
                    y deja historial para impresión/reimpresión.
                </p>
            </div>

            <a href="{{ route('dummy_requests.index') }}" class="shrink-0 rounded-xl border px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Volver al listado
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('dummy_requests.store') }}" data-lookup-url="{{ route('dummy_requests.lookup_job') }}" id="dummyRequestCreate" class="space-y-4">
        @csrf

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <h2 class="text-base font-semibold text-slate-900">1) Datos generales</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="text-sm font-medium text-slate-700">Fecha</label>
                    <input type="date" name="request_date" max="{{ now()->toDateString() }}" value="{{ old('request_date', $defaultDate) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Semana</label>
                    <input type="number" name="week" min="1" max="53" value="{{ old('week', $defaultWeek) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Líder</label>
                    <input type="text" name="leader_name" value="{{ old('leader_name') }}" minlength="3" maxlength="120" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
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
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
            <h2 class="text-base font-semibold text-slate-900">2) Configuración Dummy QR</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="text-sm font-medium text-slate-700">Job producción</label>
                    <input id="jobNumber" type="text" name="job_number" value="{{ old('job_number') }}" maxlength="40" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 uppercase" />
                    <p class="mt-1 text-xs text-slate-500">El FG se toma automáticamente desde Oracle usando este Job.</p>
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
                    <input id="quantityRequested" type="number" name="quantity_requested" min="1" max="100000" value="{{ old('quantity_requested') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                    <p id="quantityHint" class="mt-1 text-xs text-slate-500">La cantidad no puede exceder el Job Qty de Oracle.</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Tipo de requisición</label>
                    <select name="request_type" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        @foreach($requestTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('request_type', 'first_time') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    <label class="text-sm font-medium text-slate-700">Notas (opcional)</label>
                    <textarea name="notes" rows="3" maxlength="1000" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-base font-semibold text-slate-900">Guardar requisición y generar lote Dummy QR</div>
                    <p class="mt-1 text-sm text-slate-500">Se asignará automáticamente el siguiente rango de consecutivos disponible para la Job.</p>
                </div>

                <button class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-500">
                    Crear requisición Dummy QR
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/dummy-requests-create.js')
@endpush
