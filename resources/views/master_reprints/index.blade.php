@extends('layouts.app', ['title' => 'Historial de reimpresiones'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Historial de impresión Master</h1>
            <p class="text-slate-600 mt-1">
                Requisición #{{ $mr->id }} · {{ $mr->line?->code }} · Turno {{ $mr->shift?->code }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('master_requests.print.create', $mr->id) }}"
               class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500 transition">
                Nueva impresión
            </a>

            <a href="{{ route('master_requests.show', $mr->id) }}"
               class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">
                Volver
            </a>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
            <tr class="text-left text-slate-500 border-b">
                <th class="py-3 pr-3">Batch</th>
                <th class="py-3 pr-3">Tipo</th>
                <th class="py-3 pr-3">Fecha</th>
                <th class="py-3 pr-3">Usuario</th>
                <th class="py-3 pr-3">Motivo</th>
                <th class="py-3 pr-3">Folios</th>
                <th class="py-3 pr-3 text-right">Acciones</th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @forelse($mr->printBatches as $batch)
                <tr class="align-top">
                    <td class="py-3 pr-3 font-semibold">#{{ $batch->id }}</td>
                    <td class="py-3 pr-3">
                        <span class="rounded-full px-2 py-1 text-xs {{ $batch->batch_type === 'reprint' ? 'bg-amber-100 text-amber-800' : ($batch->batch_type === 'rework' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800') }}">{{ $batch->batch_type }}</span>
                    </td>
                    <td class="py-3 pr-3">{{ $batch->printed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td class="py-3 pr-3">{{ $batch->printed_by_name ?? $batch->printedBy?->name ?? '-' }}</td>
                    <td class="py-3 pr-3">{{ $batch->reason ?: '-' }}</td>
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
                        <a href="{{ route('master_print_batches.print', $batch) }}" target="_blank"
                           class="rounded-lg border px-3 py-1.5 hover:bg-slate-50">
                            Imprimir
                        </a>
                        <a href="{{ route('master_print_batches.pdf', $batch) }}" target="_blank"
                           class="rounded-lg border px-3 py-1.5 hover:bg-slate-50 ml-1">
                            PDF
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-6 text-center text-slate-500">
                        Aún no hay historial de impresiones para esta requisición.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
