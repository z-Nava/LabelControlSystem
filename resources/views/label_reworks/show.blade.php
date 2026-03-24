@extends('layouts.app', ['title' => 'Seleccionar retrabajo de etiquetas'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6" id="label-rework-page">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Retrabajo / Reimpresión de requisición #{{ $labelRequest->id }}</h1>
            <p class="text-slate-600 mt-1">Job {{ $labelRequest->job_number ?? '-' }} · {{ $labelRequest->line?->code ?? '-' }} · Turno {{ $labelRequest->shift?->code ?? '-' }}</p>
        </div>
        <a href="{{ route('label_reworks.search', ['job' => $labelRequest->job_number]) }}" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Volver a retrabajo</a>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form id="rework-form" method="POST" action="{{ route('label_reworks.store', $labelRequest) }}">
        @csrf

        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 lg:col-span-2">
                <label class="text-sm font-medium text-slate-700">Motivo de reimpresión / retrabajo</label>
                <textarea name="reason" id="reason" rows="2" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600" placeholder="Ej. Etiquetas dañadas en línea MXB001">{{ old('reason') }}</textarea>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <label class="text-sm font-medium text-slate-700">Impresora (Browser Print)</label>
                <input type="hidden" name="printer_name" id="printer_name" value="{{ old('printer_name') }}" required>
                <div id="selected-printer" class="mt-1 text-sm text-slate-700">Sin impresora seleccionada</div>
                <button type="button" id="connect-printer" class="mt-3 w-full rounded-lg border px-3 py-2 text-sm hover:bg-slate-100">Detectar impresora</button>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="rounded-xl border border-slate-200">
                <header class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h2 class="font-semibold text-slate-900">Seriales disponibles</h2>
                    <button type="button" data-select-all="serial" class="text-xs text-red-700 hover:underline">Seleccionar todos</button>
                </header>
                <div class="max-h-80 overflow-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b">
                                <th class="py-2 px-3"></th>
                                <th class="py-2 px-3">#</th>
                                <th class="py-2 px-3">Serial completo</th>
                                <th class="py-2 px-3">Estatus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($availableUnits['serial'] as $unit)
                                <tr>
                                    <td class="py-2 px-3"><input type="checkbox" name="selected_serial_unit_ids[]" value="{{ $unit->id }}" class="serial-item rounded border-slate-300" @checked(collect(old('selected_serial_unit_ids', []))->contains($unit->id))></td>
                                    <td class="py-2 px-3 font-mono">{{ $unit->serial_number }}</td>
                                    <td class="py-2 px-3 font-mono">{{ $unit->serial_full }}</td>
                                    <td class="py-2 px-3">{{ $unit->status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No hay seriales disponibles para esta requisición.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200">
                <header class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h2 class="font-semibold text-slate-900">Ratings disponibles</h2>
                    <button type="button" data-select-all="rating" class="text-xs text-red-700 hover:underline">Seleccionar todos</button>
                </header>
                <div class="max-h-80 overflow-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b">
                                <th class="py-2 px-3"></th>
                                <th class="py-2 px-3">#</th>
                                <th class="py-2 px-3">Serial completo</th>
                                <th class="py-2 px-3">Estatus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($availableUnits['rating'] as $unit)
                                <tr>
                                    <td class="py-2 px-3"><input type="checkbox" name="selected_rating_unit_ids[]" value="{{ $unit->id }}" class="rating-item rounded border-slate-300" @checked(collect(old('selected_rating_unit_ids', []))->contains($unit->id))></td>
                                    <td class="py-2 px-3 font-mono">{{ $unit->serial_number }}</td>
                                    <td class="py-2 px-3 font-mono">{{ $unit->serial_full }}</td>
                                    <td class="py-2 px-3">{{ $unit->status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No hay ratings disponibles para esta requisición.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('label_requests.show', $labelRequest) }}#historial-impresiones" class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">Ver historial de impresiones</a>
            <button type="button" id="submit-reprint" class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500">Reimprimir seleccionados</button>
        </div>
    </form>
</div>

<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
<script>
(() => {
    const form = document.getElementById('rework-form');
    if (!form) return;

    const printerNameInput = document.getElementById('printer_name');
    const selectedPrinterBox = document.getElementById('selected-printer');
    const connectPrinterButton = document.getElementById('connect-printer');
    const submitButton = document.getElementById('submit-reprint');
    const reasonInput = document.getElementById('reason');

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

    document.querySelectorAll('[data-select-all]').forEach((button) => {
        button.addEventListener('click', () => {
            const type = button.dataset.selectAll;
            const selector = type === 'serial' ? '.serial-item' : '.rating-item';
            document.querySelectorAll(selector).forEach((checkbox) => {
                checkbox.checked = true;
            });
        });
    });

    submitButton?.addEventListener('click', async () => {
        const serialCount = document.querySelectorAll('.serial-item:checked').length;
        const ratingCount = document.querySelectorAll('.rating-item:checked').length;

        if (!serialCount && !ratingCount) {
            window.Swal?.fire('Selecciona elementos', 'Debes elegir al menos un serial o rating para continuar.', 'warning');
            return;
        }

        if (!printerNameInput.value.trim()) {
            window.Swal?.fire('Impresora requerida', 'Primero selecciona una impresora con Browser Print.', 'warning');
            return;
        }

        if (!reasonInput.value.trim()) {
            window.Swal?.fire('Motivo requerido', 'Captura el motivo de reimpresión/retrabajo.', 'warning');
            return;
        }

        const result = await window.Swal?.fire({
            title: '¿Confirmar reimpresión / retrabajo?',
            html: `Seriales: <b>${serialCount}</b><br>Ratings: <b>${ratingCount}</b><br>Impresora: <b>${printerNameInput.value}</b><br><br>Motivo:<br><span class="text-sm">${reasonInput.value}</span>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, crear batch',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        });

        if (result?.isConfirmed) {
            form.submit();
        }
    });
})();
</script>
@endsection
