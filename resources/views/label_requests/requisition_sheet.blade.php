<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Requisición de etiquetas #{{ $labelRequest->id }}</title>

    @vite(['resources/css/app.css', 'resources/js/pages/master-print-template.js'])

    <style>
        @page {
            size: letter landscape;
            margin: 5mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        body {
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12.5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 269mm;
            min-height: 190mm;
            margin: 0 auto;
            border: 1.5px solid #111827;
            border-radius: 3mm;
            overflow: hidden;
        }

        .sheet-header > div {
            min-height: 19mm;
            padding: 2mm 3mm !important;
        }

        .sheet-header img {
            max-height: 14mm !important;
        }

        .sheet-header h1 {
            margin-top: 0 !important;
            font-size: 22px !important;
            line-height: 1;
        }

        .header-meta > div {
            padding: 1.3mm 2.5mm !important;
            font-size: 11px;
        }

        .general-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .field {
            min-height: 16mm;
            border-right: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            padding: 1.8mm 2.7mm;
            overflow-wrap: anywhere;
        }

        .general-grid .field {
            border-right: 1px solid #cbd5e1 !important;
        }

        .general-grid .field:nth-child(6n) {
            border-right: 0 !important;
        }

        .field-label {
            color: #64748b;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .field-value {
            margin-top: 1mm;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.1;
        }

        .request-summary > div {
            min-height: 22mm;
            padding: 2mm 3mm !important;
        }

        .type-grid {
            margin-top: 2mm !important;
            gap: 1.5mm 3.5mm !important;
            font-size: 12px !important;
        }

        .type-box {
            width: 6mm !important;
            height: 6mm !important;
            font-size: 12px !important;
        }

        .manual-line {
            height: 8mm;
            margin-top: 1.5mm;
            border-bottom: 1px solid #111827;
        }

        .detail-title {
            padding: 1.5mm 3mm !important;
        }

        .detail-section {
            flex: 1 1 auto;
        }

        .detail-table {
            table-layout: fixed;
            font-size: 12px !important;
            line-height: 1.1;
        }

        .detail-table th,
        .detail-table td {
            padding: 1.6mm 3mm !important;
            overflow-wrap: anywhere;
        }

        .notes-section {
            min-height: 16mm;
            padding: 2mm 3mm !important;
        }

        .notes-content {
            margin-top: 1.5mm !important;
            font-size: 11px !important;
            line-height: 1.2 !important;
        }

        .signature-footer > div {
            min-height: 16mm;
            padding: 1.8mm 3mm !important;
        }

        @media screen {
            body {
                background: #e2e8f0;
                padding: 12px;
            }

            .sheet {
                background: #fff;
                box-shadow: 0 12px 30px rgba(15, 23, 42, .12);
            }
        }

        @media print {
            html,
            body {
                width: 100%;
                height: 100%;
                overflow: hidden;
            }

            .sheet {
                max-width: none;
                height: 205mm;
                box-shadow: none;
                break-inside: avoid;
                page-break-inside: avoid;
                border-radius: 0;
            }
        }
    </style>
</head>
<body data-render-qrs="0" data-auto-print="1">
    <main class="sheet">
        <header class="sheet-header grid grid-cols-12 border-b-2 border-slate-900">
            <div class="col-span-3 flex items-center justify-center border-r border-slate-300 p-4">
                <img src="{{ asset('images/LOGO-MILWAUKEE.png') }}" alt="Milwaukee" class="max-h-[18mm] w-auto object-contain">
            </div>
            <div class="col-span-6 flex items-center justify-center border-r border-slate-300 p-4 text-center">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[.22em] text-red-600">Label Control System</div>
                    <h1 class="mt-1 text-[25px] font-black uppercase tracking-wide text-slate-950">Requisición de etiquetas</h1>
                </div>
            </div>
            <div class="header-meta col-span-3 grid grid-cols-2 text-sm">
                <div class="border-b border-r border-slate-300 p-3 font-bold">Requisición</div>
                <div class="border-b border-slate-300 p-3 text-right text-lg font-black">#{{ $labelRequest->id }}</div>
                <div class="border-r border-slate-300 p-3 font-bold">Estatus</div>
                <div class="p-3 text-right font-bold">{{ $labelRequest->status_label }}</div>
            </div>
        </header>

        <section class="general-grid">
            <div class="field"><div class="field-label">Fecha</div><div class="field-value">{{ $labelRequest->request_date?->format('d/m/Y') }}</div></div>
            <div class="field"><div class="field-label">Semana</div><div class="field-value">{{ $labelRequest->week }}</div></div>
            <div class="field"><div class="field-label">Línea</div><div class="field-value">{{ $labelRequest->line?->code }} · {{ $labelRequest->line?->name }}</div></div>
            <div class="field !border-r-0"><div class="field-label">Turno de producción</div><div class="field-value">{{ $labelRequest->shift?->code }} · {{ $labelRequest->shift?->name }}</div></div>

            <div class="field"><div class="field-label">Solicita</div><div class="field-value">{{ $labelRequest->requested_by_name }}</div></div>
            <div class="field"><div class="field-label">Líder</div><div class="field-value">{{ $labelRequest->leader_name }}</div></div>
            <div class="field"><div class="field-label">Job</div><div class="field-value">{{ $labelRequest->job_number ?: '—' }}</div></div>
            <div class="field !border-r-0"><div class="field-label">Assembly</div><div class="field-value">{{ $labelRequest->oracleJob?->assembly ?: '—' }}</div></div>

            <div class="field"><div class="field-label">Modelo</div><div class="field-value">{{ $labelRequest->model ?: '—' }}</div></div>
            <div class="field"><div class="field-label">PO</div><div class="field-value">{{ $labelRequest->po_number ?: '—' }}</div></div>
            <div class="field"><div class="field-label">Destino</div><div class="field-value">{{ $labelRequest->destination ?: '—' }}</div></div>
            <div class="field !border-r-0">
                <div class="field-label">Cantidades</div>
                <div class="field-value">General: {{ number_format($labelRequest->quantity_requested) }}</div>
                <div class="mt-1 text-sm font-semibold">Shipping: {{ $labelRequest->include_shipping ? number_format($labelRequest->shipping_quantity ?? $labelRequest->quantity_requested) : 'No requerida' }}</div>
            </div>
        </section>

        <section class="request-summary grid grid-cols-12 border-b border-slate-300">
            <div class="col-span-4 border-r border-slate-300 p-4">
                <div class="field-label">Tipos solicitados</div>
                <div class="type-grid mt-3 grid grid-cols-2 gap-3 text-base font-bold">
                    @foreach(['Serial' => $labelRequest->include_serial, 'Rating' => $labelRequest->include_rating, 'Inner' => $labelRequest->include_inner, 'Shipping' => $labelRequest->include_shipping] as $type => $selected)
                        <div class="flex items-center gap-2">
                            <span class="type-box inline-flex h-7 w-7 items-center justify-center border-2 border-slate-900 text-base">{{ $selected ? '✓' : '' }}</span>
                            <span>{{ $type }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-span-4 border-r border-slate-300 p-4">
                <div class="field-label">NP de Serial</div>
                <div class="field-value">{{ $labelRequest->serial_part_number ?: ($labelRequest->label_part_number ?: '—') }}</div>
            </div>
            <div class="col-span-2 border-r border-slate-300 p-4">
                <div class="field-label">Folio inicial</div>
                <div class="manual-line"></div>
            </div>
            <div class="col-span-2 p-4">
                <div class="field-label">Folio final</div>
                <div class="manual-line"></div>
            </div>
        </section>

        <section class="detail-section border-b border-slate-300">
            <div class="detail-title border-b border-slate-300 bg-slate-50 px-4 py-2 field-label">Detalle solicitado</div>
            <table class="detail-table w-full border-collapse text-sm">
                <colgroup>
                    <col style="width: 24%">
                    <col style="width: 54%">
                    <col style="width: 22%">
                </colgroup>
                <thead>
                    <tr class="border-b border-slate-300 text-left">
                        <th class="border-r border-slate-300 px-4 py-2">Tipo</th>
                        <th class="border-r border-slate-300 px-4 py-2">Número de parte</th>
                        <th class="px-4 py-2 text-right">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($labelRequest->requestedLabelLines() as $line)
                        <tr class="border-b border-slate-200 last:border-b-0">
                            <td class="border-r border-slate-300 px-4 py-2">{{ $line['type'] }}</td>
                            <td class="border-r border-slate-300 px-4 py-2 font-mono">{{ $line['part_number'] ?: 'No aplica' }}</td>
                            <td class="px-4 py-2 text-right font-bold">{{ number_format($line['quantity']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="grid grid-cols-12 border-b border-slate-300">
            <div class="notes-section col-span-12 p-4">
                <div class="field-label">Notas</div>
                <div class="notes-content mt-2 text-sm leading-relaxed">{{ $labelRequest->notes ?: 'Sin notas adicionales.' }}</div>
            </div>
        </section>

        <footer class="signature-footer grid grid-cols-3">
            <div class="border-r border-slate-300 p-4">
                <div class="field-label text-center">Imprimió</div>
                <div class="manual-line"></div>
            </div>
            <div class="border-r border-slate-300 p-4">
                <div class="field-label text-center">Recibió</div>
                <div class="manual-line"></div>
            </div>
            <div class="p-4">
                <div class="field-label text-center">Turno LabelRoom</div>
                <div class="manual-line"></div>
            </div>
        </footer>
    </main>
</body>
</html>
