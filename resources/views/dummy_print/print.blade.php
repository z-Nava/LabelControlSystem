@extends('layouts.app', ['title' => 'Centro de impresión Dummy QR'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6" id="dummy-print-center"
     data-templates='@json($templatesByType)'
     data-confirm-url="{{ route('dummy_requests.print_batches.confirm', ['dummy_request' => $dummyRequest, 'batch' => $batch]) }}"
     data-csrf-token="{{ csrf_token() }}"
     data-already-printed="{{ $alreadyPrinted ? '1' : '0' }}">
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
        <button id="prepare-print" type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Preparar impresión</button>
        <button id="print-batch" type="button" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:bg-slate-400">Imprimir</button>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Impresora seleccionada</div>
            <div id="selected-printer" class="mt-1 text-sm text-slate-800">Sin conectar</div>
            <div class="mt-3">
                <label for="printer-select" class="text-xs uppercase tracking-wide text-slate-500">Elegir impresora Zebra detectada</label>
                <select id="printer-select" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Primero detecta impresoras</option>
                </select>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Estado</div>
            <div id="print-status" class="mt-1 text-sm text-slate-700">Primero conecta una impresora para habilitar la impresión directa.</div>
        </div>
    </div>

    <div id="print-progress-panel" class="mt-4 rounded-xl border border-slate-200 bg-white p-4" aria-live="polite">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Progreso de impresión</div>
            <div id="print-progress-summary" class="text-sm font-semibold text-slate-700">Pendiente</div>
        </div>
        <div
            id="print-progress"
            class="mt-3 h-3 overflow-hidden rounded-full bg-slate-200"
            role="progressbar"
            aria-label="Progreso informativo de impresión"
            aria-valuemin="0"
            aria-valuemax="{{ (int) $batch->quantity }}"
            aria-valuenow="0"
        >
            <div id="print-progress-bar" class="h-full w-0 rounded-full bg-red-600 transition-all duration-200 ease-out"></div>
        </div>
        <div id="print-progress-detail" class="mt-2 text-sm text-slate-600">0 de {{ number_format((int) $batch->quantity) }} etiquetas enviadas (0%).</div>
        <p class="mt-1 text-xs text-slate-500">Indicador informativo: muestra los datos aceptados por Zebra Browser Print; no confirma la salida física de cada etiqueta.</p>
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
    const prepareButton = document.getElementById('prepare-print');
    const printButton = document.getElementById('print-batch');
    const printerBox = document.getElementById('selected-printer');
    const printerSelect = document.getElementById('printer-select');
    const statusBox = document.getElementById('print-status');
    const progress = document.getElementById('print-progress');
    const progressBar = document.getElementById('print-progress-bar');
    const progressSummary = document.getElementById('print-progress-summary');
    const progressDetail = document.getElementById('print-progress-detail');
    const storageKey = 'dummy_print_selected_printer';

    const templatesByType = JSON.parse(root.dataset.templates || '{}');
    const items = Array.from(root.querySelectorAll('tbody tr[data-item]')).map((row) => JSON.parse(row.dataset.item));
    const alreadyPrinted = root.dataset.alreadyPrinted === '1';
    const csrfToken = root.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
    let selectedDevice = null;
    let availablePrinters = [];
    let printPrepared = false;
    let printLocked = false;
    let isPrinting = false;

    const setStatus = (message, isError = false) => {
        statusBox.textContent = message;
        statusBox.classList.toggle('text-red-700', isError);
    };

    const updatePrintProgress = (sent, total, state = 'idle') => {
        const safeTotal = Math.max(0, Number(total) || 0);
        const safeSent = Math.min(Math.max(0, Number(sent) || 0), safeTotal);
        const percentage = safeTotal > 0 ? Math.round((safeSent / safeTotal) * 100) : 0;
        const summaries = {
            idle: 'Pendiente',
            printing: 'Imprimiendo',
            sent: 'Envío completado',
            error: 'Envío interrumpido',
        };

        if (progress) {
            progress.setAttribute('aria-valuemax', String(safeTotal));
            progress.setAttribute('aria-valuenow', String(safeSent));
        }

        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
            progressBar.classList.toggle('bg-red-600', !['sent', 'error'].includes(state));
            progressBar.classList.toggle('bg-emerald-500', state === 'sent');
            progressBar.classList.toggle('bg-amber-500', state === 'error');
        }

        if (progressSummary) {
            progressSummary.textContent = summaries[state] || summaries.idle;
        }

        if (progressDetail) {
            progressDetail.textContent = `${safeSent.toLocaleString()} de ${safeTotal.toLocaleString()} etiquetas enviadas (${percentage}%).`;
        }
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

    const getPrinterId = (device) => [device?.name || '', device?.uid || '', device?.connection || ''].join('::');

    const persistSelectedPrinter = (device) => {
        localStorage.setItem(storageKey, JSON.stringify({
            name: device.name,
            uid: device.uid,
            connection: device.connection,
        }));
    };

    const setSelectedPrinter = (device) => {
        if (!device) {
            selectedDevice = null;
            printerBox.textContent = 'Sin conectar';
            return;
        }

        selectedDevice = device;
        persistSelectedPrinter(device);
        printerBox.textContent = `${device.name} (${device.connection || 'connection'})`;
    };

    const restorePreferredPrinter = () => {
        const raw = localStorage.getItem(storageKey);
        if (!raw) return null;

        try {
            const parsed = JSON.parse(raw);
            return availablePrinters.find((printer) => {
                return (printer.name || '') === (parsed.name || '')
                    && (printer.uid || '') === (parsed.uid || '')
                    && (printer.connection || '') === (parsed.connection || '');
            }) || null;
        } catch (_error) {
            localStorage.removeItem(storageKey);
            return null;
        }
    };

    const renderPrinterOptions = (preferredPrinter) => {
        if (!printerSelect) return;

        printerSelect.innerHTML = '';

        if (!availablePrinters.length) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No se detectaron impresoras';
            printerSelect.appendChild(option);
            printerSelect.value = '';
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecciona una impresora';
        printerSelect.appendChild(placeholder);

        availablePrinters.forEach((printer) => {
            const option = document.createElement('option');
            option.value = getPrinterId(printer);
            option.textContent = `${printer.name || 'Sin nombre'} (${printer.connection || 'connection'})`;
            printerSelect.appendChild(option);
        });

        if (preferredPrinter) {
            printerSelect.value = getPrinterId(preferredPrinter);
            setSelectedPrinter(preferredPrinter);
            return;
        }

        printerSelect.value = '';
        setSelectedPrinter(null);
    };

    const connectPrinter = () => {
        if (!window.BrowserPrint) {
            setStatus('No se encontró BrowserPrint. Instala/abre Zebra Browser Print.', true);
            return;
        }

        setStatus('Buscando impresoras Zebra...');

        BrowserPrint.getLocalDevices((devices) => {
            availablePrinters = (devices || []).filter((candidate) => candidate.deviceType === 'printer');

            if (!availablePrinters.length) {
                renderPrinterOptions(null);
                setStatus('No se detectaron impresoras locales.', true);
                return;
            }

            const preferredPrinter = restorePreferredPrinter();
            renderPrinterOptions(preferredPrinter);

            if (availablePrinters.length > 1 && !selectedDevice) {
                setStatus(`Se detectaron ${availablePrinters.length} impresoras. Elige una para continuar.`);
                return;
            }

            if (!selectedDevice && availablePrinters.length === 1) {
                setSelectedPrinter(availablePrinters[0]);
            }

            setStatus(`Se detectaron ${availablePrinters.length} impresora(s). ${selectedDevice ? 'Lista para imprimir.' : 'Selecciona una para habilitar impresión.'}`);
        }, (error) => {
            setStatus(`Error al conectar impresora: ${error}`, true);
        }, 'printer');
    };

    const buildItemZpl = (item) => {
        const template = templatesByType[item.dummy_type];
        if (!template) {
            throw new Error(`No existe template activo para tipo ${String(item.dummy_type).toUpperCase()}.`);
        }

        const qrPayload = String(item.qr_payload || '');
        const qrPayloadHex = qrPayload
            .replaceAll('\\', '\\5C')
            .replaceAll('^', '\\5E')
            .replaceAll('~', '\\7E');
        const normalizedQrField = `^FH\\^FDLA,${qrPayloadHex}^FS`;

        let zpl = template;
        ['N', 'R', 'I', 'B'].forEach((orientation) => {
            zpl = zpl.replaceAll(`^FD${orientation},A^DM^^FG^^JOB^^CONSECUTIVO^^^FS`, normalizedQrField);
            zpl = zpl.replaceAll(`^FD${orientation},A^RW^^FG^^JOB^^CONSECUTIVO^^^FS`, normalizedQrField);
        });

        zpl = zpl
            .replaceAll('^FH\\^FDLA,^DM^^FG^^JOB^^CONSECUTIVO^^^FS', normalizedQrField)
            .replaceAll('^FH\\^FDLA,^RW^^FG^^JOB^^CONSECUTIVO^^^FS', normalizedQrField)
            .replaceAll('^FDLA,^DM^^FG^^JOB^^CONSECUTIVO^^^FS', normalizedQrField)
            .replaceAll('^FDLA,^RW^^FG^^JOB^^CONSECUTIVO^^^FS', normalizedQrField)
            .replaceAll('^DM^^FG^^JOB^^CONSECUTIVO^^', qrPayloadHex)
            .replaceAll('^RW^^FG^^JOB^^CONSECUTIVO^^', qrPayloadHex)
            .replaceAll('^FDLA,', '^FH\\^FDLA,')
            .replaceAll('^FH\\^FH\\^FDLA,', '^FH\\^FDLA,')
            .replaceAll('^FG^', item.fg_code)
            .replaceAll('^JOB^', item.job_number)
            .replaceAll('^CONSECUTIVO^', item.consecutive_10d);

        return zpl;
    };

    const sendToPrinter = (zplChunk) => new Promise((resolve, reject) => {
        selectedDevice.send(zplChunk, () => resolve(), (error) => reject(new Error(error)));
    });

    const showAlert = (title, text, icon = 'error') => {
        if (window.Swal?.fire) {
            window.Swal.fire(title, text, icon);
            return;
        }

        window.alert(`${title}: ${text}`);
    };

    const setPrintBlocked = (message) => {
        printLocked = true;

        if (printButton) {
            printButton.disabled = true;
            printButton.title = message;
        }

        setStatus(message, true);
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
        let sentCount = 0;
        let totalCount = 0;

        try {
            if (printLocked || isPrinting || printButton?.disabled) {
                return;
            }

            if (!selectedDevice) {
                setStatus('Primero conecta una impresora.', true);
                return;
            }

            if (!printPrepared) {
                showAlert('Preparación requerida', 'Debes presionar "Preparar impresión" antes de imprimir.', 'error');
                setStatus('Debes preparar la impresión primero para liberar el botón de imprimir.', true);
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

            totalCount = queue.length;
            isPrinting = true;
            if (printButton) {
                printButton.disabled = true;
            }
            updatePrintProgress(0, totalCount, 'printing');
            setStatus(`Enviando ${queue.length} etiqueta(s) a la impresora...`);

            for (const chunk of queue) {
                await sendToPrinter(chunk);
                sentCount += 1;
                updatePrintProgress(sentCount, totalCount, 'printing');
            }

            updatePrintProgress(sentCount, totalCount, 'sent');
            const result = await confirmPrinted();
            setPrintBlocked(result.message || 'Batch confirmado como impreso. Botón bloqueado para evitar duplicidad.');
        } catch (error) {
            if (totalCount > 0) {
                updatePrintProgress(sentCount, totalCount, sentCount === totalCount ? 'sent' : 'error');
            }
            setStatus(`Error en impresión: ${error.message}`, true);
        } finally {
            isPrinting = false;
            if (printButton && !printLocked) {
                printButton.disabled = false;
            }
        }
    };

    const preparePrint = async () => {
        try {
            if (printButton?.disabled) {
                return;
            }

            if (!selectedDevice) {
                setStatus('Primero conecta una impresora.', true);
                showAlert('Impresora requerida', 'Conecta una impresora antes de preparar la impresión.', 'error');
                return;
            }

            if (!items.length) {
                setStatus('No hay dummys para preparar en este batch.', true);
                return;
            }

            const firstDummy = items[0];
            const zpl = buildItemZpl(firstDummy);
            setStatus('Enviando dummy de prueba para validación...');
            await sendToPrinter(zpl);

            printPrepared = true;
            setStatus('Dummy de prueba enviado. Si el template está centrado, ya puedes presionar "Imprimir".');
            showAlert('Preparación completada', 'Se imprimió el primer dummy de prueba. Verifica centrado y luego imprime el batch completo.', 'success');
        } catch (error) {
            printPrepared = false;
            setStatus(`Error al preparar impresión: ${error.message}`, true);
            showAlert('Error de preparación', error.message || 'No se pudo enviar el dummy de prueba.', 'error');
        }
    };

    connectButton?.addEventListener('click', connectPrinter);
    printerSelect?.addEventListener('change', (event) => {
        const selectedId = event.target.value;
        if (!selectedId) {
            setSelectedPrinter(null);
            setStatus('Selecciona una impresora para continuar.', true);
            return;
        }

        const foundPrinter = availablePrinters.find((printer) => getPrinterId(printer) === selectedId);
        if (!foundPrinter) {
            setSelectedPrinter(null);
            setStatus('No se pudo recuperar la impresora seleccionada. Vuelve a detectar impresoras.', true);
            return;
        }

        setSelectedPrinter(foundPrinter);
        setStatus('Impresora seleccionada. Ya puedes preparar e imprimir.');
    });
    prepareButton?.addEventListener('click', preparePrint);
    printButton?.addEventListener('click', printBatch);

    restoreStoredPrinter();
    updatePrintProgress(0, items.reduce((total, item) => total + Math.max(1, Number(item.copies || 1)), 0));

    if (alreadyPrinted) {
        setPrintBlocked('Este batch ya fue confirmado como impreso. El botón se bloqueó para evitar duplicidad.');
    }
})();
</script>
@endsection
