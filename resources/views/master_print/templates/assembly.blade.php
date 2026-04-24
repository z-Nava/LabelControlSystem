<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Master - Ensamble</title>

    @vite('resources/css/app.css')

    <style>
        @page {
            size: letter landscape;
            margin: 6mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .sheet {
            width: 100%;
            max-width: 267mm;
            min-height: auto;
            margin: 0 auto;
        }

        .qr-box > div,
        .qr-box canvas,
        .qr-box img {
            margin: 0 auto !important;
        }

        @media screen {
            body {
                background: #e5e7eb;
                padding: 10px;
            }

            .sheet {
                background: #fff;
                box-shadow: 0 8px 24px rgba(0,0,0,.08);
            }
        }

        @media print {
            body {
                background: #fff !important;
                padding: 0;
            }

            .sheet {
                max-width: none;
                min-height: auto;
                box-shadow: none !important;
                break-before: page;
            }

            .sheet:first-child {
                break-before: auto;
            }
        }
    </style>
</head>
<body
    class="m-0"
    data-render-qrs="1"
    data-auto-print="{{ ($mode ?? null) === 'print' ? '1' : '0' }}"
>
@foreach($sheets as $s)
    <section class="sheet overflow-hidden rounded-[3mm] border border-black">
        {{-- HEADER --}}
        <div class="grid grid-cols-12 border-b border-black">
            <div class="col-span-3 flex items-center justify-center border-r border-black px-4 py-2.5">
                <img
                    src="{{ asset('images/LOGO-MILWAUKEE.png') }}"
                    alt="Logo Milwaukee Tool"
                    class="max-h-[14mm] w-auto object-contain"
                >
            </div>

            <div class="col-span-6 flex items-center justify-center border-r border-black px-4 py-2.5 text-center">
                <div>
                    <div class="text-[7px] font-semibold uppercase tracking-[1.6px] text-slate-500">
                        Master Sheet
                    </div>
                    <div class="text-[18px] font-extrabold leading-none tracking-[0.6px] text-black">
                        PRODUCTO TERMINADO - ENSAMBLE
                    </div>
                </div>
            </div>

            <div class="col-span-3 grid grid-cols-2 text-[15px]">
                <div class="border-r border-b border-black px-2 py-2 font-bold">Fecha</div>
                <div class="border-b border-black px-2 py-2 text-right font-semibold">{{ $s['date'] ?? '' }}</div>

                <div class="border-r border-black px-2 py-2 font-bold">Folio</div>
                <div class="px-2 py-2 text-right text-[14px] font-extrabold">{{ $s['folio_no'] ?? '' }}</div>
            </div>
        </div>

        {{-- CONTENT --}}
        <div class="grid grid-cols-12">
            {{-- LEFT SIDE --}}
            <div class="col-span-8 border-r border-black">
                {{-- top info --}}
                <div class="grid grid-cols-4 border-b border-black">
                    <div class="border-r border-black p-2.5">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Líder</div>
                        <div class="mt-1 text-[14px] font-semibold text-black">
                            {{ $s['leader'] ?? '' }}
                        </div>
                    </div>

                    <div class="border-r border-black p-2.5">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Turno</div>
                        <div class="mt-1 text-[14px] font-semibold text-black">
                            {{ $s['shift'] ?? '' }}
                        </div>
                    </div>

                    <div class="border-r border-black p-2.5">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Línea</div>
                        <div class="mt-1 text-[14px] font-semibold text-black">
                            {{ $s['line'] ?? '' }}
                        </div>
                    </div>

                    <div class="p-2.5">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Lote</div>
                        <div class="mt-1 text-[16px] font-extrabold text-black">
                            {{ $s['lote'] ?? '' }}
                        </div>
                    </div>
                </div>

                <div class="border-b border-black p-3">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                        Modelo
                    </div>
                    <div class="mt-1.5 text-[24px] font-extrabold leading-none text-black">
                        {{ $s['model'] ?? '' }}
                    </div>
                </div>

                {{-- NP / JOB --}}
                <div class="grid grid-cols-12 border-b border-black">
                    <div class="col-span-8 border-r border-black p-3">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                            NP Ensamble
                        </div>
                        <div class="mt-1.5 text-[24px] font-extrabold leading-none text-black">
                            {{ $s['np'] ?? '' }}
                        </div>
                        <div class="mt-2 min-h-[10mm] border-t border-dashed border-slate-400 pt-1.5 text-[10px] leading-snug text-slate-700">
                            {{ $s['desc'] ?? '' }}
                        </div>
                    </div>

                    <div class="col-span-4 p-3">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                            Job
                        </div>
                        <div class="mt-1.5 text-[22px] font-extrabold leading-none text-black">
                            {{ $s['job'] ?? '' }}
                        </div>

                        <div class="mt-3 text-[10px] font-bold uppercase tracking-wide text-slate-500">
                            Resumen
                        </div>
                        <div class="mt-1.5 space-y-0.5 text-[10px] text-black">
                            <div><span class="font-bold">Subinventory:</span> {{ $s['subinventory'] ?? '' }}</div>
                            <div><span class="font-bold">Local:</span> {{ $s['local'] ?? '' }}</div>
                            <div><span class="font-bold">Qty pallet:</span> {{ $s['qty_pallet'] ?? '' }}</div>
                        </div>
                    </div>
                </div>

                {{-- data row + observaciones compactas --}}
                <div class="grid grid-cols-12 border-b border-black">
                    <div class="col-span-4 border-r border-black p-2.5">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Subinventory</div>
                        <div class="mt-1.5 text-[15px] font-bold text-black">
                            {{ $s['subinventory'] ?? '' }}
                        </div>
                    </div>

                    <div class="col-span-4 border-r border-black p-2.5">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Local</div>
                        <div class="mt-1.5 text-[15px] font-bold text-black">
                            {{ $s['local'] ?? '' }}
                        </div>
                    </div>

                    <div class="col-span-4 p-2.5">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Cantidad en pallet</div>
                        <div class="mt-1.5 text-[15px] font-bold text-black">
                            {{ $s['qty_pallet'] ?? '' }}
                        </div>
                    </div>
                </div>

                <div class="border-b border-black px-2.5 py-2">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Observaciones</div>
                </div>
            </div>

            {{-- RIGHT SIDE / QR PANEL --}}
            <div class="col-span-4">
                <div class="border-b border-black px-3 py-1.5 text-center">
                    <div class="text-[12px] font-extrabold uppercase tracking-[1px] text-black">
                        Panel de Códigos
                    </div>
                </div>

                <div class="grid grid-cols-2">
                    <div class="border-r border-b border-black p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Job</div>
                        <div class="qr-box mt-1.5 flex min-h-[23mm] items-center justify-center">
                            <div
                                class="js-qr h-[20mm] w-[20mm] overflow-hidden"
                                data-size="82"
                                data-value="{{ $s['job'] ?? '' }}"
                            ></div>
                        </div>
                        <div class="mt-1.5 break-all text-[9px] font-semibold text-black">
                            {{ $s['job'] ?? '' }}
                        </div>
                    </div>

                    <div class="border-b border-black p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">NP Ensamble</div>
                        <div class="qr-box mt-1.5 flex min-h-[23mm] items-center justify-center">
                            <div
                                class="js-qr h-[20mm] w-[20mm] overflow-hidden"
                                data-size="82"
                                data-value="{{ $s['np'] ?? '' }}"
                            ></div>
                        </div>
                        <div class="mt-1.5 break-all text-[9px] font-semibold text-black">
                            {{ $s['np'] ?? '' }}
                        </div>
                    </div>

                    <div class="border-r border-b border-black p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Lote</div>
                        <div class="qr-box mt-1.5 flex min-h-[23mm] items-center justify-center">
                            <div
                                class="js-qr h-[20mm] w-[20mm] overflow-hidden"
                                data-size="82"
                                data-value="{{ $s['lote'] ?? '' }}"
                            ></div>
                        </div>
                        <div class="mt-1.5 break-all text-[9px] font-semibold text-black">
                            {{ $s['lote'] ?? '' }}
                        </div>
                    </div>

                    <div class="border-b border-black p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Subinventory</div>
                        <div class="qr-box mt-1.5 flex min-h-[23mm] items-center justify-center">
                            <div
                                class="js-qr h-[20mm] w-[20mm] overflow-hidden"
                                data-size="82"
                                data-value="{{ $s['subinventory'] ?? '' }}"
                            ></div>
                        </div>
                        <div class="mt-1.5 break-all text-[9px] font-semibold text-black">
                            {{ $s['subinventory'] ?? '' }}
                        </div>
                    </div>

                    <div class="border-r border-black p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Local</div>
                        <div class="qr-box mt-1.5 flex min-h-[23mm] items-center justify-center">
                            <div
                                class="js-qr h-[20mm] w-[20mm] overflow-hidden"
                                data-size="82"
                                data-value="{{ $s['local'] ?? '' }}"
                            ></div>
                        </div>
                        <div class="mt-1.5 break-all text-[9px] font-semibold text-black">
                            {{ $s['local'] ?? '' }}
                        </div>
                    </div>

                    <div class="p-2.5 text-center">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Cantidad pallet</div>
                        <div class="qr-box mt-1.5 flex min-h-[23mm] items-center justify-center">
                            <div
                                class="js-qr h-[20mm] w-[20mm] overflow-hidden"
                                data-size="82"
                                data-value="{{ $s['qty_pallet'] ?? '' }}"
                            ></div>
                        </div>
                        <div class="mt-1.5 break-all text-[9px] font-semibold text-black">
                            {{ $s['qty_pallet'] ?? '' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FOOTER SIGNATURES --}}
        <div class="grid grid-cols-3 border-t border-black">
            <div class="border-r border-black">
                <div class="border-b border-black px-3 py-1 text-center text-[9px] font-extrabold uppercase tracking-wide">
                    Liberación IPQC
                </div>
                <div class="h-[40mm]"></div>
            </div>

            <div class="border-r border-black">
                <div class="border-b border-black px-3 py-1 text-center text-[9px] font-extrabold uppercase tracking-wide">
                    Liberación OQC
                </div>
                <div class="h-[40mm]"></div>
            </div>

            <div>
                <div class="border-b border-black px-3 py-1 text-center text-[9px] font-extrabold uppercase tracking-wide">
                    Production Support
                </div>
                <div class="h-[40mm]"></div>
            </div>
        </div>
    </section>
@endforeach

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@vite('resources/js/app.js')
</body>
</html>