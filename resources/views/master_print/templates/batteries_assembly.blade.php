<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Master - Ensamble Baterías</title>

  <style>
    @page { size: letter landscape; margin: 8mm; }

    :root{
      --sheet-w: 258mm;
      --b: 1px;

      --gray:  #d9d9d9;
      --cream: #fff2cc;
      --peach: #fbe5d6;
      --green: #92d050;
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
    .bg-green{ background: var(--green); }

    .title{ font-size: 22px; font-weight: 800; letter-spacing:.4px; }
    .logo-wrap{ height: 100%; padding-left: 3mm; display:flex; align-items:center; }
    .logo{ height: 15mm; width:auto; object-fit:contain; display:block; }

    .job-label{ font-size: 25px; font-weight: 800; }
    .job-top{ font-size: 26px; font-weight: 800; }
    .job-bar{ font-size: 34px; font-weight: 800; }

    .np-top{ font-size: 56px; font-weight: 800; line-height: 1; }
    .np-desc{ font-size: 14px; line-height: 1.15; padding: 0 3mm; }
    .np-bar{ font-size: 46px; font-weight: 800; }

    .lote-top{ font-size: 26px; font-weight: 800; line-height: 1; }
    .lote-bar{ font-size: 30px; font-weight: 800; }

    .sub-bar{ font-size: 34px; font-weight: 800; }

    .h-header{ height: 12mm; }
    .h-top1{ height: 14mm; }
    .h-top2{ height: 18mm; }
    .h-top3{ height: 10mm; }
    .h-np{ height: 56mm; }
    .h-subh{ height: 10mm; }
    .h-sub1{ height: 16mm; }
    .h-sub2{ height: 18mm; }
    .h-foot-h{ height: 10mm; }
    .h-foot{ height: 36mm; }

    @media print{
      body{ background:#fff; }
      .sheet{ margin:0; }
    }
  </style>
</head>
<body data-render-barcodes="1"
      data-auto-print="{{ ($mode ?? null) === 'print' ? '1' : '0' }}">

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
          <td colspan="4" class="left"><div class="logo-wrap"><img src="{{ Vite::asset('resources/img/LOGO-MILWAUKEE.png') }}" alt="Milwaukee" class="logo"></div></td>
          <td colspan="11" class="center"><div class="title">PRODUCTO TERMINADO - ENSAMBLE BATERIAS</div></td>
        </tr>

        <tr class="h-top1">
          <td colspan="2" class="bg-gray bold center">Líder:</td>
          <td colspan="3" class="bg-cream center bold">{{ $s['leader'] ?? '' }}</td>

          <td colspan="1" class="bg-gray bold center">Turno:</td>
          <td colspan="1" class="bg-cream center bold">{{ $s['shift'] ?? '' }}</td>

          <td colspan="2" rowspan="2" class="bg-gray center"><div class="job-label">Job</div></td>

          <td colspan="4" class="bg-peach center"><div class="job-top">{{ $s['job'] ?? '' }}</div></td>

          <td colspan="2" class="bg-gray bold center">Fecha:</td>
        </tr>

        <tr class="h-top2">
          <td colspan="2" class="bg-gray bold center">Línea:</td>
          <td colspan="5" class="center bold">{{ $s['line'] ?? '' }}</td>

          <td colspan="4" class="center" style="padding: 0 1.5mm;">
            <svg class="js-barcode" style="width:100%; max-height:14mm;"
                 data-format="CODE39"
                 data-height="44"
                 data-width="1.4"
                 data-value="{{ $s['job'] ?? '' }}"></svg>
          </td>

          <td colspan="2" rowspan="2" class="bg-cream center bold">{{ $s['date'] ?? '' }}</td>
        </tr>

        <tr class="h-top3">
          <td colspan="2" class="bg-gray bold center">Modelo:</td>
          <td colspan="5" class="center bold">{{ $s['model'] ?? '' }}</td>

          <td colspan="2" class="bg-gray bold center">Folio:</td>
          <td colspan="4" class="center bold">{{ $s['folio_no'] ?? '' }}</td>

          <td colspan="2" class="bg-cream center bold">{{ $s['date'] ?? '' }}</td>
        </tr>

        <tr class="h-np">
          <td colspan="2" class="bg-gray bold center">Np Ensamble:</td>

          <td colspan="8" style="padding:0;">
            <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
              <tr style="height: 16mm;">
                <td class="center bg-green" style="border:0;"><div class="np-top">{{ $s['np'] ?? '' }}</div></td>
              </tr>
              <tr style="height: 12mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0;"><div class="np-desc">{{ $s['desc'] ?? '' }}</div></td>
              </tr>
              <tr style="height: 32mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0; padding: 0 1.5mm;">
                  <svg class="js-barcode" style="width:100%; max-height:18mm;"
                       data-format="CODE39"
                       data-height="64"
                       data-width="1.6"
                       data-value="{{ $s['np'] ?? '' }}"></svg>
                </td>
              </tr>
            </table>
          </td>

          <td colspan="2" class="bg-gray bold center">Lote</td>

          <td colspan="3" style="padding:0;">
            <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
              <tr style="height: 24mm;">
                <td class="center" style="border:0;"><div class="lote-top">{{ $s['lote'] ?? '' }}</div></td>
              </tr>
              <tr style="height: 36mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0; padding: 0 1.5mm;">
                  <svg class="js-barcode" style="width:100%; max-height:18mm;"
                       data-format="CODE39"
                       data-height="56"
                       data-width="1.6"
                       data-value="{{ $s['lote'] ?? '' }}"></svg>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr class="h-subh">
          <td colspan="4" class="bg-gray bold center">Subinventory:</td>
          <td colspan="4" class="bg-gray bold center">Local:</td>
          <td colspan="3" class="bg-gray bold center">Cantidad en pallet:</td>
          <td colspan="4" class="bg-gray bold center">Observaciones:</td>
        </tr>

        <tr class="h-sub1">
          <td colspan="4" class="center">{{ $s['subinventory'] ?? '' }}</td>
          <td colspan="4" class="center">{{ $s['local'] ?? '' }}</td>
          <td colspan="3" class="center">{{ $s['qty_pallet'] ?? '' }}</td>
          <td colspan="4"></td>
        </tr>

        <tr class="h-sub2">
          <td colspan="4" class="center" style="padding: 0 1.5mm;">
            <svg class="js-barcode" style="width:100%; max-height:14mm;"
                 data-format="CODE39"
                 data-height="44"
                 data-width="1.4"
                 data-value="{{ $s['subinventory'] ?? '' }}"></svg>
          </td>
          <td colspan="4" class="center"><div class="sub-bar">*{{ $s['local'] ?? '' }}*</div></td>
          <td colspan="3" class="center" style="padding: 0 1.5mm;">
            <svg class="js-barcode" style="width:100%; max-height:14mm;"
                 data-format="CODE39"
                 data-height="44"
                 data-width="1.4"
                 data-value="{{ $s['qty_pallet'] ?? '' }}"></svg>
          </td>
          <td colspan="4"></td>
        </tr>

        <tr class="h-foot-h">
          <td colspan="5" class="bg-gray bold center">LIBERACION IPQC</td>
          <td colspan="5" class="bg-gray bold center">LIBERACION OQC</td>
          <td colspan="5" class="bg-gray bold center">PRODUCTION SUPPORT</td>
        </tr>

        <tr class="h-foot">
          <td colspan="5"></td>
          <td colspan="5"></td>
          <td colspan="5"></td>
        </tr>
      </table>
    </div>
  </div>
@endforeach
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.12.1/JsBarcode.all.min.js"></script>
@vite('resources/js/app.js')
</body>
</html>