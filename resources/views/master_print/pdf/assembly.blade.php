<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Master - Ensamble</title>

    <style>
        @page { size: letter; margin: 10mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; }
        .sheet { page-break-after: always; border: 1px solid #ddd; padding: 10px; }
        .row { display: flex; gap: 12px; }
        .box { border: 1px solid #999; padding: 8px; flex: 1; }
        .h { font-weight: bold; font-size: 12px; color: #333; margin-bottom: 4px; }
        .v { font-size: 16px; font-weight: bold; }
        .small { font-size: 12px; font-weight: normal; }
        .mono { font-family: "Courier New", monospace; }
        .barcode { font-size: 22px; font-weight: bold; letter-spacing: 2px; }
        .muted { color: #666; font-size: 12px; }
    </style>
</head>
<body>

@foreach($folios as $folio)
    <div class="sheet">
        <div class="row">
            <div class="box">
                <div class="h">Líder</div>
                <div class="v">{{ $mr->leader_name }}</div>
            </div>
            <div class="box">
                <div class="h">Turno</div>
                <div class="v">{{ $mr->shift?->code }}</div>
            </div>
            <div class="box">
                <div class="h">Fecha</div>
                <div class="v small">{{ $mr->request_date?->format('Y-m-d') }}</div>
            </div>
        </div>

        <div class="row" style="margin-top:12px;">
            <div class="box">
                <div class="h">Línea</div>
                <div class="v">{{ $mr->line?->code }}</div>
                <div class="muted">{{ $mr->line?->name }}</div>
            </div>
            <div class="box">
                <div class="h">Job</div>
                <div class="v mono">{{ $mr->job_assembly ?? '-' }}</div>
                <div class="barcode mono">*{{ $mr->job_assembly ?? '' }}*</div>
            </div>
            <div class="box">
                <div class="h">Folio</div>
                <div class="v">{{ str_pad($folio->folio_number, 2, '0', STR_PAD_LEFT) }}</div>
                <div class="muted">{{ $folio->is_partial ? 'Parcial' : 'Normal' }}</div>
            </div>
        </div>

        <div class="row" style="margin-top:12px;">
            <div class="box">
                <div class="h">Destino</div>
                <div class="v">{{ $mr->destination ?? '-' }}</div>
            </div>
            <div class="box">
                <div class="h">Custom PO</div>
                <div class="v mono">{{ $mr->po_number ?? '-' }}</div>
                @if(!empty($mr->po_number))
                    <div class="barcode mono">*{{ $mr->po_number }}*</div>
                @endif
            </div>
            <div class="box">
                <div class="h">Qty pallet</div>
                <div class="v">{{ $folio->qty_for_folio ?? ($mr->std_pack_qty ?? '-') }}</div>
            </div>
        </div>

        <div class="row" style="margin-top:12px;">
            <div class="box" style="flex:2;">
                <div class="h">Lote</div>
                <div class="v mono">{{ ($mr->job_assembly ?? '') . '-' . str_pad($folio->folio_number, 2, '0', STR_PAD_LEFT) }}</div>
                <div class="barcode mono">*{{ ($mr->job_assembly ?? '') . '-' . str_pad($folio->folio_number, 2, '0', STR_PAD_LEFT) }}*</div>
            </div>
            <div class="box">
                <div class="h">Batch</div>
                <div class="v">#{{ $batch->id }}</div>
                <div class="muted">{{ $batch->batch_type }} · {{ $batch->printed_at?->format('Y-m-d H:i') }}</div>
            </div>
        </div>

        <p class="muted" style="margin-top:12px;">
            Nota: Esto es HTML imprimible. El siguiente paso es convertirlo a PDF real y usar Code39 real.
        </p>
    </div>
@endforeach

</body>
</html>
