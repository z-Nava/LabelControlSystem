<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Requisición de etiquetas #{{ $labelRequest->id }}</title>

    @vite(['resources/css/app.css', 'resources/js/pages/master-print-template.js'])

    <style>
        @page {
            size: letter landscape;
            margin: 8mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        body {
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            width: 100%;
            max-width: 263mm;
            min-height: 190mm;
            margin: 0 auto;
            border: 1.5px solid #111827;
            border-radius: 5mm;
            overflow: hidden;
        }

        .field {
            min-height: 22mm;
            border-right: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            padding: 3mm 4mm;
        }

        .field-label {
            color: #64748b;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .field-value {
            margin-top: 2mm;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.15;
        }

        .manual-line {
            height: 13mm;
            margin-top: 4mm;
            border-bottom: 1px solid #111827;
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
            .sheet {
                max-width: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body data-render-qrs="0" data-auto-print="1">
    <main class="sheet">
        <header class="grid grid-cols-12 border-b-2 border-slate-900">
            <div class="col-span-3 flex items-center justify-center border-r border-slate-300 p-4">
                <img src="{{ asset('images/LOGO-MILWAUKEE.png') }}" alt="Milwaukee" class="max-h-[18mm] w-auto object-contain">
            </div>
            <div class="col-span-6 flex items-center justify-center border-r border-slate-300 p-4 text-center">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[.22em] text-red-600">Label Control System</div>
                    <h1 class="mt-1 text-[25px] font-black uppercase tracking-wide text-slate-950">Requisición de etiquetas</h1>
                </div>
            </div>
            <div class="col-span-3 grid grid-cols-2 text-sm">
                <div class="border-b border-r border-slate-300 p-3 font-bold">Requisición</div>
                <div class="border-b border-slate-300 p-3 text-right text-lg font-black">#{{ $labelRequest->id }}</div>
                <div class="border-r border-slate-300 p-3 font-bold">Estatus</div>
                <div class="p-3 text-right font-bold">{{ $labelRequest->status_label }}</div>
            </div>
        </header>

        <section class="grid grid-cols-4">
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
            <div class="field !border-r-0"><div class="field-label">Cantidad por tipo</div><div class="field-value">{{ number_format($labelRequest->quantity_requested) }}</div></div>
        </section>

        <section class="grid grid-cols-12 border-b border-slate-300">
            <div class="col-span-5 border-r border-slate-300 p-4">
                <div class="field-label">Tipos solicitados</div>
                <div class="mt-3 flex gap-5 text-lg font-bold">
                    @foreach(['Shipping' => $labelRequest->include_shipping, 'Rating' => $labelRequest->include_rating, 'Serial' => $labelRequest->include_serial] as $type => $selected)
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center border-2 border-slate-900 text-base">{{ $selected ? '✓' : '' }}</span>
                            <span>{{ $type }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-span-3 border-r border-slate-300 p-4">
                <div class="field-label">NP de Rating</div>
                <div class="field-value">{{ $labelRequest->include_rating ? ($labelRequest->label_part_number ?: '—') : 'No requerido' }}</div>
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

        <section class="grid grid-cols-12 border-b border-slate-300">
            <div class="col-span-12 min-h-[24mm] p-4">
                <div class="field-label">Notas</div>
                <div class="mt-2 text-sm leading-relaxed">{{ $labelRequest->notes ?: 'Sin notas adicionales.' }}</div>
            </div>
        </section>

        <footer class="grid grid-cols-3">
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
