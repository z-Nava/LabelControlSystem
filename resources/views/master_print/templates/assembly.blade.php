<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Master - Ensamble</title>

    @vite('resources/css/app.css')

    <style>
        @media print {
            @page { size: landscape; margin: 4mm; }
            html, body { margin: 0; padding: 0; background: #fff; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="m-0 bg-slate-100 print:bg-white" data-render-qrs="1" data-auto-print="{{ ($mode ?? null) === 'print' ? '1' : '0' }}">
    @foreach($sheets as $s)
        <section class="mx-auto w-full max-w-[258mm] px-2 py-2 print:px-0 print:py-0 print:break-before-page first:print:break-before-auto">
            <div class="overflow-x-auto">
                <div class="min-w-[250mm] overflow-hidden rounded-[2.5mm] border border-black bg-white shadow-sm print:rounded-none print:shadow-none">
                    <table class="w-full table-fixed border-collapse text-black">
                        <colgroup>
                            <col class="w-[17.5mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]"><col class="w-[17.5mm]">
                            <col class="w-[14.32mm]"><col class="w-[14.32mm]"><col class="w-[19.09mm]"><col class="w-[19.09mm]">
                            <col class="w-[17.5mm]"><col class="w-[17.5mm]"><col class="w-[16.7mm]"><col class="w-[16.7mm]"><col class="w-[17.5mm]"><col class="w-[17.82mm]">
                        </colgroup>

                        <tr class="h-[12mm]">
                            <td colspan="4" class="border border-black p-[1.2mm]"><div class="flex h-full items-center pl-[8mm]"><img src="{{ Vite::asset('resources/img/LOGO-MILWAUKEE.png') }}" alt="Milwaukee" class="h-[10mm] w-auto object-contain"></div></td>
                            <td colspan="11" class="border border-black p-[1.2mm] text-center"><div class="text-[22px] font-extrabold tracking-[0.8px]">PRODUCTO TERMINADO - ENSAMBLE</div></td>
                        </tr>

                        <tr class="h-[14mm]">
                            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Líder:</td>
                            <td colspan="3" class="border border-black bg-[#fff8d9] p-[1.2mm] text-center font-bold">{{ $s['leader'] ?? '' }}</td>
                            <td class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Turno:</td>
                            <td class="border border-black bg-[#fff8d9] p-[1.2mm] text-center font-bold">{{ $s['shift'] ?? '' }}</td>
                            <td colspan="2" rowspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center"><div class="text-[24px] font-extrabold">Job</div></td>
                            <td colspan="4" class="border border-black bg-[#fde8dc] p-[1.2mm] text-center"><div class="text-[24px] font-extrabold">{{ $s['job'] ?? '' }}</div></td>
                            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Fecha:</td>
                        </tr>

                        <tr class="h-[14mm]">
                            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Línea:</td>
                            <td colspan="5" class="border border-black p-[1.2mm] text-center font-bold">{{ $s['line'] ?? '' }}</td>
                            <td colspan="4" class="border border-black p-[1.2mm]"><div class="flex h-full items-center justify-center"><div class="js-qr h-[17mm] w-[17mm] overflow-hidden" data-size="76" data-value="{{ $s['job'] ?? '' }}"></div></div></td>
                            <td class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Folio:</td>
                            <td class="border border-black p-[1.2mm] text-center text-[18px] font-extrabold">{{ $s['folio_no'] ?? '' }}</td>
                            <td colspan="2" class="border border-black bg-[#fff8d9] p-[1.2mm] text-center font-bold">{{ $s['date'] ?? '' }}</td>
                        </tr>

                        <tr class="h-[55mm]">
                            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Np Ensamble:</td>
                            <td colspan="8" class="border border-black p-0">
                                <div class="flex h-full flex-col">
                                    <div class="flex h-[16mm] items-center justify-center px-[2mm] text-center text-[30px] font-extrabold">{{ $s['np'] ?? '' }}</div>
                                    <div class="flex h-[12mm] items-center justify-center border-t border-black px-[3mm] text-center text-[11px]">{{ $s['desc'] ?? '' }}</div>
                                    <div class="flex flex-1 items-center justify-center border-t border-black px-[2mm]"><div class="js-qr h-[23mm] w-[23mm] overflow-hidden" data-size="96" data-value="{{ $s['np'] ?? '' }}"></div></div>
                                </div>
                            </td>
                            <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Lote</td>
                            <td colspan="3" class="border border-black p-0">
                                <div class="flex h-full flex-col">
                                    <div class="flex h-[24mm] items-center justify-center px-[2mm] text-center text-[28px] font-extrabold">{{ $s['lote'] ?? '' }}</div>
                                    <div class="flex flex-1 items-center justify-center border-t border-black px-[2mm]"><div class="js-qr h-[21mm] w-[21mm] overflow-hidden" data-size="88" data-value="{{ $s['lote'] ?? '' }}"></div></div>
                                </div>
                            </td>
                        </tr>

                        <tr class="h-[10mm]">
                            <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Subinventory:</td>
                            <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Local:</td>
                            <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Cantidad en pallet:</td>
                            <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">Observaciones:</td>
                        </tr>
                        <tr class="h-[16mm]">
                            <td colspan="4" class="border border-black p-[1.2mm] text-center">{{ $s['subinventory'] ?? '' }}</td>
                            <td colspan="4" class="border border-black p-[1.2mm] text-center">{{ $s['local'] ?? '' }}</td>
                            <td colspan="3" class="border border-black p-[1.2mm] text-center">{{ $s['qty_pallet'] ?? '' }}</td>
                            <td colspan="4" class="border border-black p-[1.2mm]"></td>
                        </tr>
                        <tr class="h-[18mm]">
                            <td colspan="4" class="border border-black p-[1.2mm]"><div class="flex h-full items-center justify-center"><div class="js-qr h-[17mm] w-[17mm] overflow-hidden" data-size="76" data-value="{{ $s['subinventory'] ?? '' }}"></div></div></td>
                            <td colspan="4" class="border border-black p-[1.2mm]"><div class="flex h-full items-center justify-center"><div class="js-qr h-[17mm] w-[17mm] overflow-hidden" data-size="76" data-value="{{ $s['local'] ?? '' }}"></div></div></td>
                            <td colspan="3" class="border border-black p-[1.2mm]"><div class="flex h-full items-center justify-center"><div class="js-qr h-[17mm] w-[17mm] overflow-hidden" data-size="76" data-value="{{ $s['qty_pallet'] ?? '' }}"></div></div></td>
                            <td colspan="4" class="border border-black p-[1.2mm]"></td>
                        </tr>

                        <tr class="h-[10mm]">
                            <td colspan="5" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">LIBERACION IPQC</td>
                            <td colspan="5" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">LIBERACION OQC</td>
                            <td colspan="5" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center font-bold">PRODUCTION SUPPORT</td>
                        </tr>
                        <tr class="h-[36mm]">
                            <td colspan="5" class="border border-black p-[1.2mm]"></td>
                            <td colspan="5" class="border border-black p-[1.2mm]"></td>
                            <td colspan="5" class="border border-black p-[1.2mm]"></td>
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
