<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Master - Ensamble</title>

    @vite('resources/css/app.css')

    <style>
        @media print {
            @page {
                size: landscape;
                margin: 4mm;
            }

            html, body {
                margin: 0;
                padding: 0;
                background: #fff;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            canvas,
            img {
                image-rendering: crisp-edges;
                image-rendering: pixelated;
            }
        }
    </style>
</head>

<body
    class="m-0 bg-slate-100 print:bg-white"
    data-render-qrs="1"
    data-auto-print="{{ ($mode ?? null) === 'print' ? '1' : '0' }}"
>
    @foreach($sheets as $s)
        <div class="mx-auto w-[250mm] py-2 print:py-0 print:break-before-page first:print:break-before-auto">
            <div class="overflow-hidden rounded-[2.5mm] border border-black bg-white shadow-sm print:rounded-none print:shadow-none">
                <table class="w-full table-fixed border-collapse text-black">
                    <colgroup>
                        <col class="w-[17.5mm]">
                        <col class="w-[17.5mm]">
                        <col class="w-[17.5mm]">
                        <col class="w-[17.5mm]">
                        <col class="w-[17.5mm]">
                        <col class="w-[14.32mm]">
                        <col class="w-[14.32mm]">
                        <col class="w-[19.09mm]">
                        <col class="w-[19.09mm]">
                        <col class="w-[17.5mm]">
                        <col class="w-[17.5mm]">
                        <col class="w-[16.7mm]">
                        <col class="w-[16.7mm]">
                        <col class="w-[17.5mm]">
                        <col class="w-[17.82mm]">
                    </colgroup>

                    {{-- HEADER --}}
                    <tr class="h-[12mm]">
                        <td colspan="4" class="border border-black p-[1.2mm] align-middle text-left">
                            <div class="flex h-full items-center pl-[10mm]">
                                <img
                                    src="{{ Vite::asset('resources/img/LOGO-MILWAUKEE.png') }}"
                                    alt="Milwaukee"
                                    class="h-[10mm] w-auto object-contain"
                                >
                            </div>
                        </td>

                        <td colspan="11" class="border border-black p-[1.2mm] align-middle text-center">
                            <div class="text-[22px] font-extrabold leading-none tracking-[0.8px]">
                                PRODUCTO TERMINADO - ENSAMBLE
                            </div>
                        </td>
                    </tr>

                    {{-- ROW 1 --}}
                    <tr class="h-[14mm]">
                        <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Líder:
                        </td>
                        <td colspan="3" class="border border-black bg-[#fff8d9] p-[1.2mm] text-center align-middle font-extrabold">
                            {{ $s['leader'] ?? '' }}
                        </td>

                        <td class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Turno:
                        </td>
                        <td class="border border-black bg-[#fff8d9] p-[1.2mm] text-center align-middle font-extrabold">
                            {{ $s['shift'] ?? '' }}
                        </td>

                        <td colspan="2" rowspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle">
                            <div class="text-[25px] font-extrabold leading-none">Job</div>
                        </td>

                        <td colspan="4" class="border border-black bg-[#fde8dc] p-[1.2mm] text-center align-middle">
                            <div class="text-[26px] font-extrabold leading-none">
                                {{ $s['job'] ?? '' }}
                            </div>
                        </td>

                        <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Fecha:
                        </td>
                    </tr>

                    {{-- ROW 2 --}}
                    <tr class="h-[14mm]">
                        <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Línea:
                        </td>
                        <td colspan="5" class="border border-black p-[1.2mm] text-center align-middle font-extrabold">
                            {{ $s['line'] ?? '' }}
                        </td>

                        <td colspan="4" class="border border-black p-[1.2mm] align-middle">
                            <div class="flex h-full items-center justify-center">
                                <div
                                    class="js-qr overflow-hidden"
                                    data-size="68"
                                    data-value="{{ $s['job'] ?? '' }}"
                                    style="width: 17mm; height: 17mm;"
                                ></div>
                            </div>
                        </td>

                        <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Folio:
                        </td>
                        <td colspan="4" class="border border-black p-[1.2mm] text-center align-middle font-extrabold">
                            {{ $s['folio_no'] ?? '' }}
                        </td>

                        <td colspan="2" class="border border-black bg-[#fff8d9] p-[1.2mm] text-center align-middle font-extrabold">
                            {{ $s['date'] ?? '' }}
                        </td>
                    </tr>

                    {{-- NP / LOTE --}}
                    <tr class="h-[55mm]">
                        <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Np Ensamble:
                        </td>

                        <td colspan="8" class="border border-black p-0 align-middle">
                            <div class="flex h-full flex-col">
                                <div class="flex h-[16mm] items-center justify-center px-[2mm] text-center">
                                    <div class="text-[34px] font-extrabold leading-none">
                                        {{ $s['np'] ?? '' }}
                                    </div>
                                </div>

                                <div class="flex h-[12mm] items-center justify-center border-t border-black px-[3mm] text-center">
                                    <div class="text-[11px] leading-tight">
                                        {{ $s['desc'] ?? '' }}
                                    </div>
                                </div>

                                <div class="flex flex-1 items-center justify-center border-t border-black px-[2mm]">
                                    <div
                                        class="js-qr overflow-hidden"
                                        data-size="84"
                                        data-value="{{ $s['np'] ?? '' }}"
                                        style="width: 21mm; height: 21mm;"
                                    ></div>
                                </div>
                            </div>
                        </td>

                        <td colspan="2" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Lote
                        </td>

                        <td colspan="3" class="border border-black p-0 align-middle">
                            <div class="flex h-full flex-col">
                                <div class="flex h-[24mm] items-center justify-center px-[2mm] text-center">
                                    <div class="text-[30px] font-extrabold leading-none">
                                        {{ $s['lote'] ?? '' }}
                                    </div>
                                </div>

                                <div class="flex flex-1 items-center justify-center border-t border-black px-[2mm]">
                                    <div
                                        class="js-qr overflow-hidden"
                                        data-size="80"
                                        data-value="{{ $s['lote'] ?? '' }}"
                                        style="width: 20mm; height: 20mm;"
                                    ></div>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- SUB / LOCAL / QTY / OBS TITLES --}}
                    <tr class="h-[10mm]">
                        <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Subinventory:
                        </td>
                        <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Local:
                        </td>
                        <td colspan="3" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Cantidad en pallet:
                        </td>
                        <td colspan="4" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            Observaciones:
                        </td>
                    </tr>

                    {{-- VALUES --}}
                    <tr class="h-[16mm]">
                        <td colspan="4" class="border border-black p-[1.2mm] text-center align-middle">
                            {{ $s['subinventory'] ?? '' }}
                        </td>
                        <td colspan="4" class="border border-black p-[1.2mm] text-center align-middle">
                            {{ $s['local'] ?? '' }}
                        </td>
                        <td colspan="3" class="border border-black p-[1.2mm] text-center align-middle">
                            {{ $s['qty_pallet'] ?? '' }}
                        </td>
                        <td colspan="4" class="border border-black p-[1.2mm] align-middle"></td>
                    </tr>

                    {{-- QRS --}}
                    <tr class="h-[18mm]">
                        <td colspan="4" class="border border-black p-[1.2mm] align-middle">
                            <div class="flex h-full items-center justify-center">
                                <div
                                    class="js-qr overflow-hidden"
                                    data-size="68"
                                    data-value="{{ $s['subinventory'] ?? '' }}"
                                    style="width: 17mm; height: 17mm;"
                                ></div>
                            </div>
                        </td>

                        <td colspan="4" class="border border-black p-[1.2mm] align-middle">
                            <div class="flex h-full items-center justify-center">
                                <div
                                    class="js-qr overflow-hidden"
                                    data-size="68"
                                    data-value="{{ $s['local'] ?? '' }}"
                                    style="width: 17mm; height: 17mm;"
                                ></div>
                            </div>
                        </td>

                        <td colspan="3" class="border border-black p-[1.2mm] align-middle">
                            <div class="flex h-full items-center justify-center">
                                <div
                                    class="js-qr overflow-hidden"
                                    data-size="68"
                                    data-value="{{ $s['qty_pallet'] ?? '' }}"
                                    style="width: 17mm; height: 17mm;"
                                ></div>
                            </div>
                        </td>

                        <td colspan="4" class="border border-black p-[1.2mm] align-middle"></td>
                    </tr>

                    {{-- FOOTER TITLES --}}
                    <tr class="h-[10mm]">
                        <td colspan="5" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            LIBERACION IPQC
                        </td>
                        <td colspan="5" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            LIBERACION OQC
                        </td>
                        <td colspan="5" class="border border-black bg-gradient-to-b from-slate-100 to-slate-300 p-[1.2mm] text-center align-middle font-bold">
                            PRODUCTION SUPPORT
                        </td>
                    </tr>

                    {{-- FOOTER BOXES --}}
                    <tr class="h-[36mm]">
                        <td colspan="5" class="border border-black p-[1.2mm] align-middle"></td>
                        <td colspan="5" class="border border-black p-[1.2mm] align-middle"></td>
                        <td colspan="5" class="border border-black p-[1.2mm] align-middle"></td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    @vite('resources/js/app.js')
</body>
</html>