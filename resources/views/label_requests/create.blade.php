@extends('layouts.app', ['title' => 'Nueva requisición de etiquetas'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Crear requisición de etiquetas</h1>
            <p class="text-slate-600 mt-1">Registra línea, turno, SKU/NP, qty y datos de job/po/destino/modelo.</p>
        </div>
        <a href="{{ route('label_requests.index') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al listado</a>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form id="labelRequestCreate" data-lookup-url="{{ route('oracle.lookup_job') }}" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4" method="POST" action="{{ route('label_requests.store') }}">
        @csrf

        <div>
            <label class="text-sm text-slate-600">Fecha</label>
            <input type="date" name="request_date" value="{{ old('request_date', $defaultDate) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div>
            <label class="text-sm text-slate-600">Semana</label>
            <input type="number" name="week" min="1" max="53" value="{{ old('week', $defaultWeek) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div>
            <label class="text-sm text-slate-600">Líder</label>
            <input type="text" name="leader_name" value="{{ old('leader_name') }}" minlength="3" maxlength="120" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s\-\.']+" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div>
            <label class="text-sm text-slate-600">Línea</label>
            <select name="line_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                <option value="">Selecciona</option>
                @foreach($lines as $line)
                    <option value="{{ $line->id }}" @selected((string) old('line_id') === (string) $line->id)>{{ $line->code }} · {{ $line->line_type }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-sm text-slate-600">Turno</label>
            <select name="shift_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                <option value="">Selecciona</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}" @selected((string) old('shift_id') === (string) $shift->id)>{{ $shift->code }} · {{ $shift->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-sm text-slate-600">SKU / Label PN</label>
            <select id="labelPartNumber" name="label_part_number" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                <option value="">Selecciona SKU / Label PN disponible</option>
                @foreach($labelSkus as $sku)
                    <option value="{{ $sku->label_part_number }}" @selected(old('label_part_number') === $sku->label_part_number)>
                        {{ $sku->sku }} · {{ $sku->label_part_number }} · {{ $sku->description }}
                    </option>
                @endforeach
           </select>
            <p class="text-xs text-slate-500 mt-1">Mostrando solo SKUs con formato activo en seriales.</p>
        </div>

        <div>
            <label class="text-sm text-slate-600">Cantidad</label>
            <input type="number" name="quantity_requested" min="1" value="{{ old('quantity_requested') }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div class="md:col-span-2">
            <label class="text-sm text-slate-600 block mb-2">Tipo de etiqueta</label>
            <label class="inline-flex items-center gap-2 mr-4">
                <input type="checkbox" name="include_serial" value="1" @checked(old('include_serial')) /> Serial
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="include_rating" value="1" @checked(old('include_rating')) /> Rating
            </label>
        </div>

        <div>
            <label class="text-sm text-slate-600">Job</label>
            <input id="jobNumber" type="text" name="job_number" value="{{ old('job_number') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
            <p id="jobHint" class="text-xs text-slate-500 mt-2"></p>
        </div>
        <div>
            <label class="text-sm text-slate-600">PO</label>
            <input id="poNumber" type="text" name="po_number" value="{{ old('po_number') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>
        <div>
            <label class="text-sm text-slate-600">Destino</label>
            <input id="destination" type="text" name="destination" value="{{ old('destination') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>
        <div>
            <label class="text-sm text-slate-600">Modelo</label>
            <input type="text" name="model" value="{{ old('model') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div class="md:col-span-3">
            <label class="text-sm text-slate-600">Notas</label>
            <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea>
        </div>

        <div class="md:col-span-3">
            <button class="rounded-xl bg-red-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-red-500">Crear requisición</button>
        </div>
    </form>
</div>
@vite('resources/js/app.js')
@endsection
