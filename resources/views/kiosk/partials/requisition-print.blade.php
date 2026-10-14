@if($requisitionPrintConfig)
    <section id="kiosk-requisition-print" class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">{{ $requisitionPrintConfig['receiptTitle'] }}</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-950">
                    Etiqueta {{ $requisitionPrintConfig['labelSize'] }} · {{ $requisitionPrintConfig['requestName'] }} #{{ $requisitionPrintConfig['requestId'] }}
                </h2>
                <p id="kiosk-requisition-print-status" class="mt-2 text-sm font-semibold text-slate-700">
                    {{ $requisitionPrintConfig['autoStart'] ? 'Preparando impresión automática…' : 'Comprobante seleccionado. Imprímelo cuando la impresora esté lista.' }}
                </p>
                <p class="mt-1 text-xs text-slate-600">
                    Impresora: <span id="kiosk-requisition-printer">{{ $requisitionPrintConfig['autoStart'] ? 'Buscando dispositivo Zebra…' : 'Se buscará al imprimir' }}</span>
                </p>
            </div>
            <button id="kiosk-requisition-print-retry"
                    type="button"
                    class="{{ $requisitionPrintConfig['autoStart'] ? 'hidden ' : '' }}min-h-12 rounded-xl bg-amber-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2">
                {{ $requisitionPrintConfig['mode'] === 'reprint' ? 'Reimprimir comprobante' : 'Imprimir comprobante' }}
            </button>
        </div>
    </section>

    <script id="kiosk-requisition-print-config" type="application/json">{!! json_encode($requisitionPrintConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
