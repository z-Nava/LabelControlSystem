<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Master - Ensamble y Empaque</title>

  @vite('resources/css/app.css')
  <style>
    @media print {
      @page { size: letter landscape; margin: 6mm; }
      html, body { margin: 0; padding: 0; background: #fff; }
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body class="m-0 bg-slate-100 print:bg-white"
      data-render-qrs="1"
      data-auto-print="{{ ($mode ?? null) === 'print' ? '1' : '0' }}">

@foreach($sheets as $s)
  <section class="mx-auto w-full max-w-[258mm] px-2 py-2 print:px-0 print:py-0 print:break-before-page first:print:break-before-auto">
    <div class="w-full overflow-hidden">
      <div class="w-full overflow-hidden rounded-[2.5mm] border border-black bg-white shadow-sm print:rounded-none print:shadow-none">
        <table class="w-full table-fixed border-collapse">
          <colgroup>
            <col class="w-[17.5mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]">
            <col class="w-[14.32mm]"><col class="w-[14.32mm]"><col class="w-[19.09mm]"><col class="w-[19.09mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]">
            <col class="w-[16.7mm]"><col class="w-[16.7mm]"><col class="w-[17.5mm]"><col class="w-[17.82mm]">
          </colgroup>

          <tr class="h-[13mm]">
            <td colspan="3" class="border border-black p-[1.2mm]">
              <div class="flex h-full items-center pl-[3mm]">
                <img src="{{ Vite::asset('resources/img/LOGO-MILWAUKEE.png') }}"
                     alt="Milwaukee"
                     class="h-[10mm] w-auto object-contain">
              </div>
            </td>
            <td colspan="8" class="border border-black p-[1.2mm] text-center text-[19px] font-extrabold tracking-[0.8px]">
              PRODUCTO TERMINADO - ENSAMBLE Y EMPAQUE
            </td>
            <td class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center text-[14px] font-bold">
              Destino
            </td>
            <td colspan="3" class="border border-black bg-yellow-300 p-[1.2mm] text-center text-[15px] font-extrabold leading-tight break-words">
              {{ $s['destination'] ?? ($s['destino'] ?? 'OB EXCELLENCE') }}
            </td>
          </tr>

          {{-- Fila superior 1 --}}
          <tr class="h-[18mm]">
            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Líder:
            </td>
            <td colspan="3" class="border border-black bg-[#fff8d9] p-[1.2mm] text-center font-bold">
              {{ $s['leader'] ?? '' }}
            </td>

            <td class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Turno:
            </td>
            <td class="border border-black bg-[#fff8d9] p-[1.2mm] text-center font-bold">
              {{ $s['shift'] ?? '' }}
            </td>

            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-extrabold">
              Job Ensamble:
            </td>

            <td colspan="3" class="border border-black bg-[#fde8dc] p-0">
              <div class="flex h-full flex-col">
                <div class="flex h-[8mm] items-center justify-center px-[1mm] text-center text-[18px] font-semibold">
                  {{ $s['job'] ?? '' }}
                </div>
                <div class="flex flex-1 items-center justify-center border-t border-black">
                  <div class="js-qr h-[10mm] w-[10mm] overflow-hidden"
                       data-size="46"
                       data-value="{{ $s['job'] ?? '' }}"></div>
                </div>
              </div>
            </td>

            <td class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Fecha:
            </td>
            <td colspan="2" class="border border-black bg-[#fff8d9] p-[1.2mm] text-center font-bold">
              {{ $s['date'] ?? '' }}
            </td>
          </tr>

          {{-- Fila superior 2 --}}
          <tr class="h-[18mm]">
            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Línea:
            </td>
            <td colspan="5" class="border border-black p-[1.2mm] text-center text-[20px] font-medium">
              {{ $s['line'] ?? '' }}
            </td>

            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-extrabold">
              Job Empaque:
            </td>

            <td colspan="3" class="border border-black bg-[#fde8dc] p-0">
              <div class="flex h-full flex-col">
                <div class="flex h-[8mm] items-center justify-center px-[1mm] text-center text-[18px] font-semibold">
                  {{ $s['job_packaging'] ?? ($s['job_pack'] ?? '') }}
                </div>
                <div class="flex flex-1 items-center justify-center border-t border-black">
                  <div class="js-qr h-[10mm] w-[10mm] overflow-hidden"
                       data-size="46"
                       data-value="{{ $s['job_packaging'] ?? ($s['job_pack'] ?? '') }}"></div>
                </div>
              </div>
            </td>

            <td class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Custom PO
            </td>

            <td colspan="2" class="border border-black p-0">
              <div class="flex h-full flex-col">
                <div class="flex h-[8mm] items-center justify-center px-[1mm] text-center text-[15px] font-semibold">
                  {{ $s['po_number'] ?? '' }}
                </div>
                <div class="flex flex-1 items-center justify-center border-t border-black">
                  @if(!empty($s['po_number']))
                    <div class="js-qr h-[10mm] w-[10mm] overflow-hidden"
                         data-size="44"
                         data-value="{{ $s['po_number'] }}"></div>
                  @endif
                </div>
              </div>
            </td>
          </tr>

          {{-- Fila superior 3 --}}
          <tr class="h-[13mm]">
            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Modelo:
            </td>
            <td colspan="5" class="border border-black p-[1.2mm] text-center text-[20px] font-medium">
              {{ $s['model'] ?? '' }}
            </td>

            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Folio:
            </td>
            <td colspan="3" class="border border-black p-[1.2mm] text-center text-[20px] font-medium">
              {{ $s['folio_no'] ?? '' }}
            </td>

            <td colspan="3" class="border border-black p-[1.2mm]"></td>
          </tr>

          <tr class="h-[30mm]">
            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Np Ensamble:
            </td>

            <td colspan="10" class="border border-black p-0">
              <div class="flex h-full flex-col">
                <div class="flex h-[8mm] items-center justify-center text-[20px] font-semibold">
                  {{ $s['np'] ?? '' }}
                </div>
                <div class="flex h-[8mm] items-center justify-center border-y border-black px-[3mm] text-center text-[10px]">
                  {{ $s['desc'] ?? '' }}
                </div>
                <div class="flex flex-1 items-center justify-center">
                  <div class="js-qr h-[19mm] w-[19mm] overflow-hidden"
                       data-size="80"
                       data-value="{{ $s['np'] ?? '' }}"></div>
                </div>
              </div>
            </td>

            <td colspan="3" class="border border-black p-0">
              <div class="flex h-full flex-col">
                <div class="flex h-[9mm] items-center justify-center bg-gradient-to-b from-slate-100 to-slate-300 text-[13px] font-bold tracking-wide">
                  Lote Ensamble:
                </div>
                <div class="flex h-[10mm] items-center justify-center border-t border-black px-[1mm] text-center text-[16px] font-semibold">
                  {{ $s['lote'] ?? '' }}
                </div>
                <div class="flex flex-1 items-center justify-center border-t border-black">
                  <div class="js-qr h-[14mm] w-[14mm] overflow-hidden"
                       data-size="62"
                       data-value="{{ $s['lote'] ?? '' }}"></div>
                </div>
              </div>
            </td>
          </tr>

          <tr class="h-[30mm]">
            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Np Empaque:
            </td>

            <td colspan="10" class="border border-black p-0">
              <div class="flex h-full flex-col">
                <div class="flex h-[8mm] items-center justify-center text-[20px] font-semibold">
                  {{ $s['np_packaging'] ?? '' }}
                </div>
                <div class="flex h-[8mm] items-center justify-center border-y border-black px-[3mm] text-center text-[10px]">
                  {{ $s['desc_packaging'] ?? '' }}
                </div>
                <div class="flex flex-1 items-center justify-center">
                  <div class="js-qr h-[19mm] w-[19mm] overflow-hidden"
                       data-size="80"
                       data-value="{{ $s['np_packaging'] ?? '' }}"></div>
                </div>
              </div>
            </td>

            <td colspan="3" class="border border-black p-0">
              <div class="flex h-full flex-col">
                <div class="flex h-[9mm] items-center justify-center bg-gradient-to-b from-slate-100 to-slate-300 text-[13px] font-bold tracking-wide">
                  Lote Empaque:
                </div>
                <div class="flex h-[10mm] items-center justify-center border-t border-black px-[1mm] text-center text-[16px] font-semibold">
                  {{ $s['lote_packaging'] ?? '' }}
                </div>
                <div class="flex flex-1 items-center justify-center border-t border-black">
                  <div class="js-qr h-[14mm] w-[14mm] overflow-hidden"
                       data-size="62"
                       data-value="{{ $s['lote_packaging'] ?? '' }}"></div>
                </div>
              </div>
            </td>
          </tr>

          <tr class="h-[10mm]">
            <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Subinventory:
            </td>
            <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Local:
            </td>
            <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Cantidad en pallet:
            </td>
            <td colspan="6" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              Observaciones:
            </td>
          </tr>

          <tr class="h-[18mm]">
            <td colspan="3" class="border border-black p-0">
              <div class="flex h-full flex-col">
                <div class="flex h-[8mm] items-center justify-center px-[1mm] text-center text-[16px]">
                  {{ $s['subinventory'] ?? '' }}
                </div>
                <div class="flex flex-1 items-center justify-center border-t border-black">
                  <div class="js-qr h-[11mm] w-[11mm] overflow-hidden"
                       data-size="50"
                       data-value="{{ $s['subinventory'] ?? '' }}"></div>
                </div>
              </div>
            </td>

            <td colspan="3" class="border border-black p-0">
              <div class="flex h-full flex-col">
                <div class="flex h-[8mm] items-center justify-center px-[1mm] text-center text-[16px]">
                  {{ $s['local'] ?? '' }}
                </div>
                <div class="flex flex-1 items-center justify-center border-t border-black">
                  @if(!empty($s['local']))
                    <div class="js-qr h-[11mm] w-[11mm] overflow-hidden"
                         data-size="50"
                         data-value="{{ $s['local'] }}"></div>
                  @else
                    <span class="text-[10px] text-slate-400">Sin código</span>
                  @endif
                </div>
              </div>
            </td>

            <td colspan="3" class="border border-black p-0">
              <div class="flex h-full flex-col">
                <div class="flex h-[8mm] items-center justify-center px-[1mm] text-center text-[16px]">
                  {{ $s['qty_pallet'] ?? '' }}
                </div>
                <div class="flex flex-1 items-center justify-center border-t border-black">
                  <div class="js-qr h-[11mm] w-[11mm] overflow-hidden"
                       data-size="50"
                       data-value="{{ $s['qty_pallet'] ?? '' }}"></div>
                </div>
              </div>
            </td>

            <td colspan="6" class="border border-black p-[1.2mm]"></td>
          </tr>

          <tr class="h-[10mm]">
            <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              LIBERACION IPQC
            </td>
            <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              LIBERACION OQC
            </td>
            <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              PRODUCTION SUPPORT
            </td>
            <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">
              ALMACÉN:
            </td>
          </tr>

          <tr class="h-[36mm]">
            <td colspan="4" class="border border-black p-[1.2mm]"></td>
            <td colspan="4" class="border border-black p-[1.2mm]"></td>
            <td colspan="4" class="border border-black p-[1.2mm]"></td>
            <td colspan="3" class="border border-black p-[1.2mm]"></td>
          </tr>
        </table>
      </div>
    </div>
  </section>
@endforeach

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@vite('resources/js/app.js')
</body>
</html>