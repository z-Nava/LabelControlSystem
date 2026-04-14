@extends('layouts.app', ['title' => 'Centro de impresión Dummy QR'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6" id="dummy-print-center"
     data-templates='@json($templatesByType)'>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Centro de impresión Dummy QR</h1>
            <p class="text-slate-600 mt-1">Requisición #{{ $dummyRequest->id }} · Batch #{{ $batch->id }} · {{ strtoupper($batch->batch_type) }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('dummy_requests.show', $dummyRequest) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver al detalle</a>
        </div>
    </div>

    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
        <div><span class="font-semibold">Job:</span> {{ $dummyRequest->job_number }} | <span class="font-semibold">FG:</span> {{ $dummyRequest->fg_code }}</div>
        <div><span class="font-semibold">Cantidad total a imprimir:</span> {{ number_format($batch->quantity) }}</div>
        <div><span class="font-semibold">Línea/Turno:</span> {{ $dummyRequest->line?->code }} · {{ $dummyRequest->shift?->code }}</div>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <button id="connect-printer" type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Conectar impresora</button>
        <button id="print-batch" type="button" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">Imprimir</button>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Impresora seleccionada</div>
            <div id="selected-printer" class="mt-1 text-sm text-slate-800">Sin conectar</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Estado</div>
            <div id="print-status" class="mt-1 text-sm text-slate-700">Primero conecta una impresora para habilitar la impresión directa.</div>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
            <tr class="text-left text-slate-500 border-b border-slate-200">
                <th class="py-3 px-4">Consecutivo</th>
                <th class="py-3 px-4">Tipo</th>
                <th class="py-3 px-4">Copias</th>
                <th class="py-3 px-4">QR payload</th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @foreach($batch->items as $item)
                @php
                    $rowPayload = [
                        'dummy_type' => strtolower((string) $item->requestItem?->dummy_type),
                        'copies' => (int) $item->copies,
                        'job_number' => (string) ($dummyRequest->job_number ?? ''),
                        'fg_code' => (string) ($dummyRequest->fg_code ?? ''),
                        'consecutive_10d' => (string) ($item->requestItem?->consecutive_10d ?? ''),
                        'qr_payload' => (string) ($item->requestItem?->qr_payload ?? ''),
                    ];
                @endphp
                <tr class="hover:bg-slate-50" data-item='@json($rowPayload)'>
                    <td class="py-3 px-4 font-mono">{{ $item->requestItem?->consecutive_10d }}</td>
                    <td class="py-3 px-4">{{ strtoupper($item->requestItem?->dummy_type ?? '-') }}</td>
                    <td class="py-3 px-4">{{ number_format((int) $item->copies) }}</td>
                    <td class="py-3 px-4 font-mono text-xs">{{ $item->requestItem?->qr_payload }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
<script>
(() => {
    const root = document.getElementById('dummy-print-center');
    if (!root) return;

    const connectButton = document.getElementById('connect-printer');
    const printButton = document.getElementById('print-batch');
    const printerBox = document.getElementById('selected-printer');
    const statusBox = document.getElementById('print-status');
    const storageKey = 'dummy_print_selected_printer';

    const templatesByType = JSON.parse(root.dataset.templates || '{}');
    const items = Array.from(root.querySelectorAll('tbody tr[data-item]')).map((row) => JSON.parse(row.dataset.item));
    let selectedDevice = null;

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
        } catch (_error) {
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
                setStatus('Impresora conectada (predeterminada). Ya puedes imprimir.');
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
                setStatus('Impresora conectada (local). Ya puedes imprimir.');
            }, (error) => {
                setStatus(`Error al conectar impresora: ${error}`, true);
            }, 'printer');
        }, (error) => {
            setStatus(`Error al obtener impresora default: ${error}`, true);
        });
    };

    const buildItemZpl = (item) => {
        const template = templatesByType[item.dummy_type];
        if (!template) {
            throw new Error(`No existe template activo para tipo ${String(item.dummy_type).toUpperCase()}.`);
        }

        return template
            .replaceAll('^FG^', item.fg_code)
            .replaceAll('^JOB^', item.job_number)
            .replaceAll('^CONSECUTIVO^', item.consecutive_10d)
            .replaceAll('^DM^^FG^^JOB^^CONSECUTIVO^^', item.qr_payload);
    };

    const sendToPrinter = (zplChunk) => new Promise((resolve, reject) => {
        selectedDevice.send(zplChunk, () => resolve(), (error) => reject(new Error(error)));
    });

    const printBatch = async () => {
        try {
            if (!selectedDevice) {
                setStatus('Primero conecta una impresora.', true);
                return;
            }

            if (!items.length) {
                setStatus('No hay dummys para imprimir en este batch.', true);
                return;
            }

            const queue = [];
            for (const item of items) {
                const zpl = buildItemZpl(item);
                const copies = Math.max(1, Number(item.copies || 1));
                for (let i = 0; i < copies; i += 1) {
                    queue.push(zpl);
                }
            }

            setStatus(`Enviando ${queue.length} etiqueta(s) a la impresora...`);

            for (const chunk of queue) {
                await sendToPrinter(chunk);
            }

            setStatus('Impresión enviada correctamente a la impresora conectada.');
        } catch (error) {
            setStatus(`Error en impresión: ${error.message}`, true);
        }
    };

    connectButton?.addEventListener('click', connectPrinter);
    printButton?.addEventListener('click', printBatch);

    restoreStoredPrinter();
})();
</script>
@endsection
