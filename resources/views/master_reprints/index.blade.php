@extends('layouts.app', ['title' => 'Historial de reimpresiones'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Historial de impresiones y retrabajos Master</h1>
            <p class="text-slate-600 mt-1">
                Requisición #{{ $mr->id }} · {{ $mr->oracle_line ?: $mr->line?->code }}@if($mr->shift) · Turno {{ $mr->shift->code }}@endif
            </p>
            @if($mr->isManual())
                <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Master Manual</span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('master_requests.show', $mr->id) }}"
               class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">
                Volver
            </a>
        </div>
    </div>

    @include('master_requests._notes', [
        'notes' => $mr->notes,
        'title' => $mr->isRework() ? 'Notas del retrabajo' : 'Notas de la requisición',
    ])

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
            <tr class="text-left text-slate-500 border-b">
                <th class="py-3 pr-3">Batch</th>
                <th class="py-3 pr-3">Tipo</th>
                <th class="py-3 pr-3">Fecha</th>
                <th class="py-3 pr-3">Registrado por</th>
                <th class="py-3 pr-3">Motivo y notas de la impresión</th>
                <th class="py-3 pr-3">Folios</th>
                <th class="py-3 pr-3 text-right">Acciones</th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @forelse($printBatches as $batch)
                @php
                    $batchRequest = $batch->masterRequest;
                    $batchReason = filled($batch->reason)
                        ? $batch->reason
                        : ($batch->batch_type === 'rework' ? $batchRequest->rework_reason : null);
                    $batchTypeLabel = match ($batch->batch_type) {
                        'print' => 'Impresión',
                        'reprint' => 'Reimpresión',
                        'rework' => 'Retrabajo',
                        default => $batch->batch_type,
                    };
                @endphp
                <tr class="align-top">
                    <td class="py-3 pr-3 font-semibold">
                        #{{ $batch->id }}
                        @if($batchRequest->isRework())
                            <a href="{{ route('master_reworks.show', $batchRequest) }}" class="mt-1 block text-xs text-purple-700 hover:underline">
                                R{{ $batchRequest->revision_number }} · Req. #{{ $batchRequest->id }}
                            </a>
                        @endif
                    </td>
                    <td class="py-3 pr-3">
                        <span class="rounded-full px-2 py-1 text-xs {{ $batch->batch_type === 'reprint' ? 'bg-amber-100 text-amber-800' : ($batch->batch_type === 'rework' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800') }}">{{ $batchTypeLabel }}</span>
                    </td>
                    <td class="py-3 pr-3">{{ $batch->printed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td class="py-3 pr-3">{{ $batch->printed_by_name ?? $batch->printedBy?->name ?? '-' }}</td>
                    <td class="py-3 pr-3">
                        @if(filled($batchReason))
                            <div class="whitespace-pre-line break-words">{{ $batchReason }}</div>
                        @elseif($batch->batch_type === 'print' && filled($batchRequest->notes))
                            <div class="whitespace-pre-line break-words">{{ $batchRequest->notes }}</div>
                            <div class="mt-1 text-xs text-slate-500">Nota registrada al crear la requisición.</div>
                        @else
                            <div>-</div>
                        @endif
                        @if($batch->batch_type === 'rework' && $batchRequest->isRework() && filled($batchRequest->notes))
                            <div class="mt-2 text-xs text-slate-500">Notas de la revisión</div>
                            <div class="whitespace-pre-line break-words">{{ $batchRequest->notes }}</div>
                        @endif
                    </td>
                    <td class="py-3 pr-3">
                        <div class="text-slate-700">
                            {{ $batch->items->count() }} folio(s)
                            · {{ $batch->items->sum('copies') }} copia(s)
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            @foreach($batch->items->sortBy(fn($item) => $item->folio?->folio_number ?? 0) as $item)
                                F{{ str_pad((string) ($item->folio?->folio_number ?? ''), 2, '0', STR_PAD_LEFT) }}
                                (x{{ $item->copies }})@if(!$loop->last), @endif
                            @endforeach
                        </div>
                    </td>
                    <td class="py-3 pl-3 text-right whitespace-nowrap">
                        @if($batchRequest->isCancelled())
                            <span class="inline-flex cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-slate-500">
                                Bloqueada
                            </span>
                        @else
                            <a href="{{ route('master_requests.print.create', $batchRequest->id) }}"
                               class="inline-flex rounded-lg bg-red-600 px-3 py-1.5 font-semibold text-white transition hover:bg-red-500">
                                Nueva impresión
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-slate-500">
                        Aún no hay historial de impresiones para esta requisición.
                    </td>
                    <td class="py-3 pl-3 text-right whitespace-nowrap">
                        @if($mr->isCancelled())
                            <span class="inline-flex cursor-not-allowed rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-slate-500">
                                Bloqueada
                            </span>
                        @else
                            <a href="{{ route('master_requests.print.create', $mr->id) }}"
                               class="inline-flex rounded-lg bg-red-600 px-3 py-1.5 font-semibold text-white transition hover:bg-red-500">
                                Nueva impresión
                            </a>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
