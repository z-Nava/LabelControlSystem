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

    <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4">
        <h2 class="text-base font-semibold text-blue-900">¿Qué acabas de hacer y qué sigue?</h2>
        <ol class="mt-3 list-decimal list-inside space-y-2 text-sm text-blue-900">
            <li>Esta pantalla corresponde al <span class="font-semibold">batch #{{ $batch->id }}</span> de la requisición <span class="font-semibold">#{{ $labelRequest->id }}</span>.</li>
            <li>Conecta tu impresora y presiona <span class="font-semibold">“Preparar impresión”</span> para revisar el resumen del lote.</li>
            <li>Si los datos son correctos, presiona <span class="font-semibold">“Imprimir ahora”</span>.</li>
            <li>Al finalizar, verás una confirmación de impresión para saber que el sistema registró correctamente lo impreso.</li>
        </ol>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <button id="connect-printer" type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Conectar impresora</button>
        <button id="preview-batch" type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Preparar impresión</button>
        <button id="print-batch" type="button" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">Imprimir ahora</button>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Impresora seleccionada</div>
            <div id="selected-printer" class="mt-1 text-sm text-slate-800">Sin conectar</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:col-span-2">
            <div class="text-xs uppercase tracking-wide text-slate-500">Estado</div>
            <div id="print-status" class="mt-1 text-sm text-slate-700">Pendiente de conexión y preparación de impresión.</div>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="text-xs uppercase tracking-wide text-emerald-700">Confirmación</div>
            <div id="print-confirmation" class="mt-1 text-sm text-emerald-800">Aún no hay impresión confirmada.</div>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-slate-200">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 text-sm font-semibold text-slate-900">Resumen de lo que se imprimirá</div>
        <div id="preview-summary" class="p-4 text-sm text-slate-600">Aún no se ha preparado la impresión.</div>
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
    const confirmationBox = document.getElementById('print-confirmation');
    const previewSummary = document.getElementById('preview-summary');
    const storageKey = 'label_print_selected_printer';

    let selectedDevice = null;
    let previewPayload = null;
    let printPrepared = false;

    const csrfToken = root.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

    const setStatus = (message, isError = false) => {
        statusBox.textContent = message;
        statusBox.classList.toggle('text-red-700', isError);
    };

    const showAlert = (title, text, icon = 'error') => {
        if (window.Swal?.fire) {
            window.Swal.fire(title, text, icon);
            return;
        }

        window.alert(`${title}: ${text}`);
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
                printPrepared = false;
                previewPayload = null;
                localStorage.setItem(storageKey, JSON.stringify({
                    name: selectedDevice.name,
                    uid: selectedDevice.uid,
                    connection: selectedDevice.connection,
                }));

                printerBox.textContent = `${selectedDevice.name} (${selectedDevice.connection || 'connection'})`;
                setStatus('Impresora conectada (predeterminada). Ya puedes preparar e imprimir.');
                return;
            }

            BrowserPrint.getLocalDevices((devices) => {
                const printers = (devices || []).filter((candidate) => candidate.deviceType === 'printer');

                if (!printers.length) {
                    setStatus('No se detectaron impresoras locales.', true);
                    return;
                }

                selectedDevice = printers[0];
                printPrepared = false;
                previewPayload = null;
                localStorage.setItem(storageKey, JSON.stringify({
                    name: selectedDevice.name,
                    uid: selectedDevice.uid,
                    connection: selectedDevice.connection,
                }));

                printerBox.textContent = `${selectedDevice.name} (${selectedDevice.connection || 'connection'})`;
                setStatus('Impresora conectada (local). Ya puedes preparar e imprimir.');
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
        setStatus('Preparando información de impresión...');

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
            throw new Error(errorText || 'No se pudo preparar la impresión.');
        }

        previewPayload = await response.json();

        const lines = (previewPayload.documents || []).map((doc) => {
            return `Tipo de etiqueta: ${doc.label_type} · Cantidad: ${doc.units_count}`;
        });

        previewSummary.textContent = lines.join('\n') || 'No hay documentos para este lote.';
        setStatus('Preparación completada. Revisa el resumen y presiona "Imprimir ahora".');
    };

    const preparePrint = async () => {
        try {
            if (!selectedDevice) {
                setStatus('Primero conecta una impresora.', true);
                showAlert('Impresora requerida', 'Conecta una impresora antes de preparar la impresión.', 'error');
                return;
            }

            await loadPreview();

            const testZpl = (previewPayload?.documents || []).map((doc) => doc.zpl).find(Boolean) || previewPayload?.zpl || '';
            if (!testZpl) {
                printPrepared = false;
                setStatus('No hay contenido de prueba para imprimir.', true);
                showAlert('Sin contenido', 'No se encontró contenido ZPL para la impresión de prueba.', 'warning');
                return;
            }

            setStatus('Enviando etiqueta de prueba para validar template...');
            await sendToPrinter(testZpl);

            printPrepared = true;
            setStatus('Impresión de prueba enviada. Si el template está correcto, ya puedes presionar "Imprimir ahora".');
            showAlert('Preparación completada', 'Se envió una impresión de prueba para validar el template. Si está correcta, ya puedes imprimir el lote.', 'success');
        } catch (error) {
            printPrepared = false;
            setStatus(`Error al preparar impresión: ${error.message}`, true);
            showAlert('Error de preparación', error.message || 'No se pudo enviar la impresión de prueba.', 'error');
        }
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

            if (!printPrepared) {
                showAlert('Preparación requerida', 'Debes presionar "Preparar impresión" antes de imprimir.', 'error');
                setStatus('Debes preparar la impresión primero para liberar el botón de imprimir.', true);
                return;
            }

            if (!previewPayload || !previewPayload.zpl) {
                setStatus('No hay preparación activa. Presiona "Preparar impresión" nuevamente.', true);
                printPrepared = false;
                return;
            }

            if (!previewPayload?.zpl) {
                setStatus('No hay contenido listo para imprimir.', true);
                return;
            }

            const documents = (previewPayload.documents || []).map((doc) => doc.zpl).filter(Boolean);
            const queue = documents.length ? documents : [previewPayload.zpl];

            setStatus(`Enviando ${queue.length} documento(s) a la impresora...`);

            for (const chunk of queue) {
                await sendToPrinter(chunk);
            }

            try {
                const result = await confirmPrinted();
                setStatus('Impresión enviada y confirmada correctamente.');
                if (confirmationBox) {
                    confirmationBox.textContent = `Impresión confirmada para el batch #${result.batch_id}. Seriales actualizados: ${result.updated_serial_units}.`;
                }
            } catch (error) {
                setStatus(`Impreso localmente, pero falló confirmación backend: ${error.message}`, true);
            }
        } catch (error) {
            setStatus(`Error en impresión: ${error.message}`, true);
        }
    };

    connectButton?.addEventListener('click', connectPrinter);
    previewButton?.addEventListener('click', preparePrint);
    printButton?.addEventListener('click', printBatch);

    restoreStoredPrinter();
})();
</script>
@endsection
