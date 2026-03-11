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

    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 space-y-1">
        <p><span class="font-semibold">Requisición:</span> {{ number_format($labelRequest->quantity_requested) }} etiqueta(s) · Semana {{ $labelRequest->week }} · Año {{ $labelRequest->request_date?->format('Y') }}</p>
        <p><span class="font-semibold">Serial permitido:</span> {{ $labelRequest->include_serial ? 'Sí' : 'No' }} · <span class="font-semibold">Rating permitido:</span> {{ $labelRequest->include_rating ? 'Sí' : 'No' }}</p>
        <p class="text-xs text-slate-500">Batch <span class="font-semibold">print</span>: si no hay rango asignado, consume seriales; si ya existe, reutiliza rango (modo reprint). Rating también consume seriales porque comparte secuencia con serial.</p>
    </div>

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
            <p class="mt-1 text-xs text-slate-500">Para <span class="font-medium">print</span> se generan todos los seriales de la requisición con 1 copia por serial.</p>
        </div>

        <div>
            <label class="text-sm text-slate-600">Contenido a imprimir</label>
            <div class="mt-2 space-y-2 rounded-xl border border-slate-300 px-3 py-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="print_serial" value="1" @checked(old('print_serial', $labelRequest->include_serial ? 1 : 0)) @disabled(!$labelRequest->include_serial)>
                    Serial
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="print_rating" value="1" @checked(old('print_rating', $labelRequest->include_rating ? 1 : 0)) @disabled(!$labelRequest->include_rating)>
                    Rating
                </label>
            </div>
        </div>

        <div class="md:col-span-3">
            <label class="text-sm text-slate-600">Razón (obligatoria para reprint/rework)</label>
            <input type="text" name="reason" value="{{ old('reason') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div class="md:col-span-3">
            <button class="rounded-xl bg-red-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-red-500">Ir a imprimir</button>
        </div>
    </form>
</div>
@endsection
