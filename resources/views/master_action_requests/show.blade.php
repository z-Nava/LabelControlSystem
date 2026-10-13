@extends('layouts.app', ['title' => 'Revisión de solicitud Master'])

@section('content')
<div class="space-y-5">
    <div class="rounded-2xl bg-white p-6 shadow">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-red-600">{{ $action->typeLabel() }} Master</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Solicitud #{{ $action->id }}</h1>
                <p class="mt-1 text-slate-600">Requisición origen <a href="{{ route('master_requests.show', $action->master_request_id) }}" class="font-semibold text-red-600 hover:underline">#{{ $action->master_request_id }}</a> · {{ $action->masterRequest?->request_type }}</p>
            </div>
            <a href="{{ route('master_action_requests.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-center text-sm hover:bg-slate-50">Volver a solicitudes</a>
        </div>

        @if(session('success'))
            <p class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>
        @endif
        @if($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                <strong>No se pudo completar la revisión.</strong>
                <ul class="mt-2 list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><div class="text-xs uppercase text-slate-500">Solicitó</div><div class="mt-1 font-semibold">{{ $action->requested_by_name }}</div></div>
            <div><div class="text-xs uppercase text-slate-500">Registrada</div><div class="mt-1 font-semibold">{{ $action->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</div></div>
            <div><div class="text-xs uppercase text-slate-500">Estado</div><div class="mt-1 font-semibold">{{ ['pending' => 'Pendiente', 'approved' => 'Confirmada', 'rejected' => 'Rechazada'][$action->status] ?? $action->status }}</div></div>
            <div><div class="text-xs uppercase text-slate-500">Job</div><div class="mt-1 break-words font-semibold">{{ $action->masterRequest?->job_assembly ?: $action->masterRequest?->job_packaging ?: '—' }}</div></div>
        </div>
    </div>

    <section class="rounded-2xl bg-white p-6 shadow">
        <h2 class="font-semibold text-slate-900">Folios solicitados</h2>
        <p class="mt-1 text-sm text-slate-600">{{ $action->selectedFolios->isEmpty() ? 'Esta solicitud pide únicamente folios nuevos.' : 'Confirma que pertenecen a la requisición origen y que su estado sigue siendo adecuado.' }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @forelse($action->selectedFolios as $folio)
                <span class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold">F{{ $folio->folio_number }} · {{ $folio->status === 'printed' ? 'Impreso' : 'Pendiente' }}</span>
            @empty
                <span class="text-sm text-slate-600">Ninguno; se solicitaron solo folios adicionales.</span>
            @endforelse
        </div>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div><div class="text-xs uppercase text-slate-500">{{ $action->action_type === 'reprint' ? 'Copias por folio' : 'Folios adicionales' }}</div><div class="mt-1 font-semibold">{{ $action->action_type === 'reprint' ? $action->copies : $action->additional_folios_count }}</div></div>
            <div><div class="text-xs uppercase text-slate-500">Motivo</div><div class="mt-1 whitespace-pre-wrap text-slate-800">{{ $action->reason }}</div></div>
        </div>
    </section>

    @if($action->isPending())
        <section class="grid gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
                <h2 class="font-semibold text-emerald-950">Confirmar solicitud</h2>
                @if($action->action_type === 'rework')
                    <p class="mt-2 text-sm text-emerald-900">Revisa y ajusta Jobs, modelo y piezas por folio. La revisión se creará al guardar el formulario; después podrás imprimirla.</p>
                    <a href="{{ route('master_action_requests.rework.create', $action) }}" class="mt-5 inline-flex w-full justify-center rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-600">Preparar retrabajo y cantidades</a>
                @else
                    <p class="mt-2 text-sm text-emerald-900">Se creará un lote de reimpresión con estos folios y copias. La impresión se hará desde Label Room.</p>
                    <form method="POST" action="{{ route('master_action_requests.approve', $action) }}" class="mt-5">
                        @csrf
                        <button class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-600">Confirmar y crear lote</button>
                    </form>
                @endif
            </div>
            <form method="POST" action="{{ route('master_action_requests.reject', $action) }}" class="rounded-2xl border border-red-200 bg-red-50 p-6">
                @csrf
                <h2 class="font-semibold text-red-950">Rechazar solicitud</h2>
                <label for="reviewNotes" class="mt-2 block text-sm text-red-900">Motivo del rechazo *</label>
                <textarea id="reviewNotes" name="review_notes" rows="3" minlength="5" maxlength="500" required class="mt-1 w-full rounded-xl border border-red-300 bg-white px-3 py-2.5 focus:ring-2 focus:ring-red-600">{{ old('review_notes') }}</textarea>
                <button class="mt-3 w-full rounded-xl border border-red-300 bg-white px-4 py-3 font-semibold text-red-800 hover:bg-red-100">Rechazar solicitud</button>
            </form>
        </section>
    @else
        <section class="rounded-2xl bg-white p-6 shadow">
            <h2 class="font-semibold text-slate-900">Resolución de Label Room</h2>
            <p class="mt-2 text-sm text-slate-700">{{ $action->reviewed_by_name }} · {{ $action->reviewed_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</p>
            @if($action->review_notes)<p class="mt-3 whitespace-pre-wrap text-slate-700">{{ $action->review_notes }}</p>@endif
            @if($action->resulting_master_request_id)
                <a href="{{ route('master_reworks.show', $action->resulting_master_request_id) }}" class="mt-4 inline-flex rounded-xl bg-red-600 px-4 py-2.5 font-semibold text-white hover:bg-red-500">Abrir revisión #{{ $action->resulting_master_request_id }}</a>
                <p class="mt-3 text-sm text-slate-700">Cantidades de la revisión: {{ $action->resultingMasterRequest?->folios?->sortBy('folio_number')->map(fn ($folio) => 'F'.$folio->folio_number.' · '.$folio->qty_for_folio.' pzas')->join(', ') ?: 'Consulta la revisión' }}.</p>
            @endif
            @if($action->resulting_print_batch_id)
                <a href="{{ route('master_print_batches.print', $action->resulting_print_batch_id) }}" target="_blank" class="mt-4 inline-flex rounded-xl bg-red-600 px-4 py-2.5 font-semibold text-white hover:bg-red-500">Abrir lote de reimpresión #{{ $action->resulting_print_batch_id }}</a>
            @endif
        </section>
    @endif
</div>
@endsection
