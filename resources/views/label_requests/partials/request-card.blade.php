<article class="min-w-0 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="font-semibold text-slate-900">
            <a href="{{ route('label_requests.show', $labelRequest) }}" class="underline decoration-slate-300 underline-offset-4 hover:text-blue-700" aria-label="Ver detalle de la requisición #{{ $labelRequest->id }}">Requisición #{{ $labelRequest->id }}</a>
        </h3>
        @if($labelRequest->isLpk())
            <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">LPK</span>
        @endif
        @if($status === 'all')
            <span class="rounded-full border px-2 py-0.5 text-xs font-semibold {{ $labelRequest->status_badge_classes }}">{{ $labelRequest->status_label }}</span>
        @endif
    </div>
    <div class="mt-1 text-xs text-slate-500">
        {{ $labelRequest->created_at?->timezone('America/Monterrey')->format('d/m/Y H:i') }} (Torreón)
    </div>

    <div class="mt-4 break-words">
        @if($row['hasGroupedLpkDetails'])
            <p class="font-semibold text-slate-900">{{ $row['lpkProductionJobs']->count() }} Jobs de producción</p>
            <p>{{ $row['lpkProductionJobs']->take(3)->implode(', ') ?: 'Sólo Shipping' }}@if($row['lpkProductionJobs']->count() > 3) · +{{ $row['lpkProductionJobs']->count() - 3 }}@endif</p>
        @else
            <p class="font-semibold text-slate-900">{{ $labelRequest->job_number ? 'Job '.$labelRequest->job_number : 'Sin Job' }}</p>
            <p>{{ $labelRequest->model ?: 'Sin modelo' }}</p>
        @endif
    </div>
    <div class="mt-2 break-words text-xs text-slate-600">
        {{ $labelRequest->line?->code ? 'Línea '.$labelRequest->line->code : 'Sin línea' }} · Turno {{ $labelRequest->shift?->code ?: '—' }}
    </div>

    <dl class="mt-3 grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-3">
        <div>
            <dt class="text-xs text-slate-500">{{ $labelRequest->isLostLabelRework() ? 'Juegos faltantes' : ($row['hasGroupedLpkDetails'] ? 'Reserva Jobs' : 'Cantidad general') }}</dt>
            <dd class="font-semibold text-slate-900">{{ number_format($labelRequest->quantity_requested) }}</dd>
            @if($labelRequest->isLostLabelRework())
                <dd class="text-xs text-amber-800">Reposición · origen #{{ $labelRequest->source_label_request_id }}</dd>
            @endif
            @if($row['hasGroupedLpkDetails'])
                <dd class="text-xs text-slate-600">Shipping · {{ $labelRequest->lpkShippingGroups->count() }} grupo(s)</dd>
            @elseif($labelRequest->include_shipping)
                <dd class="text-xs text-slate-600">Shipping · {{ number_format($labelRequest->shipping_quantity ?? $labelRequest->quantity_requested) }}</dd>
            @endif
        </div>
        <div>
            <dt class="text-xs text-slate-500">Folios</dt>
            <dd class="break-words font-medium">{{ $labelRequest->folio_start !== null ? $labelRequest->folio_start.' – '.$labelRequest->folio_end : ($labelRequest->released_at ? 'Ver folios por Rating' : 'Pendientes') }}</dd>
        </div>
    </dl>

    @if($row['hasGroupedLpkDetails'] || $labelRequest->requestedLabelTypes())
        <details class="mt-3 break-words text-xs">
            <summary class="cursor-pointer font-medium text-slate-700">{{ implode(' + ', $labelRequest->requestedLabelTypes()) ?: 'Etiquetas' }} · NP y modelos</summary>
            <div class="mt-2 space-y-1 text-slate-600">
                @if($row['hasGroupedLpkDetails'])
                    @foreach($labelRequest->lpkLabelGroups as $group)
                        <p><strong>{{ $group->type_label }} {{ $group->part_number }}</strong> · @include('label_requests.partials.lpk-group-models', ['items' => $group->items])</p>
                    @endforeach
                    @foreach($labelRequest->lpkShippingGroups as $group)
                        <p><strong>Shipping {{ $group->part_number }}</strong> · @include('label_requests.partials.lpk-group-models', ['items' => $group->items]) · {{ number_format($group->quantity) }} {{ $group->quantity === 1 ? 'etiqueta' : 'etiquetas' }}</p>
                    @endforeach
                @else
                    @if($labelRequest->include_serial)
                        <p><strong>Serial</strong> · {{ implode(', ', $row['serialPartNumbers']) ?: 'Sin NP capturado' }}</p>
                    @endif
                    @if($labelRequest->include_rating)
                        <p><strong>Rating</strong> · {{ implode(', ', $row['ratingPartNumbers']) ?: 'Sin NP capturado' }}</p>
                    @endif
                    @if($labelRequest->include_inner)
                        <p><strong>Inner</strong> · {{ $row['innerItem'] ?: 'Sin NP capturado' }}</p>
                    @endif
                    @if($labelRequest->include_shipping)
                        <p><strong>Shipping</strong> · {{ implode(', ', $row['shippingItems']) ?: 'Sin NP capturado' }}</p>
                    @endif
                @endif
            </div>
        </details>
    @endif

    <div class="mt-4 border-t border-slate-200 pt-3">
        @include('label_requests.partials.workflow-actions', ['labelRequest' => $labelRequest, 'compact' => true])
    </div>
</article>
