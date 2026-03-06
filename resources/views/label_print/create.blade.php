@extends('layouts.app', ['title' => 'Registrar batch de impresión'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Registrar batch de impresión</h1>
            <p class="text-slate-600 mt-1">Requisición #{{ $labelRequest->id }} · NP {{ $labelRequest->label_part_number }}</p>
        </div>
        <a href="{{ route('label_requests.show', $labelRequest) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al detalle</a>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4" method="POST" action="{{ route('label_requests.print.store', $labelRequest) }}">
        @csrf

        <div>
            <label class="text-sm text-slate-600">Tipo de batch</label>
            <select name="batch_type" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                <option value="print" @selected(old('batch_type', 'print') === 'print')>Impresión</option>
                <option value="reprint" @selected(old('batch_type') === 'reprint')>Reimpresión</option>
                <option value="rework" @selected(old('batch_type') === 'rework')>Retrabajo</option>
            </select>
        </div>

        <div>
            <label class="text-sm text-slate-600">Copias</label>
            <input type="number" name="copies" min="1" value="{{ old('copies', 1) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div class="md:col-span-3">
            <label class="text-sm text-slate-600">Razón (obligatoria para reprint/rework)</label>
            <input type="text" name="reason" value="{{ old('reason') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div class="md:col-span-3">
            <button class="rounded-xl bg-red-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-red-500">Registrar batch</button>
        </div>
    </form>
</div>
@endsection
