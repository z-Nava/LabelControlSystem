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

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Impresora SERIAL</div>
            <div id="selected-printer-serial" class="mt-1 text-sm text-slate-800">Sin seleccionar</div>
            <div class="mt-3">
                <label for="printer-select-serial" class="text-xs uppercase tracking-wide text-slate-500">Elegir impresora para Serial</label>
                <select id="printer-select-serial" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Primero detecta impresoras</option>
                </select>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Impresora RATING</div>
            <div id="selected-printer-rating" class="mt-1 text-sm text-slate-800">Sin seleccionar</div>
            <div class="mt-3">
                <label for="printer-select-rating" class="text-xs uppercase tracking-wide text-slate-500">Elegir impresora para Rating</label>
                <select id="printer-select-rating" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Primero detecta impresoras</option>
                </select>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
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
    const serialPrinterBox = document.getElementById('selected-printer-serial');
    const ratingPrinterBox = document.getElementById('selected-printer-rating');
    const serialPrinterSelect = document.getElementById('printer-select-serial');
    const ratingPrinterSelect = document.getElementById('printer-select-rating');
    const statusBox = document.getElementById('print-status');
    const confirmationBox = document.getElementById('print-confirmation');
    const previewSummary = document.getElementById('preview-summary');
    const storageKeys = {
        serial: 'label_print_selected_printer_serial',
        rating: 'label_print_selected_printer_rating',
    };

    let availablePrinters = [];
    let selectedPrinters = { serial: null, rating: null };
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

    const getPrinterId = (device) => [device?.name || '', device?.uid || '', device?.connection || ''].join('::');
    const getPrinterLabel = (device) => `${device?.name || 'Sin nombre'} (${device?.connection || 'connection'})`;

    const persistSelectedPrinter = (labelType, device) => {
        localStorage.setItem(storageKeys[labelType], JSON.stringify({
            name: device.name,
            uid: device.uid,
            connection: device.connection,
        }));
    };

    const setSelectedPrinter = (labelType, device) => {
        if (!['serial', 'rating'].includes(labelType)) return;

        selectedPrinters[labelType] = device || null;

        if (labelType === 'serial' && serialPrinterBox) {
            serialPrinterBox.textContent = device ? getPrinterLabel(device) : 'Sin seleccionar';
        }

        if (labelType === 'rating' && ratingPrinterBox) {
            ratingPrinterBox.textContent = device ? getPrinterLabel(device) : 'Sin seleccionar';
        }

        if (device) {
            persistSelectedPrinter(labelType, device);
        }
    };

    const restorePreferredPrinter = (labelType) => {
        const raw = localStorage.getItem(storageKeys[labelType]);
        if (!raw) return;

        try {
            const parsed = JSON.parse(raw);
            return availablePrinters.find((printer) => {
                return (printer.name || '') === (parsed.name || '')
                    && (printer.uid || '') === (parsed.uid || '')
                    && (printer.connection || '') === (parsed.connection || '');
            }) || null;
        } catch (_error) {
            localStorage.removeItem(storageKeys[labelType]);
            return null;
        }
    };

    const renderPrinterOptions = (selectElement, preferredPrinter) => {
        if (!selectElement) return;

        selectElement.innerHTML = '';

        if (!availablePrinters.length) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No se detectaron impresoras';
            selectElement.appendChild(option);
            selectElement.value = '';
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecciona una impresora';
        selectElement.appendChild(placeholder);

        availablePrinters.forEach((printer) => {
            const option = document.createElement('option');
            option.value = getPrinterId(printer);
            option.textContent = getPrinterLabel(printer);
            selectElement.appendChild(option);
        });

        if (preferredPrinter) {
            selectElement.value = getPrinterId(preferredPrinter);
        } else {
            selectElement.value = '';
        }
    };

    const resolveSelectedPrinter = (labelType) => {
        if (selectedPrinters[labelType]) {
            return selectedPrinters[labelType];
        }

        if (selectedPrinters.serial && selectedPrinters.rating && getPrinterId(selectedPrinters.serial) === getPrinterId(selectedPrinters.rating)) {
            return selectedPrinters.serial;
        }

        return null;
    };

    const validatePrintersForDocuments = (documents = []) => {
        const requiredTypes = [...new Set(documents.map((doc) => String(doc.label_type || '').toLowerCase()).filter(Boolean))];

        for (const type of requiredTypes) {
            if (!['serial', 'rating'].includes(type)) {
                continue;
            }

            if (!resolveSelectedPrinter(type)) {
                throw new Error(`Debes seleccionar impresora para ${type.toUpperCase()}.`);
            }
        }
    };

    const connectPrinter = () => {
        if (!window.BrowserPrint) {
            setStatus('No se encontró BrowserPrint. Instala/abre Zebra Browser Print.', true);
            return;
        }

        setStatus('Buscando impresoras Zebra...');

        BrowserPrint.getLocalDevices((devices) => {
            availablePrinters = (devices || []).filter((candidate) => candidate.deviceType === 'printer');
            printPrepared = false;
            previewPayload = null;

            const preferredSerial = restorePreferredPrinter('serial');
            const preferredRating = restorePreferredPrinter('rating');
            renderPrinterOptions(serialPrinterSelect, preferredSerial);
            renderPrinterOptions(ratingPrinterSelect, preferredRating);

            setSelectedPrinter('serial', preferredSerial);
            setSelectedPrinter('rating', preferredRating);

            if (!availablePrinters.length) {
                setStatus('No se detectaron impresoras locales.', true);
                return;
            }

            if (availablePrinters.length === 1) {
                const onlyPrinter = availablePrinters[0];
                if (!selectedPrinters.serial) {
                    if (serialPrinterSelect) serialPrinterSelect.value = getPrinterId(onlyPrinter);
                    setSelectedPrinter('serial', onlyPrinter);
                }

                if (!selectedPrinters.rating) {
                    if (ratingPrinterSelect) ratingPrinterSelect.value = getPrinterId(onlyPrinter);
                    setSelectedPrinter('rating', onlyPrinter);
                }
            }

            const serialReady = Boolean(selectedPrinters.serial);
            const ratingReady = Boolean(selectedPrinters.rating);
            setStatus(`Se detectaron ${availablePrinters.length} impresora(s). Serial: ${serialReady ? 'OK' : 'pendiente'} · Rating: ${ratingReady ? 'OK' : 'pendiente'}.`);
        }, (error) => {
            setStatus(`Error al conectar impresora: ${error}`, true);
        }, 'printer');
    };

    const sendToPrinter = (device, zplChunk) => new Promise((resolve, reject) => {
        device.send(zplChunk, () => resolve(), (error) => reject(new Error(error)));
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
            if (!availablePrinters.length) {
                setStatus('Primero conecta impresoras.', true);
                showAlert('Impresora requerida', 'Conecta una impresora antes de preparar la impresión.', 'error');
                return;
            }

            await loadPreview();
            const documents = previewPayload?.documents || [];
            validatePrintersForDocuments(documents);

            const testDocs = documents.filter((doc) => doc?.test_zpl);
            if (!testDocs.length) {
                printPrepared = false;
                setStatus('No hay contenido de prueba para imprimir.', true);
                showAlert('Sin contenido', 'No se encontró contenido ZPL para la impresión de prueba.', 'warning');
                return;
            }

            setStatus('Enviando etiqueta(s) de prueba para validar template...');
            for (const doc of testDocs) {
                const labelType = String(doc.label_type || '').toLowerCase();
                const printer = resolveSelectedPrinter(labelType);
                if (!printer) {
                    throw new Error(`No hay impresora seleccionada para ${labelType.toUpperCase()}.`);
                }

                await sendToPrinter(printer, doc.test_zpl);
            }

            printPrepared = true;
            setStatus('Impresión de prueba enviada (1 etiqueta por tipo). Si todo está correcto, ya puedes presionar "Imprimir ahora".');
            showAlert('Preparación completada', 'Se envió 1 etiqueta de prueba por tipo (serial/rating) a la impresora seleccionada. Si están correctas, ya puedes imprimir el lote completo.', 'success');
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
            if (!availablePrinters.length) {
                setStatus('Primero conecta impresoras.', true);
                return;
            }

            if (!printPrepared) {
                showAlert('Preparación requerida', 'Debes presionar "Preparar impresión" antes de imprimir.', 'error');
                setStatus('Debes preparar la impresión primero para liberar el botón de imprimir.', true);
                return;
            }

            if (!previewPayload || !previewPayload.documents) {
                setStatus('No hay preparación activa. Presiona "Preparar impresión" nuevamente.', true);
                printPrepared = false;
                return;
            }

            const documents = (previewPayload.documents || []).filter((doc) => doc?.zpl);
            if (!documents.length) {
                setStatus('No hay contenido listo para imprimir.', true);
                return;
            }

            validatePrintersForDocuments(documents);

            setStatus(`Enviando ${documents.length} documento(s) a las impresoras seleccionadas...`);

            for (const doc of documents) {
                const labelType = String(doc.label_type || '').toLowerCase();
                const printer = resolveSelectedPrinter(labelType);
                if (!printer) {
                    throw new Error(`No hay impresora seleccionada para ${labelType.toUpperCase()}.`);
                }

                await sendToPrinter(printer, doc.zpl);
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
    serialPrinterSelect?.addEventListener('change', (event) => {
        const selectedId = event.target.value;
        const device = availablePrinters.find((printer) => getPrinterId(printer) === selectedId) || null;
        setSelectedPrinter('serial', device);
        printPrepared = false;
        if (device) {
            setStatus('Impresora SERIAL seleccionada. Prepara impresión nuevamente para validar.');
            return;
        }

        setStatus('Selecciona una impresora para SERIAL.', true);
    });
    ratingPrinterSelect?.addEventListener('change', (event) => {
        const selectedId = event.target.value;
        const device = availablePrinters.find((printer) => getPrinterId(printer) === selectedId) || null;
        setSelectedPrinter('rating', device);
        printPrepared = false;
        if (device) {
            setStatus('Impresora RATING seleccionada. Prepara impresión nuevamente para validar.');
            return;
        }

        setStatus('Selecciona una impresora para RATING.', true);
    });
    previewButton?.addEventListener('click', preparePrint);
    printButton?.addEventListener('click', printBatch);

    setSelectedPrinter('serial', null);
    setSelectedPrinter('rating', null);
})();
</script>
@endsection
