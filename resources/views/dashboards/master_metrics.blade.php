@extends('layouts.app', ['title' => 'Métricas Hojas Master'])

@section('content')
    <a href="{{ route('dashboard') }}" class="mb-4 inline-flex text-sm font-medium text-slate-600 hover:text-slate-900">← Volver al dashboard Admin</a>
    <section class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5" aria-labelledby="master-print-metrics-title">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h2 id="master-print-metrics-title" class="text-lg font-semibold text-slate-900">Solicitudes de reimpresión y retrabajo Master</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Desde el {{ $masterPrintMetrics['since']->format('d/m/Y') }} · Se cuentan las solicitudes registradas, incluso si la impresión sigue pendiente
                </p>
            </div>
            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 border border-slate-200">Últimos 90 días</span>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-red-200 bg-white p-4">
                <div class="text-xs font-medium text-slate-600">Reimpresiones solicitadas</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-red-700">{{ number_format($masterPrintMetrics['reprint_requests']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-medium text-slate-600">Retrabajos solicitados</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ number_format($masterPrintMetrics['rework_requests']) }}</div>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <span class="text-sm font-medium text-slate-700">Agrupar solicitudes</span>
            <div class="inline-flex rounded-lg border border-slate-300 bg-white p-1 text-sm" aria-label="Agrupar métricas Master">
                <a href="{{ route('admin.master_metrics.index', ['metrics_group' => 'line']) }}"
                   class="rounded-md px-3 py-1.5 {{ $masterPrintMetrics['group_by'] === 'line' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}"
                   @if($masterPrintMetrics['group_by'] === 'line') aria-current="page" @endif>Por línea</a>
                <a href="{{ route('admin.master_metrics.index', ['metrics_group' => 'area']) }}"
                   class="rounded-md px-3 py-1.5 {{ $masterPrintMetrics['group_by'] === 'area' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}"
                   @if($masterPrintMetrics['group_by'] === 'area') aria-current="page" @endif>Por área</a>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div>
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="font-semibold text-slate-900">Más solicitudes de reimpresión</h3>
                    <p class="text-xs text-slate-500">{{ count($masterPrintMetrics['reprint_lines']) }} de {{ $masterPrintMetrics['lines_with_reprints'] }} {{ $masterPrintMetrics['group_by'] === 'area' ? 'áreas' : 'líneas' }}</p>
                </div>
                <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-100 text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold">{{ $masterPrintMetrics['group_by'] === 'area' ? 'Área' : 'Línea / área' }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Veces</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($masterPrintMetrics['reprint_lines'] as $line)
                                <tr>
                                    <th scope="row" class="px-4 py-3 text-left font-medium text-slate-900">
                                        <span class="block whitespace-nowrap">{{ $line['code'] }}</span>
                                        @if($masterPrintMetrics['group_by'] === 'line')
                                            <span class="block text-xs font-normal text-slate-500">{{ $line['area'] }}</span>
                                        @endif
                                    </th>
                                    <td class="px-4 py-3 text-right tabular-nums font-semibold text-red-700">{{ number_format($line['requests']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-4 py-6 text-center text-slate-500">Sin solicitudes de reimpresión en este periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="font-semibold text-slate-900">Más solicitudes de retrabajo</h3>
                    <p class="text-xs text-slate-500">{{ count($masterPrintMetrics['rework_lines']) }} de {{ $masterPrintMetrics['lines_with_reworks'] }} {{ $masterPrintMetrics['group_by'] === 'area' ? 'áreas' : 'líneas' }}</p>
                </div>
                <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-100 text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-semibold">{{ $masterPrintMetrics['group_by'] === 'area' ? 'Área' : 'Línea / área' }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-semibold">Veces</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse ($masterPrintMetrics['rework_lines'] as $line)
                                <tr>
                                    <th scope="row" class="px-4 py-3 text-left font-medium text-slate-900">
                                        <span class="block whitespace-nowrap">{{ $line['code'] }}</span>
                                        @if($masterPrintMetrics['group_by'] === 'line')
                                            <span class="block text-xs font-normal text-slate-500">{{ $line['area'] }}</span>
                                        @endif
                                    </th>
                                    <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-900">{{ number_format($line['requests']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-4 py-6 text-center text-slate-500">Sin solicitudes de retrabajo en este periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">Cada solicitud cuenta una vez, sin importar la cantidad de hojas o copias.</p>
    </section>
@endsection
