<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Master - Ensamble y Empaque</title>

    @vite('resources/css/app.css')

    <style>
        @page {
            size: letter landscape;
            margin: 5mm;
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
            max-width: 269mm;
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
    <section class="sheet overflow-hidden rounded-[2.5mm] border border-black">

        {{-- HEADER --}}
        <div class="grid grid-cols-12 border-b border-black">
            <div class="col-span-3 flex items-center justify-center border-r border-black px-3 py-2">
                <img
                    src="{{ asset('images/LOGO-MILWAUKEE.png') }}"
                    alt="Logo Milwaukee Tool"
                    class="max-h-[12mm] w-auto object-contain"
                >
            </div>

            <div class="col-span-6 flex items-center justify-center border-r border-black px-3 py-2 text-center">
                <div>
                    <div class="text-[6px] font-semibold uppercase tracking-[1.4px] text-slate-500">
                        Master Sheet
                    </div>
                    <div class="text-[16px] font-extrabold leading-none tracking-[0.4px] text-black">
                        PRODUCTO TERMINADO - ENSAMBLE Y EMPAQUE
                    </div>
                </div>
            </div>

            <div class="col-span-3 grid grid-cols-2 text-[9px]">
                <div class="border-r border-b border-black px-2 py-1.5 font-bold">Fecha</div>
                <div class="border-b border-black px-2 py-1.5 text-right font-semibold">{{ $s['date'] ?? '' }}</div>

                <div class="border-r border-black px-2 py-1.5 font-bold">Folio</div>
                <div class="px-2 py-1.5 text-right text-[12px] font-extrabold">{{ $s['folio_no'] ?? '' }}</div>
            </div>
        </div>

        {{-- TOP INFO --}}
        <div class="grid grid-cols-12 border-b border-black">
            <div class="col-span-2 border-r border-black p-2">
                <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Líder</div>
                <div class="mt-1 text-[12px] font-semibold text-black">
                    {{ $s['leader'] ?? '' }}
                </div>
            </div>

            <div class="col-span-1 border-r border-black p-2">
                <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Turno</div>
                <div class="mt-1 text-[12px] font-semibold text-black">
                    {{ $s['shift'] ?? '' }}
                </div>
            </div>

            <div class="col-span-2 border-r border-black p-2">
                <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Línea</div>
                <div class="mt-1 text-[13px] font-semibold text-black">
                    {{ $s['line'] ?? '' }}
                </div>
            </div>

            <div class="col-span-2 border-r border-black p-2">
                <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Modelo</div>
                <div class="mt-1 text-[13px] font-semibold text-black">
                    {{-- {{ $s['model'] ?? '' }} --}}
                </div>
            </div>

            <div class="col-span-2 border-r border-black p-2">
                <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Destino</div>
                <div class="mt-1 text-[11px] font-bold text-black break-words">
                    {{ $s['destination'] ?? ($s['destino'] ?? '') }}
                </div>
            </div>

            <div class="col-span-3 p-2">
                <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Custom PO</div>
                <div class="mt-1 text-[11px] font-semibold text-black break-all">
                    {{ $s['po_number'] ?? '' }}
                </div>
            </div>
        </div>

        {{-- JOBS CON QR --}}
        <div class="grid grid-cols-12 border-b border-black">
            <div class="col-span-6 border-r border-black">
                <div class="grid grid-cols-12">
                    <div class="col-span-8 border-r border-black p-2.5">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Job Ensamble</div>
                        <div class="mt-1 text-[19px] font-extrabold leading-none text-black">
                            {{ $s['job'] ?? '' }}
                        </div>
                    </div>
                    <div class="col-span-4 p-1.5 text-center">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">QR Job Ensamble</div>
                        <div class="qr-box mt-1 flex min-h-[17mm] items-center justify-center">
                            <div class="js-qr h-[14mm] w-[14mm] overflow-hidden"
                                 data-size="60"
                                 data-value="{{ $s['job'] ?? '' }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-6">
                <div class="grid grid-cols-12">
                    <div class="col-span-8 border-r border-black p-2.5">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Job Empaque</div>
                        <div class="mt-1 text-[19px] font-extrabold leading-none text-black">
                            {{ $s['job_packaging'] ?? ($s['job_pack'] ?? '') }}
                        </div>
                    </div>
                    <div class="col-span-4 p-1.5 text-center">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">QR Job Empaque</div>
                        <div class="qr-box mt-1 flex min-h-[17mm] items-center justify-center">
                            <div class="js-qr h-[14mm] w-[14mm] overflow-hidden"
                                 data-size="60"
                                 data-value="{{ $s['job_packaging'] ?? ($s['job_pack'] ?? '') }}"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ENSAMBLE / EMPAQUE CON QR --}}
        <div class="grid grid-cols-12 border-b border-black">
            <div class="col-span-6 border-r border-black">
                <div class="grid grid-cols-12">
                    <div class="col-span-8 border-r border-black p-2.5">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">
                            NP Ensamble
                        </div>
                        <div class="mt-1 text-[19px] font-extrabold leading-none text-black">
                            {{ $s['np'] ?? '' }}
                        </div>
                        <div class="mt-1.5 text-[9px] leading-snug text-slate-700">
                            {{ $s['desc'] ?? '' }}
                        </div>

                        <div class="mt-2 border-t border-dashed border-slate-400 pt-1.5">
                            <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">
                                Lote Ensamble
                            </div>
                            <div class="mt-1 text-[11px] font-extrabold text-black break-all">
                                {{ $s['lote'] ?? '' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-span-4 p-1.5">
                        <div class="text-center">
                            <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">QR NP Ensamble</div>
                            <div class="qr-box mt-1 flex min-h-[16mm] items-center justify-center">
                                <div class="js-qr h-[14mm] w-[14mm] overflow-hidden"
                                     data-size="60"
                                     data-value="{{ $s['np'] ?? '' }}"></div>
                            </div>
                        </div>

                        <div class="mt-2 border-t border-dashed border-slate-400 pt-1.5 text-center">
                            <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">QR Lote Ensamble</div>
                            <div class="qr-box mt-1 flex min-h-[16mm] items-center justify-center">
                                <div class="js-qr h-[14mm] w-[14mm] overflow-hidden"
                                     data-size="60"
                                     data-value="{{ $s['lote'] ?? '' }}"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-6">
                <div class="grid grid-cols-12">
                    <div class="col-span-8 border-r border-black p-2.5">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">
                            NP Empaque
                        </div>
                        <div class="mt-1 text-[19px] font-extrabold leading-none text-black">
                            {{ $s['np_packaging'] ?? '' }}
                        </div>
                        <div class="mt-1.5 text-[9px] leading-snug text-slate-700">
                            {{ $s['desc_packaging'] ?? '' }}
                        </div>

                        <div class="mt-2 border-t border-dashed border-slate-400 pt-1.5">
                            <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">
                                Lote Empaque
                            </div>
                            <div class="mt-1 text-[11px] font-extrabold text-black break-all">
                                {{ $s['lote_packaging'] ?? '' }}
                            </div>
                        </div>

                        <div class="mt-2 border-t border-dashed border-slate-400 pt-1.5">
                            <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">
                                Custom PO
                            </div>
                            <div class="mt-1 text-[10px] font-semibold text-black break-all">
                                {{ $s['po_number'] ?? '' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-span-4 p-1.5">
                        <div class="text-center">
                            <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">QR NP Empaque</div>
                            <div class="qr-box mt-1 flex min-h-[16mm] items-center justify-center">
                                <div class="js-qr h-[14mm] w-[14mm] overflow-hidden"
                                     data-size="60"
                                     data-value="{{ $s['np_packaging'] ?? '' }}"></div>
                            </div>
                        </div>

                        <div class="mt-2 border-t border-dashed border-slate-400 pt-1.5 text-center">
                            <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">QR Lote Empaque</div>
                            <div class="qr-box mt-1 flex min-h-[16mm] items-center justify-center">
                                <div class="js-qr h-[14mm] w-[14mm] overflow-hidden"
                                     data-size="60"
                                     data-value="{{ $s['lote_packaging'] ?? '' }}"></div>
                            </div>
                        </div>

                        <div class="mt-2 border-t border-dashed border-slate-400 pt-1.5 text-center">
                            <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">QR Custom PO</div>
                            <div class="qr-box mt-1 flex min-h-[16mm] items-center justify-center">
                                @if(!empty($s['po_number']))
                                    <div class="js-qr h-[14mm] w-[14mm] overflow-hidden"
                                         data-size="60"
                                         data-value="{{ $s['po_number'] }}"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- UBICACION / CONTROL --}}
        <div class="grid grid-cols-12 border-b border-black">
            <div class="col-span-3 border-r border-black">
                <div class="grid grid-cols-12">
                    <div class="col-span-7 border-r border-black p-2">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Subinventory</div>
                        <div class="mt-1 text-[12px] font-bold text-black">
                            {{ $s['subinventory'] ?? '' }}
                        </div>
                    </div>
                    <div class="col-span-5 p-1.5 text-center">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">QR Subinventory</div>
                        <div class="qr-box mt-1 flex min-h-[15mm] items-center justify-center">
                            <div class="js-qr h-[13mm] w-[13mm] overflow-hidden"
                                 data-size="56"
                                 data-value="{{ $s['subinventory'] ?? '' }}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-3 border-r border-black">
                <div class="grid grid-cols-12">
                    <div class="col-span-7 border-r border-black p-2">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Local</div>
                        <div class="mt-1 text-[12px] font-bold text-black">
                            {{ $s['local'] ?? '' }}
                        </div>
                    </div>
                    <div class="col-span-5 p-1.5 text-center">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">QR Local</div>
                        <div class="qr-box mt-1 flex min-h-[15mm] items-center justify-center">
                            @if(!empty($s['local']))
                                <div class="js-qr h-[13mm] w-[13mm] overflow-hidden"
                                     data-size="56"
                                     data-value="{{ $s['local'] }}"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-3 border-r border-black">
                <div class="grid grid-cols-12">
                    <div class="col-span-7 border-r border-black p-2">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Qty pallet</div>
                        <div class="mt-1 text-[12px] font-bold text-black">
                            {{ $s['qty_pallet'] ?? '' }}
                        </div>
                    </div>
                    <div class="col-span-5 p-1.5 text-center">
                        <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">QR Qty pallet</div>
                        <div class="qr-box mt-1 flex min-h-[15mm] items-center justify-center">
                            @if(!empty($s['qty_pallet']))
                                <div class="js-qr h-[13mm] w-[13mm] overflow-hidden"
                                     data-size="56"
                                     data-value="{{ $s['qty_pallet'] }}"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-3 p-2">
                <div class="text-[7px] font-bold uppercase tracking-wide text-slate-500">Observaciones</div>
                <div class="mt-1 h-[6mm] rounded-[2mm] border border-black px-2 py-1 text-[9px] text-black"></div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="grid grid-cols-12 border-t border-black">
            <div class="col-span-3 border-r border-black">
                <div class="border-b border-black px-2 py-1 text-center text-[8px] font-extrabold uppercase tracking-wide">
                    Liberación IPQC
                </div>
                <div class="h-[20mm]"></div>
            </div>

            <div class="col-span-3 border-r border-black">
                <div class="border-b border-black px-2 py-1 text-center text-[8px] font-extrabold uppercase tracking-wide">
                    Liberación OQC
                </div>
                <div class="h-[20mm]"></div>
            </div>

            <div class="col-span-3 border-r border-black">
                <div class="border-b border-black px-2 py-1 text-center text-[8px] font-extrabold uppercase tracking-wide">
                    Production Support
                </div>
                <div class="h-[20mm]"></div>
            </div>

            <div class="col-span-3">
                <div class="border-b border-black px-2 py-1 text-center text-[8px] font-extrabold uppercase tracking-wide">
                    Almacén
                </div>
                <div class="h-[20mm]"></div>
            </div>
        </div>
    </section>
@endforeach

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@vite('resources/js/app.js')
</body>
</html>