@extends('layouts.app', ['title' => 'Nueva requisición Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Nueva requisición Master</h1>
            <p class="text-slate-600 mt-1">Captura la requisición del papel y autollenamos con Oracle Jobs.</p>
        </div>

        <a href="{{ route('dashboard')}}"
           class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">
            Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4" method="POST" action="{{ route('master_requests.store') }}">
        @csrf

        <div>
            <label class="text-sm text-slate-600">Fecha</label>
            <input name="request_date" type="date" value="{{ old('request_date', now()->toDateString()) }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
        </div>

        <div>
            <label class="text-sm text-slate-600">Semana</label>
            <input name="week" type="number" min="1" max="53" value="{{ old('week', now()->weekOfYear) }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
        </div>

        <div>
            <label class="text-sm text-slate-600">Línea</label>
            <select name="line_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                <option value="">Selecciona...</option>
                @foreach($lines as $line)
                    <option value="{{ $line->id }}" @selected(old('line_id') == $line->id)>{{ $line->code }} - {{ $line->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-sm text-slate-600">Turno</label>
            <select name="shift_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                <option value="">Selecciona...</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}" @selected(old('shift_id') == $shift->id)>{{ $shift->code }} - {{ $shift->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-sm text-slate-600">Líder</label>
            <input name="leader_name" value="{{ old('leader_name') }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
        </div>

        <div>
            <label class="text-sm text-slate-600">Tipo de Master</label>
            <select name="request_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                <option value="">Selecciona...</option>
                <option value="assembly" @selected(old('request_type')=='assembly')>HOJA MASTER - ENSAMBLE</option>
                <option value="batteries_assembly" @selected(old('request_type')=='batteries_assembly')>HOJA MASTER - ENSAMBLE BATERÍAS</option>
                <option value="assembly_packaging" @selected(old('request_type')=='assembly_packaging')>HOJA MASTER - ENSAMBLE Y EMPAQUE</option>
                <option value="motors_molding" @selected(old('request_type')=='motors_molding')>HOJA MASTER - MOTORES Y MOLDEO</option>
            </select>
        </div>

        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-slate-600">Job Ensamble</label>
                <input id="jobAssembly" name="job_assembly" value="{{ old('job_assembly') }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                <p id="jobAssemblyHint" class="text-xs text-slate-500 mt-1"></p>
            </div>

            <div>
                <label class="text-sm text-slate-600">Job Empaque (si aplica)</label>
                <input id="jobPackaging" name="job_packaging" value="{{ old('job_packaging') }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
                <p id="jobPackagingHint" class="text-xs text-slate-500 mt-1"></p>
            </div>
        </div>

        <div>
            <label class="text-sm text-slate-600">Custom PO</label>
            <input id="poNumber" name="po_number" value="{{ old('po_number') }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
        </div>

        <div>
            <label class="text-sm text-slate-600">Destino (Ship Code)</label>
            <input id="destination" name="destination" value="{{ old('destination') }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
        </div>

        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-sm text-slate-600">Folios del</label>
                <input name="folios_from" type="number" min="1" value="{{ old('folios_from') }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
            </div>

            <div>
                <label class="text-sm text-slate-600">al</label>
                <input name="folios_to" type="number" min="1" value="{{ old('folios_to') }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
            </div>

            <div>
                <label class="text-sm text-slate-600">Std pack (pzas/pallet)</label>
                <input name="std_pack_qty" type="number" min="1" value="{{ old('std_pack_qty') }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>

            <div>
                <label class="text-sm text-slate-600">Tipo</label>
                <select name="kind" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
                    <option value="new" @selected(old('kind','new')=='new')>Nuevo</option>
                    <option value="reposition" @selected(old('kind')=='reposition')>Reposición</option>
                </select>
            </div>
        </div>

        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-slate-600">Folio parcial (opcional)</label>
                <input name="partial_folio" type="number" min="1" value="{{ old('partial_folio') }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>

            <div>
                <label class="text-sm text-slate-600">Pzas pallet parcial (opcional)</label>
                <input name="partial_qty" type="number" min="1" value="{{ old('partial_qty') }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            </div>
        </div>

        <div class="md:col-span-2">
            <label class="text-sm text-slate-600">Notas</label>
            <textarea name="notes" rows="3"
                      class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">{{ old('notes') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">
                Guardar requisición Master
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const lookupUrl = @json(route('oracle.lookup_job'));

    const jobAssembly = document.getElementById('jobAssembly');
    const jobPackaging = document.getElementById('jobPackaging');

    const poNumber = document.getElementById('poNumber');
    const destination = document.getElementById('destination');

    const hintA = document.getElementById('jobAssemblyHint');
    const hintP = document.getElementById('jobPackagingHint');

    let timerA = null;
    let timerP = null;

    async function lookup(jobNumber) {
        const url = new URL(lookupUrl, window.location.origin);
        url.searchParams.set('job_number', jobNumber);

        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
        return await res.json();
    }

    async function handleAssembly() {
        const v = (jobAssembly.value || '').trim();
        if (!v) { hintA.textContent = ''; return; }

        const data = await lookup(v);

        if (!data.found) {
            hintA.textContent = 'No encontrado en Oracle Jobs.';
            return;
        }

        hintA.textContent = `NP: ${data.assembly || '-'} | ${data.part_description || ''}`;

        // Autollenar destino y PO (solo si están vacíos para no pisar manual)
        if (!destination.value) destination.value = data.ship_code || '';
        if (!poNumber.value) poNumber.value = data.ttl_cust_po || '';
    }

    async function handlePackaging() {
        const v = (jobPackaging.value || '').trim();
        if (!v) { hintP.textContent = ''; return; }

        const data = await lookup(v);

        if (!data.found) {
            hintP.textContent = 'No encontrado en Oracle Jobs.';
            return;
        }

        hintP.textContent = `NP: ${data.assembly || '-'} | ${data.part_description || ''}`;

        if (!destination.value) destination.value = data.ship_code || '';
        if (!poNumber.value) poNumber.value = data.ttl_cust_po || '';
    }

    jobAssembly.addEventListener('input', () => {
        clearTimeout(timerA);
        timerA = setTimeout(handleAssembly, 350);
    });

    jobPackaging.addEventListener('input', () => {
        clearTimeout(timerP);
        timerP = setTimeout(handlePackaging, 350);
    });
})();
</script>
@endsection
