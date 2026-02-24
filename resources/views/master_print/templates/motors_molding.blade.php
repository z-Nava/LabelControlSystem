<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Master - Motores y Moldeo</title>

  @vite('resources/css/app.css')

  <style>
    @page { size: letter landscape; margin: 8mm; }
    body { margin:0; }

    .sheet { width: 258mm; margin: 0 auto; }
    .sheet + .sheet { page-break-before: always; }

    table, tr, td { page-break-inside: avoid; }
  </style>
</head>

<body class="bg-slate-100 print:bg-white"
      data-render-barcodes="1"
      data-auto-print="{{ ($mode ?? null) === 'print' ? '1' : '0' }}">

@foreach($sheets as $s)
  <div class="sheet py-2">
    <div class="bg-white border border-black rounded-[2.5mm] print:rounded-none shadow-sm print:shadow-none overflow-hidden">
      <table class="w-full border-collapse table-fixed">
        <colgroup>
          <col style="width:17.5mm"><col style="width:17.5mm"><col style="width:17.5mm"><col style="width:17.5mm"><col style="width:17.5mm">
          <col style="width:14.32mm"><col style="width:14.32mm"><col style="width:19.09mm"><col style="width:19.09mm">
          <col style="width:17.5mm"><col style="width:17.5mm"><col style="width:16.7mm"><col style="width:16.7mm"><col style="width:17.5mm"><col style="width:17.82mm">
        </colgroup>

        @php
          $gray  = 'bg-gradient-to-b from-slate-100 to-slate-300';
          $cream = 'bg-[#fff8d9]';
          $peach = 'bg-[#fde8dc]';
          $cell  = 'border border-black align-middle';
          $pad   = 'p-[1.2mm]';
        @endphp

        <tr style="height:12mm">
          <td colspan="4" class="{{ $cell }} {{ $pad }} text-left">
            <div class="text-[34px] font-black italic leading-none text-[#c00000] pl-[6mm]">Milwaukee</div>
          </td>
          <td colspan="11" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[22px] font-extrabold tracking-[.8px] leading-none">PRODUCTO TERMINADO - MOTORES Y MOLDEO</div>
          </td>
        </tr>

        <tr style="height:9mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-left font-bold">Líder:</td>
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $cream }}">{{ $s['leader'] ?? '' }}</td>

          <td colspan="1" rowspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Turno:</td>
          <td colspan="1" rowspan="2" class="{{ $cell }} {{ $pad }} {{ $cream }} text-center font-extrabold">{{ $s['shift'] ?? '' }}</td>

          <td colspan="2" rowspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center">
            <div class="text-[25px] font-extrabold leading-none">Job</div>
          </td>

          <td colspan="4" class="{{ $cell }} {{ $pad }} {{ $peach }} text-center">
            <div class="text-[26px] font-extrabold leading-none">{{ $s['job'] ?? '' }}</div>
          </td>

          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Fecha:</td>
        </tr>

        <tr style="height:9mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-left font-bold"># empleado estación final</td>
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $cream }}"></td>

          <td colspan="4" rowspan="2" class="{{ $cell }} {{ $pad }} text-center">
            <svg class="js-barcode w-full max-h-[14mm] mx-auto"
                 data-format="CODE39"
                 data-height="44"
                 data-width="1.4"
                 data-value="{{ $s['job'] ?? '' }}"></svg>
          </td>

          <td colspan="2" rowspan="3" class="{{ $cell }} {{ $pad }} {{ $cream }} text-center font-extrabold">{{ $s['date'] ?? '' }}</td>
        </tr>

        <tr style="height:20mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Línea:</td>
          <td colspan="5" class="{{ $cell }} {{ $pad }} text-center font-extrabold">{{ $s['line'] ?? '' }}</td>
        </tr>

        <tr style="height:10mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Modelo:</td>
          <td colspan="5" class="{{ $cell }} {{ $pad }} text-center font-extrabold">{{ $s['model'] ?? '' }}</td>

          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Folio:</td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} text-center font-extrabold">{{ $s['folio_no'] ?? '' }}</td>

          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $cream }} text-center font-extrabold">{{ $s['date'] ?? '' }}</td>
        </tr>

        <tr style="height:60mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Np Ensamble:</td>

          <td colspan="7" class="{{ $cell }} p-0">
            <div class="h-full flex flex-col">
              <div class="h-[16mm] flex items-center justify-center">
                <div class="text-[34px] font-extrabold leading-none">{{ $s['np'] ?? '' }}</div>
              </div>
              <div class="h-[12mm] border-t border-black flex items-center justify-center px-[3mm]">
                <div class="text-[11px] leading-tight text-center">{{ $s['desc'] ?? '' }}</div>
              </div>
              <div class="flex-1 border-t border-black flex items-center justify-center px-[2mm]">
                <svg class="js-barcode w-full max-h-[20mm] mx-auto"
                     data-format="CODE39"
                     data-height="64"
                     data-width="1.6"
                     data-value="{{ $s['np'] ?? '' }}"></svg>
              </div>
            </div>
          </td>

          <td colspan="1" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Lote:</td>

          <td colspan="4" class="{{ $cell }} p-0">
            <div class="h-full flex flex-col">
              <div class="h-[24mm] flex items-center justify-center">
                <div class="text-[30px] font-extrabold leading-none">{{ $s['lote'] ?? '' }}</div>
              </div>
              <div class="h-[16mm] border-t border-black flex items-center justify-center px-[2mm]">
                <svg class="js-barcode w-full max-h-[14mm] mx-auto"
                     data-format="CODE39"
                     data-height="44"
                     data-width="1.4"
                     data-value="{{ $s['lote'] ?? '' }}"></svg>
              </div>
              <div class="h-[10mm] border-t border-black {{ $gray }} flex items-center justify-center font-bold">Revisión:</div>
              <div class="h-[10mm] border-t border-black flex items-center justify-center text-[22px] font-extrabold leading-none">{{ $s['revision'] ?? '' }}</div>
              <div class="h-[10mm] border-t border-black flex items-center justify-center px-[2mm]">
                <svg class="js-barcode w-full max-h-[8mm] mx-auto"
                     data-format="CODE39"
                     data-height="28"
                     data-width="1.2"
                     data-value="{{ $s['revision'] ?? '' }}"></svg>
              </div>
            </div>
          </td>
        </tr>

        <tr style="height:10mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Subinventory:</td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Local:</td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Cantidad en pallet:</td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Observaciones:</td>
        </tr>

        <tr style="height:17mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[26px] leading-none font-semibold">{{ $s['subinventory'] ?? '' }}</div>
          </td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[26px] leading-none font-semibold">{{ $s['local'] ?? '' }}</div>
          </td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[26px] leading-none font-semibold">{{ $s['qty_pallet'] ?? '' }}</div>
          </td>
          <td colspan="4" class="{{ $cell }} {{ $pad }}"></td>
        </tr>

        <tr style="height:20mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} text-center">
            <svg class="js-barcode w-full max-h-[14mm] mx-auto"
                 data-format="CODE39"
                 data-height="44"
                 data-width="1.4"
                 data-value="{{ $s['subinventory'] ?? '' }}"></svg>
          </td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} text-center">
            <svg class="js-barcode w-full max-h-[14mm] mx-auto"
                 data-format="CODE39"
                 data-height="44"
                 data-width="1.4"
                 data-value="{{ $s['local'] ?? '' }}"></svg>
          </td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} text-center">
            <svg class="js-barcode w-full max-h-[14mm] mx-auto"
                 data-format="CODE39"
                 data-height="44"
                 data-width="1.4"
                 data-value="{{ $s['qty_pallet'] ?? '' }}"></svg>
          </td>
          <td colspan="4" class="{{ $cell }} {{ $pad }}"></td>
        </tr>

        <tr style="height:10mm">
          <td colspan="4" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">LIBERACION IPQC</td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">LIBERACION OQC</td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">PRODUCTION SUPPORT</td>
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">ALMACÉN:</td>
        </tr>

        <tr style="height:40mm">
          <td colspan="4" class="{{ $cell }} {{ $pad }}"></td>
          <td colspan="4" class="{{ $cell }} {{ $pad }}"></td>
          <td colspan="4" class="{{ $cell }} {{ $pad }}"></td>
          <td colspan="3" class="{{ $cell }} {{ $pad }}"></td>
        </tr>
      </table>
    </div>
  </div>
@endforeach

<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.12.1/JsBarcode.all.min.js"></script>
@vite('resources/js/app.js')
</body>
</html>