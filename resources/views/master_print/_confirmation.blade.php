@if(($mode ?? null) === 'print' && isset($batch))
    <style>
        @media screen {
            body { padding-bottom: 110px !important; }
            .master-print-controls {
                position: fixed;
                bottom: 16px;
                left: 16px;
                right: 16px;
                z-index: 100;
                max-width: 1050px;
                margin: auto;
            }
        }
        @media print {
            .master-print-controls, .swal2-container { display: none !important; }
            body.swal2-shown > .sheet { display: block !important; }
        }
    </style>
    <div id="master-print-confirmation"
         class="master-print-controls flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-lg"
         data-confirm-url="{{ route('master_print_batches.confirm', $batch) }}"
         data-return-url="{{ route('master_requests.create') }}"
         data-csrf-token="{{ csrf_token() }}"
         data-confirmed="{{ $batch->isConfirmed() ? '1' : '0' }}">
        <div>
            <div class="text-xs text-slate-500">Lote #{{ $batch->id }} · {{ $batch->items->count() }} folio(s)</div>
            <div id="master-print-status" role="status" class="mt-1 text-sm font-semibold {{ $batch->isConfirmed() ? 'text-green-700' : 'text-amber-700' }}">
                {{ $batch->isConfirmed() ? 'Impresión registrada' : 'Pendiente de confirmar impresión' }}
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($batch->isPending())
                <button id="master-print-retry" type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reintentar impresión</button>
                <button id="master-print-confirm" type="button" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">Confirmar impresión</button>
            @endif
            <a href="{{ $mr->isRework() ? route('master_reworks.show', $mr) : route('master_requests.show', $mr) }}"
               class="rounded-xl border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Volver a requisición</a>
        </div>
    </div>
@endif
