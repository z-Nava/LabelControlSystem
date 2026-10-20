@extends('layouts.kiosk', ['title' => 'Mis comprobantes de Kiosk'])

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <section class="rounded-3xl bg-slate-900 px-6 py-6 text-white shadow-lg lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-300">Kiosko de Producción</p>
                <h1 class="mt-2 text-3xl font-semibold">Mis comprobantes</h1>
                <p class="mt-2 text-sm text-slate-300">Elige la solicitud exacta para imprimir o reimprimir su comprobante físico.</p>
            </div>
            <a href="{{ route('kiosk.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/30 px-4 py-2 text-sm font-semibold hover:bg-white/10">Volver al inicio</a>
        </div>
    </section>

    @include('kiosk.partials.requisition-print', ['requisitionPrintConfig' => $requisitionPrintConfig])

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Comprobantes recientes</h2>
        <p class="mt-1 text-sm text-slate-600">Ningún comprobante se envía a la impresora hasta que lo selecciones y pulses imprimir.</p>
        <p class="mt-1 text-xs text-slate-500">Fechas y horas en horario de Torreón.</p>

        @if($receiptHistory->isEmpty())
            <p class="mt-5 rounded-2xl bg-slate-50 p-5 text-slate-600">Aún no tienes comprobantes registrados.</p>
        @else
            <div class="mt-4 divide-y divide-slate-100">
                @foreach($receiptHistory as $item)
                    <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $item['name'] }} #{{ $item['requestId'] }}</p>
                            <p class="text-sm text-slate-600">{{ $item['createdAt'] }} · {{ match($item['status']) { 'printed' => 'Impresión confirmada', 'failed' => 'Impresión fallida', 'sending' => 'En proceso', default => 'Pendiente de impresión' } }}</p>
                        </div>
                        <a href="{{ route('kiosk.receipts.index', ['receipt_job' => $item['id']]) }}"
                           class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:border-amber-600 hover:text-amber-700">
                            {{ $item['status'] === 'printed' ? 'Reimprimir comprobante' : 'Imprimir comprobante' }}
                        </a>
                    </div>
                @endforeach
            </div>
            @if($receiptPages->hasPages())
                <div class="mt-5">{{ $receiptPages->links() }}</div>
            @endif
        @endif
    </section>
</div>
@endsection

@push('scripts')
    @vite('resources/js/pages/kiosk-dashboard.js')
@endpush
