@extends('layouts.app', ['title' => 'Centro de impresión Dummy QR'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Centro de impresión Dummy QR</h1>
            <p class="text-slate-600 mt-1">Requisición #{{ $dummyRequest->id }} · Batch #{{ $batch->id }} · {{ strtoupper($batch->batch_type) }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dummy_requests.show', $dummyRequest) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al detalle</a>
            <button type="button" onclick="window.print()" class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500">Imprimir</button>
        </div>
    </div>

    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
        <div><span class="font-semibold">Job:</span> {{ $dummyRequest->job_number }} | <span class="font-semibold">FG:</span> {{ $dummyRequest->fg_code }}</div>
        <div><span class="font-semibold">Cantidad total a imprimir:</span> {{ number_format($batch->quantity) }}</div>
        <div><span class="font-semibold">Línea/Turno:</span> {{ $dummyRequest->line?->code }} · {{ $dummyRequest->shift?->code }}</div>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
            <tr class="text-left text-slate-500 border-b border-slate-200">
                <th class="py-3 px-4">Consecutivo</th>
                <th class="py-3 px-4">Tipo</th>
                <th class="py-3 px-4">Copias</th>
                <th class="py-3 px-4">QR payload</th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @foreach($batch->items as $item)
                <tr class="hover:bg-slate-50">
                    <td class="py-3 px-4 font-mono">{{ $item->requestItem?->consecutive_10d }}</td>
                    <td class="py-3 px-4">{{ strtoupper($item->requestItem?->dummy_type ?? '-') }}</td>
                    <td class="py-3 px-4">{{ number_format((int) $item->copies) }}</td>
                    <td class="py-3 px-4 font-mono text-xs">{{ $item->requestItem?->qr_payload }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
