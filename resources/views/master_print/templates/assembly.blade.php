<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Master - Ensamble</title>

  <style>
    @page { size: letter landscape; margin: 8mm; }

    :root{
      --sheet-w: 258mm; /* 279.4 - 20mm márgenes */
      --b: 1px;

      --gray:  #d9d9d9;
      --cream: #fff2cc;
      --peach: #fbe5d6;
    }

    html, body { margin:0; padding:0; font-family: Arial, Helvetica, sans-serif; color:#000; }
    body { background:#f3f4f6; }
    .no-wrap{ white-space:nowrap; }
    .sheet { width: var(--sheet-w); margin: 0 auto; }
    .sheet + .sheet { page-break-before: always; }

    .page{
      background:#fff;
      border: var(--b) solid #000;
      overflow:hidden;
    }

    table{ width:100%; border-collapse:collapse; table-layout:fixed; page-break-inside: avoid; }
    td{ border: var(--b) solid #000; padding:0; vertical-align:middle; page-break-inside: auto;}

    * { box-sizing: border-box; }
    tr { page-break-inside: avoid; }

    .center{ text-align:center; }
    .left{ text-align:left; }
    .bold{ font-weight:700; }

    .bg-gray{ background: var(--gray); }
    .bg-cream{ background: var(--cream); }
    .bg-peach{ background: var(--peach); }

    /* Header */
    .title{ font-size: 22px; font-weight: 800; letter-spacing:.4px; }
    .logo{ font-size: 34px; font-weight: 900; color:#c00000; font-style: italic; line-height:1; padding-left:6mm; }

    /* Fonts */
    .job-label{ font-size: 25px; font-weight: 800; }
    .job-top{ font-size: 26px; font-weight: 800; }
    .job-bar{ font-size: 34px; font-weight: 800; }

    .np-top{ font-size: 34px; font-weight: 800; }
    .np-desc{ font-size: 11px; }
    .np-bar{ font-size: 44px; font-weight: 800; }

    .lote-top{ font-size: 30px; font-weight: 800; }
    .lote-bar{ font-size: 32px; font-weight: 800; }

    .sub-mid{ font-size: 18px; font-weight: 800; }
    .sub-bar{ font-size: 34px; font-weight: 800; }

    /* Heights (ajustables en mm) */
    .h-header{ height: 12mm; }
    .h-top1{ height: 14mm; }
    .h-top2{ height: 14mm; }
    .h-top3{ height: 10mm; }

    .h-np{ height: 55mm; }

    .h-subh{ height: 10mm; }
    .h-sub1{ height: 16mm; }
    .h-sub2{ height: 18mm; }

    .h-foot-h{ height: 10mm; }
    .h-foot{ height: 36mm; }

    @media print{
      body{ background:#fff; }
      .sheet{ margin:0; }
    }

    .logo, .title, .job-label, .job-top, .job-bar,
    .np-top, .np-bar, .lote-top, .lote-bar,
    .sub-bar { line-height: 1; }

    /* ====== CAMBIOS DEL DIFF ====== */
    .page{ border-radius: 2.5mm; box-shadow: 0 2mm 6mm rgba(0,0,0,.08); }
    td{ padding: 1.2mm; }
    .bg-gray{ background: linear-gradient(180deg, #ececec 0%, #d9d9d9 100%); }
    .bg-cream{ background: #fff8d9; }
    .bg-peach{ background: #fde8dc; }
    .title{ letter-spacing: .8px; }

    .barcode{ width: 100%; max-height: 14mm; display:block; margin: 0 auto; }
    .barcode--sm{ max-height: 10mm; }
    .barcode--lg{ max-height: 18mm; }
    .barcode-empty{ min-height: 6mm; display:flex; align-items:center; justify-content:center; color:#666; font-size:10px; }

    @media print{
      .page{ box-shadow:none; border-radius: 0; }
    }
  </style>
</head>
<body>

@foreach($sheets as $s)
  <div class="sheet">
    <div class="page">
      <table>
        <!-- GRID FIJO: 15 columnas (suman 259.4mm) -->
        <colgroup>
        <col style="width:17.5mm"><!-- 1 -->
        <col style="width:17.5mm"><!-- 2 -->
        <col style="width:17.5mm"><!-- 3 -->
        <col style="width:17.5mm"><!-- 4 -->
        <col style="width:17.5mm"><!-- 5 -->
        <col style="width:14.32mm"><!-- 6 -->
        <col style="width:14.32mm"><!-- 7 -->
        <col style="width:19.09mm"><!-- 8 -->
        <col style="width:19.09mm"><!-- 9 -->
        <col style="width:17.5mm"><!-- 10 -->
        <col style="width:17.5mm"><!-- 11 -->
        <col style="width:16.7mm"><!-- 12 -->
        <col style="width:16.7mm"><!-- 13 -->
        <col style="width:17.5mm"><!-- 14 -->
        <col style="width:17.82mm"><!-- 15 -->
        </colgroup>

        <!-- HEADER -->
        <tr class="h-header">
          <td colspan="4" class="left">
            <div class="logo">Milwaukee</div>
          </td>
          <td colspan="11" class="center">
            <div class="title">PRODUCTO TERMINADO - ENSAMBLE</div>
          </td>
        </tr>

        <!-- ROW 1: Líder / Turno / Job / Fecha(label) -->
        <tr class="h-top1">
          <td colspan="2" class="bg-gray bold center">Líder:</td>
          <td colspan="3" class="bg-cream"></td>

          <td colspan="1" class="bg-gray bold center">Turno:</td>
          <td colspan="1" class="bg-cream center bold">{{ $s['shift'] ?? '' }}</td>

          <td colspan="2" rowspan="2" class="bg-gray center">
            <div class="job-label">Job</div>
          </td>

          <td colspan="4" class="bg-peach center">
            <div class="job-top">{{ $s['job'] ?? '' }}</div>
          </td>

          <td colspan="2" class="bg-gray bold center">Fecha:</td>
        </tr>

        <!-- ROW 2: Línea / Job barcode / Fecha(value) -->
        <tr class="h-top2">
          <td colspan="2" class="bg-gray bold center">Línea:</td>
          <td colspan="5" class="center bold">{{ $s['line'] ?? '' }}</td>

          <td colspan="4" class="center">
            <div class="job-bar">*{{ $s['job'] ?? '' }}*</div>
            <div class="job-bar"><svg class="barcode" data-value="{{ $s['job'] ?? '' }}"></svg></div>
          </td>

          <!-- Fecha value (grande crema, como Excel) -->
          <td colspan="2" rowspan="2" class="bg-cream center bold">{{ $s['date'] ?? '' }}</td>
        </tr>

        <!-- ROW 3: Modelo / Folio (sin recuadro extra) -->
        <tr class="h-top3">
            <td colspan="2" class="bg-gray bold center">Modelo:</td>
            <td colspan="5" class="center bold">{{ $s['model'] ?? '' }}</td>

            <td colspan="2" class="bg-gray bold center">Folio:</td>
            <td colspan="4" class="center bold">{{ $s['folio_no'] ?? '' }}</td>

            <td colspan="2" class="bg-cream center bold">{{ $s['date'] ?? '' }}</td>
        </tr>

        <!-- BLOQUE NP / LOTE (Lote más ancho y NP mejor separado) -->
        <tr class="h-np">
          <!-- NP label -->
          <td colspan="2" class="bg-gray bold center">Np Ensamble:</td>

          <!-- NP data -->
          <td colspan="8" style="padding:0;">
            <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
              <tr style="height: 16mm;">
                <td class="center" style="border:0;">
                  <div class="np-top">{{ $s['np'] ?? '' }}</div>
                </td>
              </tr>
              <tr style="height: 12mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0; padding: 0 3mm;">
                  <div class="np-desc">{{ $s['desc'] ?? '' }}</div>
                </td>
              </tr>
              <tr style="height: 32mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0;">
                  <div class="np-bar">*{{ $s['np'] ?? '' }}*</div>
                  <div class="np-bar"><svg class="barcode" data-value="{{ $s['np'] ?? '' }}"></svg></div>
                </td>
              </tr>
            </table>
          </td>

          <!-- Lote label -->
          <td colspan="2" class="bg-gray bold center">Lote</td>

          <!-- Lote data (colspan 3) -->
          <td colspan="3" style="padding:0;">
            <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
              <tr style="height: 24mm;">
                <td class="center" style="border:0;">
                  <div class="lote-top">{{ $s['lote'] ?? '' }}</div>
                </td>
              </tr>
              <tr style="height: 36mm;">
                <td class="center" style="border-top: var(--b) solid #000; border-left:0; border-right:0; border-bottom:0;">
                  <div class="lote-bar">*{{ $s['lote'] ?? '' }}*</div>
                  <div class="lote-bar"><svg class="barcode" data-value="{{ $s['lote'] ?? '' }}"></svg></div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- SUB/LOCAL/QTY/OBS HEADER -->
        <tr class="h-subh">
          <td colspan="4" class="bg-gray bold center">Subinventory:</td>
          <td colspan="4" class="bg-gray bold center">Local:</td>
          <td colspan="3" class="bg-gray bold center">Cantidad en pallet:</td>
          <td colspan="4" class="bg-gray bold center">Observaciones:</td>
        </tr>

        <!-- SUB/LOCAL/QTY/OBS row values -->
        <tr class="h-sub1">
          <td colspan="4" class="center">{{ $s['subinventory'] ?? '' }}</td>
          <td colspan="4" class="center">{{ $s['local'] ?? '' }}</td>
          <td colspan="3" class="center">{{ $s['qty_pallet'] ?? '' }}</td>
          <td colspan="4"></td>
        </tr>

        <!-- SUB/LOCAL/QTY/OBS row barcodes -->
        <tr class="h-sub2">
          <td colspan="4" class="center">
            <div class="sub-bar">*{{ $s['subinventory'] ?? '' }}*</div>
            <div class="sub-bar"><svg class="barcode" data-value="{{ $s['subinventory'] ?? '' }}"></svg></div>
          </td>

          <td colspan="4" class="center">
            <div class="sub-bar">*{{ $s['local'] ?? '' }}*</div>
            <div class="sub-bar"><svg class="barcode" data-value="{{ $s['local'] ?? '' }}"></svg></div>
          </td>

          <td colspan="3" class="center">
            <div class="sub-bar">*{{ $s['qty_pallet'] ?? '' }}*</div>
            <div class="sub-bar"><svg class="barcode" data-value="{{ $s['qty_pallet'] ?? '' }}"></svg></div>
          </td>

          <td colspan="4"></td>
        </tr>

        <!-- FOOTER TITLES -->
        <tr class="h-foot-h">
          <td colspan="5" class="bg-gray bold center">LIBERACION IPQC</td>
          <td colspan="5" class="bg-gray bold center">LIBERACION OQC</td>
          <td colspan="5" class="bg-gray bold center">PRODUCTION SUPPORT</td>
        </tr>

        <!-- FOOTER EMPTY -->
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
<script>
  window.addEventListener('load', () => {
    document.querySelectorAll('.barcode[data-value]').forEach((barcodeEl) => {
      const value = (barcodeEl.dataset.value || '').trim();
      if (!value) {
        barcodeEl.outerHTML = '<div class="barcode-empty">Sin código</div>';
        return;
      }

      const normalized = value.toUpperCase();
      JsBarcode(barcodeEl, normalized, {
        format: 'CODE39',
        displayValue: false,
        margin: 0,
        width: 1.4,
        height: 44,
        background: '#ffffff'
      });
    });
  });
</script>

@if(($mode ?? null) === 'print')
  <script>
    window.addEventListener('load', () => window.print());
  </script>
@endif

</body>
</html>
