@extends('layouts.app', ['title' => 'Resumen de retrabajo Master'])

@section('content')
@php
    $changes = $revision->rework_changes ?? [];
    $original = $changes['original'] ?? [];
    $resolved = $changes['resolved'] ?? [];
    $final = $changes['final'] ?? [];
    $folioChanges = $changes['folios'] ?? [];
    $batch = $revision->printBatches->first();
    $rows = [
        'Job Ensamble' => 'job_assembly',
        'Job Empaque' => 'job_packaging',
        'Línea' => 'line',
        'Local' => 'local',
        'Subinventory' => 'subinventory',
        'Modelo' => 'model',
        'Custom PO' => 'po_number',
        'Destino' => 'destination',
        'Std pack' => 'std_pack_qty',
        'Folio parcial' => 'partial_folio',
        'Cantidad parcial' => 'partial_qty',
    ];
@endphp

<div class="rounded-2xl bg-white p-6 shadow">
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-purple-700">Retrabajo guardado</div>
            <h1 class="text-2xl font-semibold text-slate-900">Revisión R{{ $revision->revision_number }} de requisición #{{ $revision->parent_master_request_id }}</h1>
            <p class="mt-1 text-slate-600">Revisión #{{ $revision->id }} · {{ $revision->line?->code ?: 'Sin línea' }} · {{ $revision->folios->count() }} folio(s)</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('master_requests.show', $revision->parent_master_request_id) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Ver original</a>
            <a href="{{ route('master_reprints.search') }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver a búsqueda</a>
        </div>
    </div>

    <section class="mt-6 rounded-2xl border border-purple-200 bg-purple-50 p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div><div class="text-xs text-purple-700">Motivo</div><div class="font-semibold text-slate-900">{{ $revision->rework_reason }}</div></div>
            <div><div class="text-xs text-purple-700">Guardado por</div><div class="font-semibold text-slate-900">{{ $revision->reworked_by_name ?: $revision->reworkedBy?->name ?: '—' }}</div></div>
            <div><div class="text-xs text-purple-700">Fecha</div><div class="font-semibold text-slate-900">{{ $revision->reworked_at?->format('Y-m-d H:i') ?: '—' }}</div></div>
        </div>
    </section>

    <section class="mt-6">
        <h2 class="font-semibold text-slate-900">Comparativo de información</h2>
        <p class="mt-1 text-sm text-slate-500">El valor final será el utilizado al generar los nuevos snapshots.</p>
        <div class="mt-3 overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr><th class="px-4 py-3">Campo</th><th class="px-4 py-3">Original</th><th class="px-4 py-3">Resuelto</th><th class="px-4 py-3">Final</th></tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($rows as $label => $key)
                        @php
                            $originalValue = $original[$key] ?? null;
                            $resolvedValue = $resolved[$key] ?? null;
                            $finalValue = $final[$key] ?? null;
                            $changed = (string) $originalValue !== (string) $finalValue;
                        @endphp
                        <tr class="{{ $changed ? 'bg-amber-50/60' : '' }}">
                            <td class="px-4 py-3 font-medium">{{ $label }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ filled($originalValue) ? $originalValue : '—' }}</td>
                            <td class="px-4 py-3 text-blue-700">{{ filled($resolvedValue) ? $resolvedValue : '—' }}</td>
                            <td class="px-4 py-3 font-semibold {{ $changed ? 'text-amber-800' : 'text-slate-900' }}">{{ filled($finalValue) ? $finalValue : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-xl border p-4"><div class="text-xs font-semibold uppercase text-slate-500">Conservados</div><div class="mt-2 text-sm">{{ collect($folioChanges['selected'] ?? [])->map(fn ($f) => 'F'.str_pad((string) $f, 2, '0', STR_PAD_LEFT))->join(', ') ?: 'Ninguno' }}</div></div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-4"><div class="text-xs font-semibold uppercase text-green-700">Agregados</div><div class="mt-2 text-sm text-green-900">{{ collect($folioChanges['added'] ?? [])->map(fn ($f) => 'F'.str_pad((string) $f, 2, '0', STR_PAD_LEFT))->join(', ') ?: 'Ninguno' }}</div></div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><div class="text-xs font-semibold uppercase text-amber-700">No incluidos</div><div class="mt-2 text-sm text-amber-900">{{ collect($folioChanges['removed'] ?? [])->map(fn ($f) => 'F'.str_pad((string) $f, 2, '0', STR_PAD_LEFT))->join(', ') ?: 'Ninguno' }}</div></div>
    </section>

    <section class="mt-6">
        <h2 class="font-semibold text-slate-900">Folios finales de la revisión</h2>
        <div class="mt-3 overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">Folio</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Cantidad</th><th class="px-4 py-3">Estado</th></tr></thead>
                <tbody class="divide-y">
                    @foreach($revision->folios as $folio)
                        <tr><td class="px-4 py-3 font-semibold">F{{ str_pad((string) $folio->folio_number, 2, '0', STR_PAD_LEFT) }}</td><td class="px-4 py-3">{{ $folio->is_partial ? 'Parcial' : 'Normal' }}</td><td class="px-4 py-3">{{ $folio->qty_for_folio ?: '—' }}</td><td class="px-4 py-3">{{ $folio->status }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4">
        <h2 class="font-semibold text-slate-900">Siguiente paso: impresión</h2>
        <p class="mt-1 text-sm text-slate-600">La impresión creará un batch de retrabajo y guardará un snapshot nuevo para cada folio.</p>
        @if($batch)
            <a href="{{ route('master_print_batches.print', $batch) }}" target="_blank"
               class="mt-4 inline-flex rounded-xl bg-red-600 px-5 py-2.5 font-semibold text-white hover:bg-red-500">Abrir impresión generada</a>
        @else
            <form method="POST" action="{{ route('master_reworks.print', $revision) }}" class="mt-4">
                @csrf
                <button class="rounded-xl bg-red-600 px-5 py-2.5 font-semibold text-white hover:bg-red-500">Generar snapshots e imprimir</button>
            </form>
        @endif
    </div>
</div>
@endsection
