@extends('layouts.app', ['title' => 'Nuevo print profile'])
@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-semibold">Nuevo print profile</h1>
    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if(session()->has('printer_detected'))
        <div class="mt-4 rounded-xl border p-3 text-sm {{ session('printer_detected') ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
            Estado de impresora:
            <span class="font-semibold">{{ session('printer_detected') ? 'Detectada (puerto 9100 abierto)' : 'No detectada' }}</span>
        </div>
    @endif
    <form class="mt-6 space-y-4" method="POST" action="{{ route('label_print_profiles.store') }}">
        @include('label_print_profiles._form')

        <div class="rounded-xl border border-slate-200 p-4 space-y-3">
            <h2 class="text-lg font-semibold text-slate-900">Prueba de impresión Zebra</h2>
            <p class="text-sm text-slate-600">Conecta una impresora Zebra por USB usando BrowserPrint y envía una impresión de prueba desde esta pantalla.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">ZPL de prueba (preview)</label>
                    <pre id="zebra-zpl-preview" class="mt-1 min-h-10 max-h-36 overflow-auto rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-800">Selecciona un template para previsualizar el ZPL.</pre>
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
    const skuSelect = document.getElementById('label-print-profile-sku');
    const templateSelect = document.getElementById('label-print-profile-template');
    const printerNameInput = document.getElementById('label-print-profile-printer-name');
    const printerNameBox = document.getElementById('zebra-printer-name');
    const statusBox = document.getElementById('zebra-print-status');
    const zplPreview = document.getElementById('zebra-zpl-preview');
    const connectButton = document.getElementById('connect-zebra-printer');
    const printButton = document.getElementById('print-zebra-test');

    let selectedPrinter = null;

    const setStatus = (message, isError = false) => {
        statusBox.textContent = message;
        statusBox.className = isError ? 'text-sm text-red-600' : 'text-sm text-slate-600';
    };

    const getTemplateOption = () => templateSelect?.options[templateSelect.selectedIndex] ?? null;
    const getSkuOption = () => skuSelect?.options[skuSelect.selectedIndex] ?? null;

    const decodeBase64 = (value) => {
        if (!value) {
            return '';
        }

        try {
            return atob(value);
        } catch (error) {
            return '';
        }
    };

    const renderTestZpl = () => {
        const templateOption = getTemplateOption();
        const skuOption = getSkuOption();
        const zplBase = decodeBase64(templateOption?.dataset.zplBase64 ?? '');
        const sku = skuOption?.dataset.sku ?? '';
        const serialFull = `TEST-${sku || 'SKU'}-000001`;

        if (!zplBase) {
            return '';
        }

        return zplBase
            .replaceAll('@{{serial_full}}', serialFull)
            .replaceAll('@{{ serial_full }}', serialFull)
            .replaceAll('@{{sku}}', sku)
            .replaceAll('@{{ label_sku }}', sku)
            .replaceAll('@{{label_sku}}', sku);
    };

    const refreshZplPreview = () => {
        const testZpl = renderTestZpl();
        zplPreview.textContent = testZpl || 'Selecciona un template para previsualizar el ZPL.';
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

            if (printerNameInput) {
                printerNameInput.value = device.name || '';
            }

            setStatus('Impresora conectada correctamente por BrowserPrint.');
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
            setStatus('Selecciona un template para generar el ZPL de prueba.', true);
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
    templateSelect?.addEventListener('change', refreshZplPreview);
    skuSelect?.addEventListener('change', refreshZplPreview);

    refreshZplPreview();
})();
</script>
@endsection
