@extends('layouts.app', ['title' => 'Seleccionar dummys para reimpresión'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6" id="dummy-reprint-page">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Reimpresión de requisición Dummy #{{ $dummyRequest->id }}</h1>
            <p class="text-slate-600 mt-1">Job {{ $dummyRequest->job_number ?? '-' }} · {{ $dummyRequest->line?->code ?? '-' }} · Turno {{ $dummyRequest->shift?->code ?? '-' }}</p>
        </div>
        <a href="{{ route('dummy_reprints.search', ['job' => $dummyRequest->job_number]) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver a reimpresiones</a>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form id="dummy-reprint-form" method="POST" action="{{ route('dummy_reprints.store', $dummyRequest) }}">
        @csrf

        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 lg:col-span-2">
                <label class="text-sm font-medium text-slate-700">Motivo de reimpresión</label>
                <textarea name="reason" id="reason" rows="2" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" placeholder="Ej. Se dañaron 4 dummys en línea">{{ old('reason') }}</textarea>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <label class="text-sm font-medium text-slate-700">Copias por dummy</label>
                <input type="number" min="1" max="10" name="copies" value="{{ old('copies', 1) }}" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" required>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <label class="text-sm font-medium text-slate-700">Impresora (Browser Print)</label>
                <input type="hidden" name="printer_name" id="printer_name" value="{{ old('printer_name') }}" required>
                <div id="selected-printer" class="mt-1 text-sm text-slate-700">Sin impresora seleccionada</div>
                <button type="button" id="connect-printer" class="mt-3 w-full rounded-lg border px-3 py-2 text-sm hover:bg-slate-100">Detectar impresora</button>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h2 class="font-semibold text-slate-900">Dummys de la requisición (selecciona 1, varios o todos)</h2>
                <div class="flex gap-2">
                    <button type="button" id="select-all" class="text-xs text-red-700 hover:underline">Seleccionar todos</button>
                    <button type="button" id="clear-all" class="text-xs text-slate-600 hover:underline">Limpiar selección</button>
                </div>
            </header>
            <div class="max-h-[28rem] overflow-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="py-2 px-3"></th>
                        <th class="py-2 px-3">Consecutivo</th>
                        <th class="py-2 px-3">Tipo</th>
                        <th class="py-2 px-3">QR payload</th>
                        <th class="py-2 px-3">Impresiones</th>
                        <th class="py-2 px-3">Última impresión</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($dummyRequest->items as $item)
                        <tr>
                            <td class="py-2 px-3"><input type="checkbox" name="selected_dummy_request_item_ids[]" value="{{ $item->id }}" class="dummy-item rounded border-slate-300" @checked(collect(old('selected_dummy_request_item_ids', []))->contains($item->id))></td>
                            <td class="py-2 px-3 font-mono">{{ $item->consecutive_10d }}</td>
                            <td class="py-2 px-3">{{ strtoupper($item->dummy_type) }}</td>
                            <td class="py-2 px-3 font-mono text-xs">{{ $item->qr_payload }}</td>
                            <td class="py-2 px-3">{{ number_format((int) $item->print_count) }}</td>
                            <td class="py-2 px-3">{{ $item->last_printed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No hay dummys registrados para esta requisición.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200" id="historial-impresiones">
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                <h2 class="font-semibold text-slate-900">Historial de impresiones / reimpresiones</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-4">Tipo</th>
                        <th class="py-3 px-4">Cantidad</th>
                        <th class="py-3 px-4">Impreso por</th>
                        <th class="py-3 px-4">Motivo</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($dummyRequest->printBatches as $batch)
                        <tr>
                            <td class="py-3 px-4">{{ $batch->printed_at?->format('Y-m-d H:i') ?? $batch->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td class="py-3 px-4">{{ strtoupper($batch->batch_type) }}</td>
                            <td class="py-3 px-4">{{ number_format((int) $batch->quantity) }}</td>
                            <td class="py-3 px-4">{{ $batch->printed_by_name ?? $batch->printedByUser?->name ?? '-' }}</td>
                            <td class="py-3 px-4">{{ $batch->reason ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No hay historial de impresión para esta requisición.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('dummy_requests.show', $dummyRequest) }}#historial-impresiones" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Ver detalle completo</a>
            <button type="button" id="submit-reprint" class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500">Reimprimir selección</button>
        </div>
    </form>
</div>

<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
<script>
(() => {
    const form = document.getElementById('dummy-reprint-form');
    if (!form) return;

    const printerNameInput = document.getElementById('printer_name');
    const selectedPrinterBox = document.getElementById('selected-printer');
    const connectPrinterButton = document.getElementById('connect-printer');
    const submitButton = document.getElementById('submit-reprint');
    const reasonInput = document.getElementById('reason');
    const selectAllButton = document.getElementById('select-all');
    const clearAllButton = document.getElementById('clear-all');

    const updatePrinter = (device) => {
        if (!device) return;
        const value = `${device.name || 'Unknown'} (${device.connection || 'connection'})`;
        printerNameInput.value = value;
        selectedPrinterBox.textContent = value;
    };

    connectPrinterButton?.addEventListener('click', () => {
        if (!window.BrowserPrint) {
            window.Swal?.fire('Browser Print no disponible', 'Instala o abre Zebra Browser Print para continuar.', 'error');
            return;
        }

        BrowserPrint.getDefaultDevice('printer', (device) => {
            if (device) {
                updatePrinter(device);
                return;
            }

            BrowserPrint.getLocalDevices((devices) => {
                const printers = (devices || []).filter((candidate) => candidate.deviceType === 'printer');
                if (!printers.length) {
                    window.Swal?.fire('Sin impresoras', 'No se detectaron impresoras locales.', 'error');
                    return;
                }

                updatePrinter(printers[0]);
            }, (error) => {
                window.Swal?.fire('Error', `No se pudo detectar impresora: ${error}`, 'error');
            }, 'printer');
        }, (error) => {
            window.Swal?.fire('Error', `No se pudo obtener impresora predeterminada: ${error}`, 'error');
        });
    });

    const allCheckboxes = () => Array.from(document.querySelectorAll('.dummy-item'));

    selectAllButton?.addEventListener('click', () => {
        allCheckboxes().forEach((checkbox) => {
            checkbox.checked = true;
        });
    });

    clearAllButton?.addEventListener('click', () => {
        allCheckboxes().forEach((checkbox) => {
            checkbox.checked = false;
        });
    });

    const showMessage = (title, text, icon = 'info') => {
        if (window.Swal?.fire) {
            window.Swal.fire(title, text, icon);
            return;
        }

        window.alert(`${title}\n\n${text}`);
    };

    submitButton?.addEventListener('click', async () => {
        const selectedCount = document.querySelectorAll('.dummy-item:checked').length;

        if (!selectedCount) {
            showMessage('Selecciona dummys', 'Debes elegir al menos 1 dummy para continuar.', 'warning');
            return;
        }

        if (!printerNameInput.value.trim()) {
            showMessage('Impresora requerida', 'Primero selecciona una impresora con Browser Print.', 'warning');
            return;
        }

        if (!reasonInput.value.trim()) {
            showMessage('Motivo requerido', 'Captura el motivo de reimpresión.', 'warning');
            return;
        }

        if (window.Swal?.fire) {
            const result = await window.Swal.fire({
                title: '¿Confirmar reimpresión?',
                html: `Dummys seleccionados: <b>${selectedCount}</b><br>Impresora: <b>${printerNameInput.value}</b>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, crear batch',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
            });

            if (!result?.isConfirmed) {
                return;
            }
        }

        form.submit();
    });
})();
</script>
@endsection
