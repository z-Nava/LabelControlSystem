@extends('layouts.app', ['title' => 'Centro de impresión'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6" id="label-print-center"
     data-preview-url="{{ route('label_requests.print_batches.preview', ['label_request' => $labelRequest, 'batch' => $batch]) }}"
     data-confirm-url="{{ route('label_requests.print_batches.confirm', ['label_request' => $labelRequest, 'batch' => $batch]) }}"
     data-csrf-token="{{ csrf_token() }}"
     data-back-url="{{ route('label_requests.show', $labelRequest) }}">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Centro de impresión</h1>
            <p class="text-slate-600 mt-1">Requisición #{{ $labelRequest->id }} · Batch #{{ $batch->id }}</p>
        </div>
        <a href="{{ route('label_requests.show', $labelRequest) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al detalle</a>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="mt-6 flex flex-wrap gap-2">
        <button id="connect-printer" type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Conectar impresora</button>
        <button id="preview-batch" type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Vista previa (Preview)</button>
        <button id="print-batch" type="button" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">Imprimir</button>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Impresora seleccionada</div>
            <div id="selected-printer" class="mt-1 text-sm text-slate-800">Sin conectar</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
            <div class="text-xs uppercase tracking-wide text-slate-500">Estado</div>
            <div id="print-status" class="mt-1 text-sm text-slate-700">Pendiente de conexión y preview.</div>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 text-sm font-semibold text-slate-900">Resumen preview</div>
        <div id="preview-summary" class="p-4 text-sm text-slate-600">Aún sin preview.</div>
    </div>

    <div class="mt-4 rounded-xl border border-slate-200">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 text-sm font-semibold text-slate-900">ZPL renderizado</div>
        <pre id="preview-zpl" class="max-h-[420px] overflow-auto p-4 text-xs text-slate-700 whitespace-pre-wrap">Sin preview.</pre>
    </div>
</div>

<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
<script>
(() => {
    const root = document.getElementById('label-print-center');
    if (!root) return;

    const connectButton = document.getElementById('connect-printer');
    const previewButton = document.getElementById('preview-batch');
    const printButton = document.getElementById('print-batch');
    const printerBox = document.getElementById('selected-printer');
    const statusBox = document.getElementById('print-status');
    const previewSummary = document.getElementById('preview-summary');
    const previewZpl = document.getElementById('preview-zpl');
    const storageKey = 'label_print_selected_printer';

    let selectedDevice = null;
    let previewPayload = null;

    const csrfToken = root.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

    const setStatus = (message, isError = false) => {
        statusBox.textContent = message;
        statusBox.classList.toggle('text-red-700', isError);
    };

    const restoreStoredPrinter = () => {
        const raw = localStorage.getItem(storageKey);
        if (!raw) return;

        try {
            const parsed = JSON.parse(raw);
            printerBox.textContent = `${parsed.name} (${parsed.connection || 'connection'})`;
        } catch (error) {
            localStorage.removeItem(storageKey);
        }
    };

    const connectPrinter = () => {
        if (!window.BrowserPrint) {
            setStatus('No se encontró BrowserPrint. Instala/abre Zebra Browser Print.', true);
            return;
        }

        setStatus('Buscando impresoras Zebra...');

        BrowserPrint.getDefaultDevice('printer', (device) => {
            if (device) {
                selectedDevice = device;
                localStorage.setItem(storageKey, JSON.stringify({
                    name: selectedDevice.name,
                    uid: selectedDevice.uid,
                    connection: selectedDevice.connection,
                }));

                printerBox.textContent = `${selectedDevice.name} (${selectedDevice.connection || 'connection'})`;
                setStatus('Impresora conectada (default). Ya puedes hacer preview o imprimir.');
                return;
            }

            BrowserPrint.getLocalDevices((devices) => {
                const printers = (devices || []).filter((candidate) => candidate.deviceType === 'printer');

                if (!printers.length) {
                    setStatus('No se detectaron impresoras locales.', true);
                    return;
                }

                selectedDevice = printers[0];
                localStorage.setItem(storageKey, JSON.stringify({
                    name: selectedDevice.name,
                    uid: selectedDevice.uid,
                    connection: selectedDevice.connection,
                }));

                printerBox.textContent = `${selectedDevice.name} (${selectedDevice.connection || 'connection'})`;
                setStatus('Impresora conectada (local). Ya puedes hacer preview o imprimir.');
            }, (error) => {
                setStatus(`Error al conectar impresora: ${error}`, true);
            }, 'printer');
        }, (error) => {
            setStatus(`Error al obtener impresora default: ${error}`, true);
        });
    };

    const sendToPrinter = (zplChunk) => new Promise((resolve, reject) => {
        selectedDevice.send(zplChunk, () => resolve(), (error) => reject(new Error(error)));
    });

    const loadPreview = async () => {
        setStatus('Generando preview...');

        const response = await fetch(root.dataset.previewUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({}),
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || 'No se pudo generar el preview.');
        }

        previewPayload = await response.json();

        const lines = (previewPayload.documents || []).map((doc) => {
            return `Tipo: ${doc.label_type} · Template: ${doc.template?.name || '-'} · Profile: ${doc.profile?.name || 'Sin perfil'} · Unidades: ${doc.units_count}`;
        });

        previewSummary.textContent = lines.join('\n') || 'No hay documentos para este batch.';
        previewZpl.textContent = previewPayload.zpl || 'Sin ZPL renderizado';
        setStatus('Preview generado.');
    };

    const confirmPrinted = async () => {
        const response = await fetch(root.dataset.confirmUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ printed_ok: true }),
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || 'No se pudo confirmar impresión.');
        }

        return response.json();
    };

    const printBatch = async () => {
        try {
            if (!selectedDevice) {
                setStatus('Primero conecta una impresora.', true);
                return;
            }

            if (!previewPayload || !previewPayload.zpl) {
                await loadPreview();
            }

            if (!previewPayload?.zpl) {
                setStatus('No hay ZPL para imprimir.', true);
                return;
            }

            const documents = (previewPayload.documents || []).map((doc) => doc.zpl).filter(Boolean);
            const queue = documents.length ? documents : [previewPayload.zpl];

            setStatus(`Enviando ${queue.length} bloque(s) ZPL a BrowserPrint...`);

            for (const chunk of queue) {
                await sendToPrinter(chunk);
            }

            try {
                const result = await confirmPrinted();
                setStatus(`Impresión confirmada. Seriales actualizados: ${result.updated_serial_units}.`);
            } catch (error) {
                setStatus(`Impreso localmente, pero falló confirmación backend: ${error.message}`, true);
            }
        } catch (error) {
            setStatus(`Error en impresión: ${error.message}`, true);
        }
    };

    connectButton?.addEventListener('click', connectPrinter);
    previewButton?.addEventListener('click', () => loadPreview().catch((error) => setStatus(error.message, true)));
    printButton?.addEventListener('click', printBatch);

    restoreStoredPrinter();
})();
</script>
@endsection
