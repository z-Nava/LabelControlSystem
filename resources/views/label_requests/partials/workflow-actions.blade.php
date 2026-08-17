@php($compact = $compact ?? false)

<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('label_requests.requisition_sheet', $labelRequest) }}"
       target="_blank"
       rel="noopener"
       class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 {{ $compact ? 'text-xs' : 'text-sm' }} font-medium text-slate-700 hover:bg-slate-50">
        Imprimir hoja
    </a>

    @if($labelRequest->canMarkRequisitionPrinted())
        <form method="POST" action="{{ route('label_requests.mark_printed', $labelRequest) }}">
            @csrf
            <button class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5 {{ $compact ? 'text-xs' : 'text-sm' }} font-medium text-violet-700 hover:bg-violet-100"
                    onclick="return confirm('¿Confirmas que la hoja de requisición fue impresa?')">
                Marcar impresa
            </button>
        </form>
    @endif

    @if($labelRequest->canMarkAttended())
        <form method="POST" action="{{ route('label_requests.attend', $labelRequest) }}">
            @csrf
            <button class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 {{ $compact ? 'text-xs' : 'text-sm' }} font-medium text-amber-700 hover:bg-amber-100"
                    onclick="return confirm('¿Confirmas que la requisición fue atendida?')">
                Marcar atendida
            </button>
        </form>
    @endif

    @if($labelRequest->canMarkDelivered())
        <form method="POST" action="{{ route('label_requests.complete', $labelRequest) }}">
            @csrf
            <button class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 {{ $compact ? 'text-xs' : 'text-sm' }} font-medium text-emerald-700 hover:bg-emerald-100"
                    onclick="return confirm('¿Confirmas que la requisición fue entregada a producción?')">
                Marcar entregada
            </button>
        </form>
    @endif

    @if($labelRequest->canCancel())
        <form method="POST" action="{{ route('label_requests.cancel', $labelRequest) }}">
            @csrf
            <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 {{ $compact ? 'text-xs' : 'text-sm' }} font-medium text-red-700 hover:bg-red-100"
                    onclick="return confirm('¿Cancelar esta requisición?')">
                Cancelar
            </button>
        </form>
    @endif
</div>
