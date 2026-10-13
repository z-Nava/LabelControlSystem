@extends('layouts.kiosk', ['title' => 'Mis solicitudes Master'])

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <section class="rounded-2xl bg-slate-900 p-6 text-white shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-red-300">Kiosko de Producción</p>
                <h1 class="mt-2 text-2xl font-semibold">Mis solicitudes Master</h1>
                <p class="mt-2 text-sm text-slate-300">Consulta el estado de los retrabajos y las reimpresiones que enviaste a Label Room.</p>
            </div>
            <a href="{{ route('kiosk.master_requests.create') }}" class="rounded-xl border border-white/30 px-4 py-2 text-center text-sm font-semibold hover:bg-white/10">Volver a Master</a>
        </div>
    </section>

    @if(session('success'))
        <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 font-semibold text-emerald-800">{{ session('success') }}</p>
    @endif

    <div class="space-y-4">
        @forelse($actions as $action)
            @php
                $status = match($action->status) {
                    'approved' => 'Confirmada',
                    'rejected' => 'Rechazada',
                    default => 'Pendiente',
                };
                $statusClasses = match($action->status) {
                    'approved' => 'bg-emerald-100 text-emerald-800',
                    'rejected' => 'bg-red-100 text-red-800',
                    default => 'bg-amber-100 text-amber-800',
                };
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $action->typeLabel() }} Master #{{ $action->id }}</h2>
                        <p class="mt-1 break-words text-sm text-slate-600">Requisición origen #{{ $action->master_request_id }} · {{ $action->masterRequest?->job_assembly ?: $action->masterRequest?->job_packaging ?: 'Job no disponible' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $statusClasses }}">{{ $status }}</span>
                </div>
                <div class="mt-4 grid gap-3 text-sm text-slate-700 sm:grid-cols-3">
                    <div><span class="block text-xs uppercase text-slate-500">Solicitada</span>{{ $action->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</div>
                    <div><span class="block text-xs uppercase text-slate-500">Folios originales</span>{{ $action->selectedFolios->take(8)->map(fn ($folio) => 'F'.$folio->folio_number)->implode(', ') ?: 'Ninguno' }}@if($action->selectedFolios->count() > 8) y {{ $action->selectedFolios->count() - 8 }} más @endif</div>
                    <div><span class="block text-xs uppercase text-slate-500">{{ $action->action_type === 'reprint' ? 'Copias por folio' : 'Folios adicionales' }}</span>{{ $action->action_type === 'reprint' ? $action->copies : $action->additional_folios_count }}</div>
                </div>
                @if($action->status === 'rejected' && $action->review_notes)
                    <p class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-900"><strong>Motivo del rechazo:</strong> {{ $action->review_notes }}</p>
                @elseif($action->status === 'approved')
                    <p class="mt-4 text-sm text-emerald-800">Label Room confirmó la solicitud{{ $action->reviewed_at ? ' el '.$action->reviewed_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : '' }}.</p>
                @else
                    <p class="mt-4 text-sm text-amber-800">Esperando revisión de Label Room.</p>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-600">Todavía no has enviado solicitudes de retrabajo o reimpresión Master.</div>
        @endforelse
    </div>

    {{ $actions->links() }}
</div>
@endsection
