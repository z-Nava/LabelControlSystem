@extends('layouts.app', ['title' => 'Registrar batch Dummy QR'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Registrar batch de impresión Dummy QR</h1>
            <p class="text-slate-600 mt-1">Requisición #{{ $dummyRequest->id }} · Job {{ $dummyRequest->job_number }}</p>
        </div>
        <a href="{{ route('dummy_requests.show', $dummyRequest) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al detalle</a>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 space-y-1">
        <p><span class="font-semibold">Total dummys:</span> {{ number_format($dummyRequest->items_count) }} · <span class="font-semibold">Solicitados:</span> {{ number_format($dummyRequest->quantity_requested) }}</p>
        <p><span class="font-semibold">Rango:</span> {{ str_pad((string) $dummyRequest->range_from, 10, '0', STR_PAD_LEFT) }} - {{ str_pad((string) $dummyRequest->range_to, 10, '0', STR_PAD_LEFT) }}</p>
        <p class="text-xs text-slate-500">Usa <span class="font-semibold">print</span> solo una vez por requisición. Para corridas adicionales, usa <span class="font-semibold">reprint</span>.</p>
    </div>

    @if($hasPrintBatch)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Esta requisición ya tiene un batch <span class="font-semibold">print</span>. Solo se permite <span class="font-semibold">reprint</span>.
        </div>
    @endif

    <form class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4" method="POST" action="{{ route('dummy_requests.print.store', $dummyRequest) }}">
        @csrf

        <div>
            <label class="text-sm text-slate-600">Tipo de batch</label>
            <select name="batch_type" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
                <option value="print" @selected(old('batch_type', $hasPrintBatch ? 'reprint' : 'print') === 'print') @disabled($hasPrintBatch)>Impresión inicial</option>
                <option value="reprint" @selected(old('batch_type', $hasPrintBatch ? 'reprint' : null) === 'reprint')>Reimpresión</option>
            </select>
        </div>

        <div>
            <label class="text-sm text-slate-600">Copias por dummy</label>
            <input type="number" name="copies" min="1" max="10" value="{{ old('copies', 1) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div>
            <label class="text-sm text-slate-600">Motivo (obligatorio en reprint)</label>
            <input type="text" name="reason" value="{{ old('reason') }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" />
        </div>

        <div class="md:col-span-3">
            <button class="rounded-xl bg-red-600 text-white px-5 py-2.5 text-sm font-semibold hover:bg-red-500">Generar batch e ir al centro de impresión</button>
        </div>
    </form>
</div>
@endsection
