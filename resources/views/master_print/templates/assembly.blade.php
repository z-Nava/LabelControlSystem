<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Master - Ensamble</title>

    <style>
        /* ====== PAGE ====== */
        @page { size: letter landscape; margin: 10mm; }
        html, body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; color:#000; }

        /* En navegador se ve centrado; en DomPDF ignora algunas cosas pero se mantiene bien */
        body { background: #f3f4f6; }
        .page { background:#fff; border: 2px solid #000; border-radius: 16px; overflow: hidden; }

        /* Cada folio = 1 hoja */
        .sheet { width: 100%; }
        .sheet + .sheet { page-break-before: always; margin-top: 0; }

        /* ====== TABLE GRID ====== */
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td { border: 1px solid #000; padding: 6px 8px; vertical-align: middle; }

        /* Colores */
        .bg-gray  { background:#d9d9d9; }
        .bg-cream { background:#fff2cc; }

        /* Texto */
        .center { text-align: center; }
        .right  { text-align: right; }
        .bold   { font-weight: 700; }

        .title { font-size: 18px; letter-spacing: .5px; font-weight: 800; }
        .logo  { font-size: 26px; font-weight: 900; color:#c00000; font-style: italic; line-height: 1; }

        .big-number { font-size: 26px; font-weight: 800; }
        .mid-number { font-size: 18px; font-weight: 800; }
        .small { font-size: 12px; }
        .xs    { font-size: 11px; }

        /* “Barcode” estilo Excel (por ahora texto con asteriscos)
           Si luego metes una fuente Code39, aquí la aplicamos. */
        .barcode {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: .5px;
            /* font-family: 'Free3of9', 'Libre Barcode 39', Arial, sans-serif; */
        }
        .barcode-mid {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: .5px;
        }

        /* Alturas (ajustadas a tu screenshot) */
        .h-42 { height: 42px; }
        .h-38 { height: 38px; }
        .h-50 { height: 50px; }
        .h-120 { height: 120px; }
        .h-95 { height: 95px; }
        .h-70 { height: 70px; }

        /* Para que los bloques de abajo se vean como Excel */
        .no-pad { padding: 0; }
        .cell-pad { padding: 6px 8px; }

        /* Print helper (solo navegador) */
        @media print {
            body { background:#fff; }
            .page { border: 2px solid #000; border-radius: 0; }
        }
    </style>
</head>

<body>

@foreach($folios as $folio)
    @php
        // NOTA: si no quieres @php en la vista, dímelo y lo movemos al Service/ViewModel.
        // Aquí lo dejo mínimo (solo para variables derivadas del folio).
        $folioNo = str_pad((string)$folio->folio_number, 2, '0', STR_PAD_LEFT);

        $job = (string)($mr->job_assembly ?? '');
        $np  = (string)optional($oracle)->assembly;
        $desc = (string)optional($oracle)->part_description;

        // Lote = JOB-XX
        $lote = $job !== '' ? ($job . '-' . $folioNo) : ('-' . $folioNo);

        // Campos “constantes” como en el formato actual (luego los hacemos configurables si quieres)
        $subinventory = 'WIP';
        $local = 'SMARKET-1';
        $qtyPallet = (string)($mr->std_pack_qty ?? '');
    @endphp

    <div class="sheet">
        <div class="page">
            <table>
                <!-- HEADER -->
                <tr class="h-42">
                    <td style="width:18%;" class="center no-pad">
                        <div class="cell-pad">
                            <div class="logo">Milwaukee</div>
                        </div>
                    </td>
                    <td style="width:82%;" colspan="9" class="center bold title">
                        PRODUCTO TERMINADO - ENSAMBLE
                    </td>
                </tr>

                <!-- ROW: Líder / Turno / Job / Fecha -->
                <tr class="h-38">
                    <td class="bg-gray bold" style="width:12%;">Líder:</td>
                    <td class="bg-cream" style="width:26%;">{{ $mr->leader_name }}</td>

                    <td class="bg-gray bold" style="width:10%;">Turno:</td>
                    <td class="bg-cream" style="width:10%;">{{ optional($mr->shift)->code ?? optional($mr->shift)->name }}</td>

                    <td class="bg-gray bold center" style="width:8%;">Job</td>
                    <td class="bg-cream center" style="width:18%;">
                        <div class="barcode-mid">*</div>
                        <div class="big-number">{{ $mr->job_assembly }}</div>
                        <div class="barcode-mid">*{{ $mr->job_assembly }}*</div>
                    </td>

                    <td class="bg-gray bold" style="width:8%;">Fecha:</td>
                    <td class="bg-cream" style="width:8%;">{{ optional($mr->request_date)->format('d/m/Y') }}</td>

                    <td style="width:0%;" class="no-pad" colspan="2"></td>
                </tr>

                <!-- ROW: Línea / Modelo / Folio -->
                <tr class="h-38">
                    <td class="bg-gray bold">Línea:</td>
                    <td class="center bold">{{ optional($mr->line)->code ?? '' }}</td>

                    <td class="bg-gray bold">Modelo:</td>
                    <td class="center bold">{{ $mr->job_description ?? '' }}</td>

                    <td class="bg-gray bold">Folio:</td>
                    <td class="center">{{ $folioNo }}</td>

                    <td colspan="4" class="bg-cream"></td>
                </tr>

                <!-- BLOQUE NP ENSAMBLE / LOTE -->
                <tr class="h-120">
                    <td class="bg-gray bold" style="width:12%;">Np Ensamble:</td>

                    <td colspan="5" class="center">
                        <div class="mid-number">{{ $np }}</div>
                        <div class="xs" style="margin-top:4px;">{{ $desc }}</div>
                        <div class="barcode" style="margin-top:8px;">*{{ $np }}*</div>
                    </td>

                    <td class="bg-gray bold center" style="width:8%;">Lote</td>
                    <td colspan="3" class="center">
                        <div class="mid-number">{{ $lote }}</div>
                        <div class="barcode-mid" style="margin-top:10px;">*{{ $lote }}*</div>
                    </td>
                </tr>

                <!-- SUBINVENTORY / LOCAL / CANTIDAD / OBS -->
                <tr class="h-38">
                    <td colspan="2" class="bg-gray bold center">Subinventory:</td>
                    <td colspan="2" class="bg-gray bold center">Local:</td>
                    <td colspan="2" class="bg-gray bold center">Cantidad en pallet:</td>
                    <td colspan="4" class="bg-gray bold center">Observaciones:</td>
                </tr>

                <tr class="h-70">
                    <td colspan="2" class="center">
                        <div class="small">{{ $subinventory }}</div>
                        <div class="barcode-mid" style="margin-top:6px;">*{{ $subinventory }}*</div>
                    </td>

                    <td colspan="2" class="center">
                        <div class="small">{{ $local }}</div>
                        <div class="barcode-mid" style="margin-top:6px;">*{{ $local }}*</div>
                    </td>

                    <td colspan="2" class="center">
                        <div class="small">{{ $qtyPallet }}</div>
                        <div class="barcode-mid" style="margin-top:6px;">*{{ $qtyPallet }}*</div>
                    </td>

                    <td colspan="4"></td>
                </tr>

                <!-- FIRMAS -->
                <tr class="h-50">
                    <td colspan="3" class="bg-gray bold center">LIBERACION IPQC</td>
                    <td colspan="3" class="bg-gray bold center">LIBERACION OQC</td>
                    <td colspan="4" class="bg-gray bold center">PRODUCTION SUPPORT</td>
                </tr>

                <tr class="h-95">
                    <td colspan="3"></td>
                    <td colspan="3"></td>
                    <td colspan="4"></td>
                </tr>
            </table>
        </div>
    </div>
@endforeach

@if(($mode ?? null) === 'print')
    <script>
        window.addEventListener('load', () => window.print());
    </script>
@endif

</body>
</html>
