<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Master - Motores y Moldeo</title>

    @vite('resources/css/app.css')

    <style>
        @media print {
            @page { size: letter landscape; margin: 5mm; }
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
                <table class="w-full table-fixed border-collapse text-black">
                    <colgroup>
                        <col class="w-[17.5mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]">
                        <col class="w-[14.32mm]"><col class="w-[14.32mm]"><col class="w-[19.09mm]"><col class="w-[19.09mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]">
                        <col class="w-[16.7mm]"><col class="w-[16.7mm]"><col class="w-[17.5mm]"><col class="w-[17.82mm]">
                    </colgroup>

                    <tr class="h-[10mm]">
                        <td colspan="4" class="border border-black p-[1mm]">
                            <div class="flex h-full items-center pl-[2.5mm]">
                                <img src="{{ Vite::asset('resources/img/LOGO-MILWAUKEE.png') }}"
                                     alt="Milwaukee"
                                     class="h-[8.5mm] w-auto object-contain">
                            </div>
                        </td>
                        <td colspan="11" class="border border-black p-[1mm] text-center text-[20px] font-extrabold">
                            PRODUCTO TERMINADO - MOTORES Y MOLDEO
                        </td>
                    </tr>

                    <tr class="h-[8mm]">
                        <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] font-bold">
                            Líder:
                        </td>
                        <td colspan="3" class="border border-black bg-[#fff8d9] p-[1mm]">
                            {{ $s['leader'] ?? '' }}
                        </td>

                        <td rowspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center font-bold">
                            Turno:
                        </td>
                        <td rowspan="2" class="border border-black bg-[#fff8d9] p-[1mm] text-center font-bold">
                            {{ $s['shift'] ?? '' }}
                        </td>

                        <td colspan="2" rowspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center text-[20px] font-extrabold">
                            Job
                        </td>

                        <td colspan="3" class="border border-black bg-[#fde8dc] p-[1mm] text-center text-[18px] font-extrabold">
                            {{ $s['job'] ?? '' }}
                        </td>

                        <td colspan="2" rowspan="2" class="border border-black p-0">
    <table class="w-full h-full table-fixed border-collapse">
        <tr class="h-[15mm]">
            <td class="border-b border-black bg-gradient-to-b from-slate-100 to-slate-300 px-[1mm] text-center align-middle font-bold">
                Fecha:
            </td>
        </tr>
        <tr class="h-[8mm]">
            <td class="bg-[#fff8d9] px-[1mm] text-center align-middle text-[11px] font-bold whitespace-nowrap">
                {{ $s['date'] ?? '' }}
            </td>
        </tr>
    </table>
</td>
                    </tr>

                    <tr class="h-[8mm]">
                        <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] font-bold">
                            # empleado estación final
                        </td>
                        <td colspan="3" class="border border-black bg-[#fff8d9] p-[1mm]"></td>

                        <td colspan="3" class="border border-black p-[1mm]">
                            <div class="flex h-full items-center justify-center">
                                <div class="js-qr h-[12mm] w-[12mm] overflow-hidden"
                                     data-size="56"
                                     data-value="{{ $s['job'] ?? '' }}"></div>
                            </div>
                        </td>

                        <td colspan="3" class="border border-black bg-[#fde8dc] p-[1mm]"></td>
                    </tr>

                    <tr class="h-[12mm]">
                        <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center font-bold">
                            Línea:
                        </td>
                        <td colspan="5" class="border border-black p-[1mm] text-center font-bold">
                            {{ $s['line'] ?? '' }}
                        </td>
                        <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center font-bold">
                            Folio:
                        </td>
                        <td colspan="5" class="border border-black p-[1mm] text-center font-bold">
                            {{ $s['folio_no'] ?? '' }}
                        </td>
                    </tr>

                    <tr class="h-[8mm]">
                        <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center font-bold">
                            Modelo:
                        </td>
                        <td colspan="12" class="border border-black p-[1mm] text-center font-bold">
                            {{ $s['model'] ?? '' }}
                        </td>
                    </tr>

                    <tr class="h-[48mm]">
                        <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center font-bold">
                            Np Ensamble:
                        </td>

                        <td colspan="7" class="border border-black p-0">
                            <div class="flex h-full flex-col">
                                <div class="flex h-[12mm] items-center justify-center text-[26px] font-extrabold">
                                    {{ $s['np'] ?? '' }}
                                </div>
                                <div class="flex h-[10mm] items-center justify-center border-t border-black px-[2mm] text-center text-[10px]">
                                    {{ $s['desc'] ?? '' }}
                                </div>
                                <div class="flex flex-1 items-center justify-center border-t border-black">
                                    <div class="js-qr h-[18mm] w-[18mm] overflow-hidden"
                                         data-size="74"
                                         data-value="{{ $s['np'] ?? '' }}"></div>
                                </div>
                            </div>
                        </td>

                        {{-- Bloque completo de Lote / Revisión --}}
                        <td colspan="5" class="border border-black p-0">
                            <table class="w-full h-full table-fixed border-collapse">
                                <colgroup>
                                    <col class="w-[32%]">
                                    <col class="w-[68%]">
                                </colgroup>

                                <tr class="h-[24mm]">
                                    <td class="border-r border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center align-middle text-[12px] font-bold">
                                        Lote:
                                    </td>
                                    <td class="p-0">
                                        <div class="flex h-full flex-col">
                                            <div class="flex h-[12mm] items-center justify-center px-[1mm] text-center text-[18px] font-extrabold">
                                                {{ $s['lote'] ?? '' }}
                                            </div>
                                            <div class="flex flex-1 items-center justify-center border-t border-black">
                                                <div class="js-qr h-[10mm] w-[10mm] overflow-hidden"
                                                     data-size="44"
                                                     data-value="{{ $s['lote'] ?? '' }}"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="h-[24mm]">
                                    <td class="border-t border-r border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center align-middle text-[12px] font-bold">
                                        Revisión:
                                    </td>
                                    <td class="border-t border-black p-0">
                                        <div class="flex h-full flex-col">
                                            <div class="flex h-[10mm] items-center justify-center px-[1mm] text-center text-[16px] font-extrabold">
                                                {{ $s['revision'] ?? '' }}
                                            </div>
                                            <div class="flex flex-1 items-center justify-center border-t border-black">
                                                <div class="js-qr h-[12mm] w-[12mm] overflow-hidden"
                                                     data-size="52"
                                                     data-value="{{ $s['revision'] ?? '' }}"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr class="h-[8mm]">
                        <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center font-bold">
                            Subinventory:
                        </td>
                        <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center font-bold">
                            Local:
                        </td>
                        <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center font-bold">
                            Cantidad en pallet:
                        </td>
                        <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center font-bold">
                            Observaciones:
                        </td>
                    </tr>

                    <tr class="h-[26mm]">
                        <td colspan="3" class="border border-black p-0 align-top">
                            <div class="flex h-full flex-col">
                                <div class="flex h-[10mm] items-center justify-center px-[1mm] text-center">
                                    {{ $s['subinventory'] ?? '' }}
                                </div>
                                <div class="flex flex-1 items-center justify-center border-t border-black">
                                    <div class="js-qr h-[11mm] w-[11mm] overflow-hidden"
                                         data-size="46"
                                         data-value="{{ $s['subinventory'] ?? '' }}"></div>
                                </div>
                            </div>
                        </td>

                        <td colspan="4" class="border border-black p-0 align-top">
                            <div class="flex h-full flex-col">
                                <div class="flex h-[10mm] items-center justify-center px-[1mm] text-center">
                                    {{ $s['local'] ?? '' }}
                                </div>
                                <div class="flex flex-1 items-center justify-center border-t border-black">
                                    @if(!empty($s['local']))
                                        <div class="js-qr h-[11mm] w-[11mm] overflow-hidden"
                                             data-size="46"
                                             data-value="{{ $s['local'] }}"></div>
                                    @else
                                        <span class="text-[10px] text-slate-400">Sin código</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td colspan="4" class="border border-black p-0 align-top">
                            <div class="flex h-full flex-col">
                                <div class="flex h-[10mm] items-center justify-center px-[1mm] text-center">
                                    {{ $s['qty_pallet'] ?? '' }}
                                </div>
                                <div class="flex flex-1 items-center justify-center border-t border-black">
                                    <div class="js-qr h-[11mm] w-[11mm] overflow-hidden"
                                         data-size="46"
                                         data-value="{{ $s['qty_pallet'] ?? '' }}"></div>
                                </div>
                            </div>
                        </td>

                        <td colspan="4" class="border border-black p-[1mm]"></td>
                    </tr>

                    <tr class="h-[8mm]">
                        <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center text-[12px] font-bold">
                            LIBERACION IPQC
                        </td>
                        <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center text-[12px] font-bold">
                            LIBERACION OQC
                        </td>
                        <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center text-[12px] font-bold">
                            PRODUCTION SUPPORT
                        </td>
                        <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1mm] text-center text-[12px] font-bold">
                            ALMACÉN:
                        </td>
                    </tr>

                    <tr class="h-[28mm]">
                        <td colspan="4" class="border border-black p-[1mm]"></td>
                        <td colspan="4" class="border border-black p-[1mm]"></td>
                        <td colspan="4" class="border border-black p-[1mm]"></td>
                        <td colspan="3" class="border border-black p-[1mm]"></td>
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