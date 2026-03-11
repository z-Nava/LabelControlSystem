@extends('layouts.app', ['title' => 'Nuevo template ZPL'])
@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-semibold">Nuevo template ZPL</h1>
    <form class="mt-6 space-y-4" method="POST" action="{{ route('label_templates.store') }}">
        @include('label_templates._form')

        <div class="rounded-xl border border-slate-200 p-4 space-y-3">
            <h2 class="text-lg font-semibold text-slate-900">Prueba de impresión Zebra</h2>
            <p class="text-sm text-slate-600">Selecciona un SKU para ver su serial format full y prueba imprimir el ZPL desde esta misma pantalla.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Serial format full (preview)</label>
                    <div id="serial-format-preview" class="mt-1 min-h-10 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-mono text-slate-800">Selecciona un SKU para ver el formato.</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Impresora Zebra detectada</label>
                    <div id="zebra-printer-name" class="mt-1 min-h-10 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-800">Sin conectar.</div>
                </div>
            </div>

            <div id="zebra-print-status" class="text-sm text-slate-600"></div>

            <div class="flex flex-wrap gap-2">
                <button id="connect-zebra-printer" type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Conectar impresora</button>
                <button id="print-zebra-test" type="button" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Imprimir prueba</button>
            </div>
        </div>

        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500">Guardar</button>
    </form>
</div>

<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
<script>
(function () {
    const skuSelect = document.getElementById('label-template-sku');
    const previewBox = document.getElementById('serial-format-preview');
    const printerNameBox = document.getElementById('zebra-printer-name');
    const statusBox = document.getElementById('zebra-print-status');
    const connectButton = document.getElementById('connect-zebra-printer');
    const printButton = document.getElementById('print-zebra-test');
    const zplInput = document.getElementById('label-template-zpl');

    let selectedPrinter = null;

    const setStatus = (message, isError = false) => {
        statusBox.textContent = message;
        statusBox.className = isError ? 'text-sm text-red-600' : 'text-sm text-slate-600';
    };

    const selectedOption = () => skuSelect?.options[skuSelect.selectedIndex] ?? null;

    const refreshSerialPreview = () => {
        const option = selectedOption();
        const preview = option?.dataset.serialPreview ?? '';
        previewBox.textContent = preview || 'Sin formato configurado para este SKU.';
    };

    const renderTestZpl = () => {
        const option = selectedOption();
        const serialFull = option?.dataset.serialPreview ?? '';
        const sku = option?.dataset.sku ?? '';
        const zpl = (zplInput?.value ?? '').trim();

        if (!zpl) {
            return '';
        }

        return zpl
            .replaceAll('@{{serial_full}}', serialFull)
            .replaceAll('@{{sku}}', sku)
            .replaceAll('@{{ label_sku }}', sku)
            .replaceAll('@{{ serial_full }}', serialFull);
    };

    const connectPrinter = () => {
        if (!window.BrowserPrint) {
            setStatus('No se encontró BrowserPrint. Verifica que Browser Print esté instalado y ejecutándose.', true);
            return;
        }

        setStatus('Buscando impresora Zebra...');

        BrowserPrint.getDefaultDevice('printer', function (device) {
            if (!device) {
                setStatus('No se detectó impresora por default. Revisa Browser Print.', true);
                return;
            }

            selectedPrinter = device;
            printerNameBox.textContent = `${device.name} (${device.connection || 'connection'})`;
            setStatus('Impresora conectada correctamente.');
        }, function (error) {
            setStatus(`Error al conectar impresora: ${error}`, true);
        });
    };

    const printTest = () => {
        if (!selectedPrinter) {
            setStatus('Primero conecta una impresora.', true);
            return;
        }

        const testZpl = renderTestZpl();
        if (!testZpl) {
            setStatus('Escribe un ZPL para imprimir la prueba.', true);
            return;
        }

        selectedPrinter.send(testZpl, function () {
            setStatus('Prueba enviada a impresora correctamente.');
        }, function (error) {
            setStatus(`Error al imprimir: ${error}`, true);
        });
    };

    connectButton?.addEventListener('click', connectPrinter);
    printButton?.addEventListener('click', printTest);
    skuSelect?.addEventListener('change', refreshSerialPreview);

    refreshSerialPreview();
})();
</script>
@endsection
