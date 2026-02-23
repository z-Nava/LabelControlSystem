<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Master - Ensamble y Empaque</title>

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

        <!-- GRID FIJO: 15 columnas -->
        <colgroup>
          <col style="width:17.5mm"><col style="width:17.5mm"><col style="width:17.5mm"><col style="width:17.5mm"><col style="width:17.5mm">
          <col style="width:14.32mm"><col style="width:14.32mm"><col style="width:19.09mm"><col style="width:19.09mm">
          <col style="width:17.5mm"><col style="width:17.5mm"><col style="width:16.7mm"><col style="width:16.7mm"><col style="width:17.5mm"><col style="width:17.82mm">
        </colgroup>

        @php
          $cell  = 'border border-black align-middle';
          $pad   = 'p-[1.2mm]';

          $gray  = 'bg-gradient-to-b from-slate-100 to-slate-300';
          $cream = 'bg-[#fff8d9]';
          $peach = 'bg-[#fde8dc]';
          $yellow= 'bg-[#fff200]';
        @endphp

        <!-- HEADER -->
        <tr style="height:12mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} text-left">
            <div class="text-[34px] font-black italic leading-none text-[#c00000] pl-[6mm]">Milwaukee</div>
          </td>

          <td colspan="9" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[22px] font-extrabold tracking-[.8px] leading-none">
              PRODUCTO TERMINADO - ENSAMBLE Y EMPAQUE
            </div>
          </td>

          <!-- DESTINO (solo etiqueta como en imagen) -->
          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center">
            <div class="text-[16px] font-bold leading-none">Destino</div>
          </td>

          <td colspan="1" class="{{ $cell }} {{ $pad }} {{ $yellow }} text-center">
            <div class="text-[18px] font-extrabold leading-none">OB EXCELLENCE</div>
          </td>
        </tr>

        <!-- ROW 1: Líder / Turno / Job Ensamble / Fecha -->
        <tr style="height:13mm">
          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Líder:</td>
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $cream }} text-center font-bold">
            {{ $s['leader'] ?? '' }}
          </td>

          <td colspan="1" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Turno:</td>
          <td colspan="1" class="{{ $cell }} {{ $pad }} {{ $cream }} text-center font-bold">
            {{ $s['shift'] ?? '' }}
          </td>

          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-extrabold">
            Job Ensamble:
          </td>

          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $peach }} text-center">
            <div class="text-[18px] font-semibold leading-none">{{ $s['job'] ?? '' }}</div>
            <svg class="js-barcode block w-full mt-[.6mm]"
                 data-format="CODE39"
                 data-height="42"
                 data-width="1.15"
                 data-margin="2"
                 data-value="{{ $s['job'] ?? '' }}"></svg>
          </td>

          <td colspan="1" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Fecha:</td>
          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $cream }} text-center font-bold">
            {{ $s['date'] ?? '' }}
          </td>
        </tr>

        <!-- ROW 2: Línea / Job Empaque / Custom PO -->
        <tr style="height:13mm">
          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Línea:</td>
          <td colspan="5" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[22px] font-medium leading-none">{{ $s['line'] ?? '' }}</div>
          </td>

          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-extrabold">
            Job Empaque:
          </td>

          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $peach }} text-center">
            <div class="text-[18px] font-semibold leading-none">{{ $s['job_packaging'] ?? ($s['job_pack'] ?? '') }}</div>
            <svg class="js-barcode block w-full mt-[.6mm]"
                 data-format="CODE39"
                 data-height="42"
                 data-width="1.15"
                 data-margin="2"
                 data-value="{{ $s['job_packaging'] ?? ($s['job_pack'] ?? '') }}"></svg>
          </td>

          <td colspan="1" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Custom PO</td>
          <td colspan="2" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[18px] font-semibold leading-none">{{ $s['po_number'] ?? '' }}</div>
            <svg class="js-barcode block w-full mt-[.6mm]"
                 data-format="CODE39"
                 data-height="42"
                 data-width="1.15"
                 data-margin="2"
                 data-value="{{ $s['po_number'] ?? '' }}"></svg>
          </td>
        </tr>

        <!-- ROW 3: Modelo / Folio -->
        <tr style="height:11mm">
          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Modelo:</td>
          <td colspan="5" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[22px] font-medium leading-none">{{ $s['model'] ?? '' }}</div>
          </td>

          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Folio:</td>
          <td colspan="3" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[22px] font-medium leading-none">{{ $s['folio_no'] ?? '' }}</div>
          </td>

          <!-- Bloque derecho vacío como el template de la imagen -->
          <td colspan="3" class="{{ $cell }} {{ $pad }}"></td>
        </tr>

        <!-- BLOQUE NP ENSAMBLE -->
        <tr style="height:28mm">
          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Np Ensamble:</td>

          <td colspan="10" class="{{ $cell }} p-0">
            <div class="h-full flex flex-col">
              <div class="h-[7mm] flex items-center justify-center">
                <div class="text-[22px] font-semibold leading-none">{{ $s['np'] ?? '' }}</div>
              </div>

              <div class="h-[7mm] border-t border-black flex items-center justify-center px-[3mm]">
                <div class="text-[11px] leading-tight text-center line-clamp-2 break-words">
                  {{ $s['desc'] ?? '' }}
                </div>
              </div>

              <div class="flex-1 border-t border-black flex flex-col items-center justify-center px-[1mm]">
                <svg class="js-barcode block w-full mt-[.6mm]"
                     data-format="CODE39"
                     data-height="42"
                     data-width="1.15"
                     data-margin="2"
                     data-value="{{ $s['np'] ?? '' }}"></svg>
              </div>
            </div>
          </td>

          <td colspan="3" class="{{ $cell }} p-0">
            <div class="h-full flex flex-col">
              <div class="h-1/2 {{ $gray }} border-b border-black flex items-center justify-center">
                <div class="text-[16px] font-bold leading-tight text-center">
                  Lote<br>Ensamble:
                </div>
              </div>

              <div class="flex-1 flex flex-col items-center justify-center px-[1mm]">
                <div class="text-[18px] font-semibold leading-none">{{ $s['lote'] ?? '' }}</div>
                <svg class="js-barcode block w-full mt-[.6mm]"
                     data-format="CODE39"
                     data-height="40"
                     data-width="1.05"
                     data-margin="2"
                     data-value="{{ $s['lote'] ?? '' }}"></svg>
              </div>
            </div>
          </td>
        </tr>

        <!-- BLOQUE NP EMPAQUE -->
        <tr style="height:28mm">
          <td colspan="2" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Np Empaque:</td>

          <td colspan="10" class="{{ $cell }} p-0">
            <div class="h-full flex flex-col">
              <div class="h-[7mm] flex items-center justify-center">
                <div class="text-[22px] font-semibold leading-none">{{ $s['np_packaging'] ?? '' }}</div>
              </div>

              <div class="h-[7mm] border-t border-black flex items-center justify-center px-[3mm]">
                <div class="text-[11px] leading-tight text-center line-clamp-2 break-words">
                  {{ $s['desc_packaging'] ?? '' }}
                </div>
              </div>

              <div class="flex-1 border-t border-black flex flex-col items-center justify-center px-[1mm]">
                <svg class="js-barcode block w-full mt-[.6mm]"
                     data-format="CODE39"
                     data-height="42"
                     data-width="1.15"
                     data-margin="2"
                     data-value="{{ $s['np_packaging'] ?? '' }}"></svg>
              </div>
            </div>
          </td>

          <td colspan="3" class="{{ $cell }} p-0">
            <div class="h-full flex flex-col">
              <div class="h-1/2 {{ $gray }} border-b border-black flex items-center justify-center">
                <div class="text-[16px] font-bold leading-tight text-center">
                  Lote<br>Empaque:
                </div>
              </div>

              <div class="flex-1 flex flex-col items-center justify-center px-[1mm]">
                <div class="text-[18px] font-semibold leading-none">{{ $s['lote_packaging'] ?? '' }}</div>
                <svg class="js-barcode block w-full mt-[.6mm]"
                     data-format="CODE39"
                     data-height="40"
                     data-width="1.05"
                     data-margin="2"
                     data-value="{{ $s['lote_packaging'] ?? '' }}"></svg>
              </div>
            </div>
          </td>
        </tr>

        <!-- SUB/LOCAL/QTY/OBS HEADER -->
        <tr style="height:10mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Subinventory:</td>
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Local:</td>
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Cantidad en pallet:</td>
          <td colspan="6" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">Observaciones:</td>
        </tr>

        <!-- VALUES -->
        <tr style="height:16mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[22px] font-medium leading-none">{{ $s['subinventory'] ?? '' }}</div>
          </td>
          <td colspan="3" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[22px] font-medium leading-none">{{ $s['local'] ?? '' }}</div>
          </td>
          <td colspan="3" class="{{ $cell }} {{ $pad }} text-center">
            <div class="text-[22px] font-medium leading-none">{{ $s['qty_pallet'] ?? '' }}</div>
          </td>
          <td colspan="6" class="{{ $cell }} {{ $pad }}"></td>
        </tr>

        <!-- BARCODES (con texto grande *VALOR* como imagen) -->
        <tr style="height:18mm">
          <td colspan="3" class="{{ $cell }} {{ $pad }} text-center px-[1mm]">
            <svg class="js-barcode block w-full mt-[.6mm]"
                 data-format="CODE39"
                 data-height="42"
                 data-width="1.15"
                 data-margin="2"
                 data-value="{{ $s['subinventory'] ?? '' }}"></svg>
          </td>

          <td colspan="3" class="{{ $cell }} {{ $pad }} text-center px-[1mm]">

            <svg class="js-barcode block w-full mt-[.6mm]"
                 data-format="CODE39"
                 data-height="42"
                 data-width="1.15"
                 data-margin="2"
                 data-value="{{ $s['local'] ?? '' }}"></svg>
          </td>

          <td colspan="3" class="{{ $cell }} {{ $pad }} text-center px-[1mm]">
            <svg class="js-barcode block w-full mt-[.6mm]"
                 data-format="CODE39"
                 data-height="42"
                 data-width="1.15"
                 data-margin="2"
                 data-value="{{ $s['qty_pallet'] ?? '' }}"></svg>
          </td>

          <td colspan="6" class="{{ $cell }} {{ $pad }}"></td>
        </tr>

        <!-- FOOTER TITLES -->
        <tr style="height:10mm">
          <td colspan="4" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">LIBERACION IPQC</td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">LIBERACION OQC</td>
          <td colspan="4" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">PRODUCTION SUPPORT</td>
          <td colspan="3" class="{{ $cell }} {{ $pad }} {{ $gray }} text-center font-bold">ALMACÉN:</td>
        </tr>

        <!-- FOOTER EMPTY -->
        <tr style="height:36mm">
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