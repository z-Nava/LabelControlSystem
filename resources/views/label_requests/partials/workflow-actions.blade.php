@php($compact = $compact ?? false)
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('label_requests.requisition_sheet', $labelRequest) }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">Ver / reimprimir hoja</a>
    @if(!$labelRequest->released_at && in_array($labelRequest->status, ['requested', 'in_progress']))
        <a href="{{ route('label_requests.review', $labelRequest) }}" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800">Revisar y liberar</a>
    @elseif($labelRequest->status === 'in_progress')
        <a href="{{ route('label_requests.show', $labelRequest) }}" class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-800">Registrar trabajo</a>
    @endif
    @if($labelRequest->canConfirmDelivery())
        <form method="POST" action="{{ route('label_requests.deliver', $labelRequest) }}">
            @csrf
            <button class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800" onclick="return confirm('¿Confirmas la entrega a producción?')">Confirmar entrega</button>
        </form>
    @endif
    @if($labelRequest->canCancel())
        <form method="POST" action="{{ route('label_requests.cancel', $labelRequest) }}">
            @csrf
            <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" onclick="return confirm('¿Cancelar la requisición? Los folios reservados se conservarán en el historial.')">Cancelar</button>
        </form>
    @endif
</div>
