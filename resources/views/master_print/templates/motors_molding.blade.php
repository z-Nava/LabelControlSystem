<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Master - Motores y Moldeo</title>

  <style>
    @page { size: letter landscape; margin: 8mm; }

    :root{
      --sheet-w: 258mm;
      --b: 1px;

      --gray:  #d9d9d9;
      --cream: #fff2cc;
      --peach: #fbe5d6;
    }

    html, body { margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; color:#000; }
    body { background:#f3f4f6; }
    .sheet { width: var(--sheet-w); margin: 0 auto; }
    .sheet + .sheet { page-break-before: always; }

    .page{ background:#fff; border: var(--b) solid #000; overflow:hidden; }

    table{ width:100%; border-collapse:collapse; table-layout:fixed; page-break-inside: avoid; }
    td{ border: var(--b) solid #000; padding:0; vertical-align:middle; page-break-inside: auto; }

    * { box-sizing: border-box; }
    tr { page-break-inside: avoid; }

    .center{ text-align:center; }
    .left{ text-align:left; }
    .bold{ font-weight:700; }

    .bg-gray{ background: var(--gray); }
    .bg-cream{ background: var(--cream); }
    .bg-peach{ background: var(--peach); }

    .title{ font-size: 22px; font-weight: 800; letter-spacing:.4px; }
    .logo{ font-size: 34px; font-weight: 900; color:#c00000; font-style: italic; line-height:1; padding-left:6mm; }

    .job-label{ font-size: 54px; font-weight: 800; line-height: 1; }
    .job-top{ font-size: 46px; line-height:1; }
    .job-bar{ font-size: 60px; line-height:1; }

    .np-top{ font-size: 54px; font-weight: 800; line-height: 1; }
    .np-desc{ font-size: 14px; line-height: 1.15; padding: 0 3mm; }
    .np-bar{ font-size: 64px; font-weight: 800; line-height:1; }

    .lote-top{ font-size: 56px; line-height:1; }
    .lote-bar{ font-size: 60px; line-height:1; }

    .rev-top{ font-size: 56px; line-height:1; }
    .rev-bar{ font-size: 58px; line-height:1; }

    .sub-mid{ font-size: 46px; line-height:1; }
    .sub-bar{ font-size: 60px; line-height:1; }

    .h-header{ height: 12mm; }
    .h-top1{ height: 9mm; }
    .h-top2{ height: 9mm; }
    .h-top3{ height: 20mm; }
    .h-top4{ height: 10mm; }

    .h-np{ height: 60mm; }

    .h-subh{ height: 12mm; }
    .h-sub1{ height: 17mm; }
    .h-sub2{ height: 20mm; }

    .h-foot-h{ height: 10mm; }
    .h-foot{ height: 40mm; }

    @media print{
      body{ background:#fff; }
      .sheet{ margin:0; }
    }
  </style>
</head>
<body>

@foreach($sheets as $s)
  <div class="sheet">
    <div class="page">
      <table>
        <colgroup>
          <col style="width:17.5mm"><col style="width:17.5mm"><col style="width:17.5mm"><col style="width:17.5mm"><col style="width:17.5mm">
          <col style="width:14.32mm"><col style="width:14.32mm"><col style="width:19.09mm"><col style="width:19.09mm">
          <col style="width:17.5mm"><col style="width:17.5mm"><col style="width:16.7mm"><col style="width:16.7mm"><col style="width:17.5mm"><col style="width:17.82mm">
        </colgroup>

        <tr class="h-header">
          <td colspan="4" class="left"><div class="logo">Milwaukee</div></td>
          <td colspan="11" class="center"><div class="title">PRODUCTO TERMINADO - MOTORES Y MOLDEO</div></td>
        </tr>

        <tr class="h-top1">
          <td colspan="3" class="bg-gray bold left" style="padding-left:2mm;">Líder:</td>
          <td colspan="3" class="bg-cream"></td>

          <td colspan="1" rowspan="2" class="bg-gray bold center">Turno:</td>
          <td colspan="1" rowspan="2" class="bg-cream center bold">{{ $s['shift'] ?? '' }}</td>

          <td colspan="2" rowspan="3" class="bg-gray center"><div class="job-label">Job</div></td>

          <td colspan="4" class="bg-peach center"><div class="job-top">{{ $s['job'] ?? '' }}</div></td>

          <td colspan="2" class="bg-gray bold center">Fecha:</td>
        </tr>

        <tr class="h-top2">
          <td colspan="3" class="bg-gray bold left" style="padding-left:2mm;"># empleado estacion final</td>
          <td colspan="3" class="bg-cream"></td>

          <td colspan="4" rowspan="2" class="center"><div class="job-bar">*{{ $s['job'] ?? '' }}*</div></td>

          <td colspan="2" rowspan="3" class="bg-cream center bold">{{ $s['date'] ?? '' }}</td>
        </tr>

        <tr class="h-top3">
          <td colspan="3" class="bg-gray bold center">Línea:</td>
          <td colspan="5" class="center bold">{{ $s['line'] ?? '' }}</td>
        </tr>

        <tr class="h-top4">
          <td colspan="3" class="bg-gray bold center">Modelo:</td>
          <td colspan="5" class="center bold">{{ $s['model'] ?? '' }}</td>

          <td colspan="2" class="bg-gray bold center">Folio:</td>
          <td colspan="4" class="center bold">{{ $s['folio_no'] ?? '' }}</td>

          <td colspan="2" class="bg-cream center bold">{{ $s['date'] ?? '' }}</td>
        </tr>

        <tr class="h-np">
          <td colspan="3" class="bg-gray bold center">Np Ensamble:</td>

          <td colspan="7" style="padding:0;">
            <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
              <tr style="height: 16mm;">
                <td class="center" style="border:0;"><div class="np-top">{{ $s['np'] ?? '' }}</div></td>
              </tr>
              <tr style="height: 12mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0;"><div class="np-desc">{{ $s['desc'] ?? '' }}</div></td>
              </tr>
              <tr style="height: 32mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0;"><div class="np-bar">*{{ $s['np'] ?? '' }}*</div></td>
              </tr>
            </table>
          </td>

          <td colspan="1" class="bg-gray bold center">Lote:</td>

          <td colspan="4" style="padding:0;">
            <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
              <tr style="height: 24mm;">
                <td class="center" style="border:0;"><div class="lote-top">{{ $s['lote'] ?? '' }}</div></td>
              </tr>
              <tr style="height: 16mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0;"><div class="lote-bar">*{{ $s['lote'] ?? '' }}*</div></td>
              </tr>
              <tr style="height: 10mm;">
                <td class="center bg-gray bold" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0;">Revisión:</td>
              </tr>
              <tr style="height: 10mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0;"><div class="rev-top">{{ $s['revision'] ?? '' }}</div></td>
              </tr>
              <tr style="height: 10mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0;"><div class="rev-bar">*{{ $s['revision'] ?? '' }}*</div></td>
              </tr>
            </table>
          </td>
        </tr>

        <tr class="h-subh">
          <td colspan="3" class="bg-gray bold center">Subinventory:</td>
          <td colspan="4" class="bg-gray bold center">Local:</td>
          <td colspan="4" class="bg-gray bold center">Cantidad en pallet:</td>
          <td colspan="4" class="bg-gray bold center">Observaciones:</td>
        </tr>

        <tr class="h-sub1">
          <td colspan="3" class="center"><div class="sub-mid">{{ $s['subinventory'] ?? '' }}</div></td>
          <td colspan="4" class="center"><div class="sub-mid">{{ $s['local'] ?? '' }}</div></td>
          <td colspan="4" class="center"><div class="sub-mid">{{ $s['qty_pallet'] ?? '' }}</div></td>
          <td colspan="4"></td>
        </tr>

        <tr class="h-sub2">
          <td colspan="3" class="center"><div class="sub-bar">*{{ $s['subinventory'] ?? '' }}*</div></td>
          <td colspan="4" class="center"><div class="sub-bar">*{{ $s['local'] ?? '' }}*</div></td>
          <td colspan="4" class="center"><div class="sub-bar">*{{ $s['qty_pallet'] ?? '' }}*</div></td>
          <td colspan="4"></td>
        </tr>

        <tr class="h-foot-h">
          <td colspan="4" class="bg-gray bold center">LIBERACION IPQC</td>
          <td colspan="4" class="bg-gray bold center">LIBERACION OQC</td>
          <td colspan="4" class="bg-gray bold center">PRODUCTION SUPPORT</td>
          <td colspan="3" class="bg-gray bold center">ALMACÉN:</td>
        </tr>

        <tr class="h-foot">
          <td colspan="4"></td>
          <td colspan="4"></td>
          <td colspan="4"></td>
          <td colspan="3"></td>
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
