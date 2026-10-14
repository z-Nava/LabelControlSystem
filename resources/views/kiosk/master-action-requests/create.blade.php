@extends('layouts.kiosk', ['title' => 'Solicitar retrabajo o reimpresión Master'])

@section('content')
<div class="space-y-6">
    @include('kiosk.partials.request-guide', [
        'title' => $type === 'rework' ? 'Preparar Retrabajo Master' : 'Solicitar Reimpresión Master',
        'description' => $type === 'rework' ? 'Busca la requisición Master original y prepara los datos, folios y cantidades del retrabajo. Label Room lo validará e imprimirá.' : 'Busca la requisición Master original y selecciona los folios que necesita producción. Kiosk imprimirá un comprobante y Label Room revisará la solicitud antes de imprimir.',
        'steps' => [
            ['title' => 'Busca la requisición', 'description' => 'Usa el número de requisición o un Job.'],
            ['title' => $type === 'rework' ? 'Prepara el retrabajo' : 'Elige los folios', 'description' => $type === 'rework' ? 'Ajusta Jobs, modelo, folios y piezas.' : 'Marca los folios que necesitan copias.'],
            ['title' => 'Explica el motivo', 'description' => 'Indica por qué se necesita el retrabajo o la reimpresión.'],
            ['title' => 'Envía a revisión', 'description' => 'Label Room confirmará o rechazará la solicitud.'],
        ],
    ])

    @include('kiosk.partials.form-errors')

    <div class="mx-auto max-w-7xl space-y-5">
        <nav class="grid gap-3 sm:grid-cols-2" aria-label="Tipo de solicitud Master">
            <a href="{{ route('kiosk.master_action_requests.create', ['type' => 'rework']) }}"
               @class(['rounded-2xl border p-4 font-semibold transition', 'border-red-500 bg-red-50 text-red-800' => $type === 'rework', 'border-slate-200 bg-white text-slate-700 hover:border-red-300' => $type !== 'rework'])>
                Retrabajo <span class="mt-1 block text-sm font-normal">Folios afectados y, si hace falta, folios adicionales.</span>
            </a>
            <a href="{{ route('kiosk.master_action_requests.create', ['type' => 'reprint']) }}"
               @class(['rounded-2xl border p-4 font-semibold transition', 'border-red-500 bg-red-50 text-red-800' => $type === 'reprint', 'border-slate-200 bg-white text-slate-700 hover:border-red-300' => $type !== 'reprint'])>
                Reimpresión <span class="mt-1 block text-sm font-normal">Copias de folios cuya impresión ya fue confirmada.</span>
            </a>
        </nav>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="font-semibold text-slate-900">1. Busca la requisición origen</h2>
                <p class="mt-1 text-sm text-slate-500">Escribe el número de requisición o el Job de Ensamble o Empaque. Solo se muestran requisiciones originales.</p>
            </div>
            <form method="GET" action="{{ route('kiosk.master_action_requests.create') }}" class="flex flex-col gap-3 p-5 sm:flex-row sm:items-end">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="min-w-0 flex-1">
                    <label for="sourceSearch" class="text-sm font-semibold text-slate-700">Requisición o Job</label>
                    <input id="sourceSearch" name="q" value="{{ $query }}" maxlength="80" required
                           class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-red-600"
                           placeholder="Ej: 123 o MP018751019-A2641UN-PC02">
                </div>
                <button class="min-h-11 rounded-xl bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-800">Buscar</button>
            </form>

            @if($sources)
                <div class="space-y-2 border-t border-slate-200 p-5">
                    @forelse($sources as $result)
                        <a href="{{ route('kiosk.master_action_requests.create', ['type' => $type, 'q' => $query, 'source_id' => $result->id]) }}"
                           class="flex flex-col gap-2 rounded-xl border border-slate-200 p-4 transition hover:border-red-300 hover:bg-red-50/40 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-900">Requisición original #{{ $result->id }}</div>
                                <div class="mt-1 break-words text-sm text-slate-600">Ensamble: {{ $result->job_assembly ?: '—' }} · Empaque: {{ $result->job_packaging ?: '—' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $result->request_type }} · {{ $result->statusLabel() }} · {{ $result->folios_count }} folios</div>
                            </div>
                            <span class="shrink-0 font-semibold text-red-600">Seleccionar →</span>
                        </a>
                    @empty
                        <p class="text-sm text-slate-600">No se encontraron requisiciones elegibles.@if($type === 'reprint') Para reimpresión debe existir al menos un folio impreso.@endif</p>
                    @endforelse
                    {{ $sources->links() }}
                </div>
            @endif
        </section>

        @if($source)
            @php
                $availableFolios = $type === 'reprint'
                    ? $source->folios->where('status', 'printed')
                    : $source->folios;
                $selectedIds = array_map('intval', (array) old('folio_ids', []));
            @endphp
            <form method="POST" action="{{ route('kiosk.master_action_requests.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="action_type" value="{{ $type }}">
                <input type="hidden" name="master_request_id" value="{{ $source->id }}">

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <h2 class="font-semibold text-slate-900">2. Selecciona los folios de la requisición #{{ $source->id }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $source->job_assembly ?: $source->job_packaging }} · {{ $source->request_type }} · {{ $source->line?->code ?: 'Línea sin dato' }}</p>
                    </div>
                    <div class="p-5">
                        <p class="mb-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                            {{ $type === 'reprint' ? 'Solo aparecen los folios cuya impresión ya fue confirmada.' : 'Puedes marcar folios existentes, pedir folios adicionales o ambas cosas. Los nuevos continuarán después del último folio de esta requisición.' }}
                        </p>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach($availableFolios as $folio)
                                @php
                                    $folioQuantity = $folio->qty_for_folio !== null
                                        ? number_format((int) $folio->qty_for_folio).' pzas'
                                        : 'Cantidad no registrada';
                                @endphp
                                <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 hover:border-red-300 hover:bg-red-50/40">
                                    <input type="checkbox" name="folio_ids[]" value="{{ $folio->id }}" @checked(in_array($folio->id, $selectedIds, true))
                                           class="mt-0.5 h-5 w-5 shrink-0 rounded border-slate-300 text-red-600 focus:ring-red-600">
                                    <span class="min-w-0 text-sm">
                                        <strong class="block">F{{ $folio->folio_number }}</strong>
                                        @if($source->request_type === \App\Models\MasterModelMapping::TYPE_ASSEMBLY_PACKAGING)
                                            <span class="block font-medium text-slate-800">Ensamble: {{ $folioQuantity }}</span>
                                            <span class="block font-medium text-slate-800">Empaque: {{ $folioQuantity }}</span>
                                        @else
                                            <span class="block font-medium text-slate-800">Cantidad: {{ $folioQuantity }}</span>
                                        @endif
                                        <span class="block text-xs text-slate-500">{{ $folio->is_partial ? 'Parcial' : 'Normal' }} · {{ $folio->status === 'printed' ? 'Impreso' : 'Pendiente' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <h2 class="font-semibold text-slate-900">3. Completa la solicitud</h2>
                        <p class="mt-1 text-sm text-slate-500">Label Room revisará esta información y confirmará los folios antes de imprimir.</p>
                    </div>
                    <div class="grid gap-4 p-5 md:grid-cols-2">
                        @if($type === 'reprint')
                            <div>
                                <label for="copies" class="text-sm font-semibold text-slate-700">Copias por folio *</label>
                                <input id="copies" name="copies" type="number" min="1" max="20" value="{{ old('copies', 1) }}" required
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-red-600">
                            </div>
                        @else
                            <div>
                                <label for="additionalFolios" class="text-sm font-semibold text-slate-700">Folios adicionales</label>
                                <input id="additionalFolios" name="additional_folios_count" type="number" min="0" max="500" value="{{ old('additional_folios_count', 0) }}" required
                                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-red-600">
                                <p class="mt-1 text-xs text-slate-500">Escribe 0 si solo necesitas los folios seleccionados.</p>
                            </div>
                        @endif
                        <div class="md:col-span-2">
                            <label for="actionReason" class="text-sm font-semibold text-slate-700">Motivo de la solicitud *</label>
                            <textarea id="actionReason" name="reason" rows="3" minlength="10" maxlength="500" required
                                      class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-red-600"
                                      placeholder="Explica qué ocurrió y por qué se necesitan estas hojas.">{{ old('reason') }}</textarea>
                        </div>
                    </div>
                </section>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <button class="min-h-12 w-full rounded-xl bg-red-600 px-5 py-3 font-semibold text-white hover:bg-red-500 focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                        Enviar solicitud a Label Room
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
