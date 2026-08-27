@php($compact = $compact ?? false)

<div @class([
    'grid gap-1.5' => $compact,
    'flex flex-wrap items-center gap-2' => ! $compact,
])>
    <a href="{{ route('label_requests.requisition_sheet', $labelRequest) }}"
       target="_blank"
       rel="noopener"
       class="inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-1.5 {{ $compact ? 'w-full text-xs' : 'text-sm' }} font-medium text-slate-700 hover:bg-slate-50">
        Ver / reimprimir hoja
    </a>

    @if($labelRequest->canStartPreparation())
        <form method="POST" action="{{ route('label_requests.start_preparation', $labelRequest) }}" @class(['w-full' => $compact])>
            @csrf
            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5 {{ $compact ? 'w-full text-xs' : 'text-sm' }} font-medium text-violet-700 hover:bg-violet-100"
                    onclick="return confirm('¿Iniciar la preparación de esta requisición?')">
                Iniciar preparación
            </button>
        </form>
    @endif

    @if($labelRequest->canMarkReadyForDelivery())
        <form method="POST" action="{{ route('label_requests.ready_for_delivery', $labelRequest) }}" @class(['w-full' => $compact])>
            @csrf
            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 {{ $compact ? 'w-full text-xs' : 'text-sm' }} font-medium text-amber-700 hover:bg-amber-100"
                    onclick="return confirm('¿Confirmas que todas las etiquetas están listas para entregar?')">
                Marcar lista para entregar
            </button>
        </form>
    @endif

    @if($labelRequest->canConfirmDelivery())
        <form method="POST" action="{{ route('label_requests.deliver', $labelRequest) }}" @class(['w-full' => $compact])>
            @csrf
            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 {{ $compact ? 'w-full text-xs' : 'text-sm' }} font-medium text-emerald-700 hover:bg-emerald-100"
                    onclick="return confirm('¿Confirmas que la requisición fue entregada a producción?')">
                Confirmar entrega
            </button>
        </form>
    @endif

    @if($labelRequest->canCancel())
        <form method="POST" action="{{ route('label_requests.cancel', $labelRequest) }}" @class(['w-full' => $compact])>
            @csrf
            <button class="inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 {{ $compact ? 'w-full text-xs' : 'text-sm' }} font-medium text-red-700 hover:bg-red-100"
                    onclick="return confirm('¿Cancelar esta requisición?')">
                Cancelar
            </button>
        </form>
    @endif
</div>
