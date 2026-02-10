@extends('layouts.app', ['title' => 'Detalle Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Requisición Master #{{ $mr->id }}</h1>
            <p class="text-slate-600 mt-1">
                {{ $mr->line?->code }} · Turno {{ $mr->shift?->code }} · {{ $mr->request_date?->format('Y-m-d') }}
            </p>
        </div>
    </div>

    <a href="{{ route('master_requests.print.create', $mr->id) }}"
        class="rounded-xl bg-red-600 text-white px-4 py-2 text-sm font-semibold hover:bg-red-500 transition">
        Imprimir
    </a>

    @if(session('batch_id'))
        <a href="{{ route('master_print_batches.print', session('batch_id')) }}" target="_blank"
        class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">
            Abrir para imprimir
        </a>

        <a href="{{ route('master_print_batches.pdf', session('batch_id')) }}" target="_blank"
        class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50 ml-2">
            Descargar PDF
        </a>
    @endif


    <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border p-4">
            <div class="text-xs text-slate-500">Líder</div>
            <div class="font-semibold">{{ $mr->leader_name }}</div>
        </div>

        <div class="rounded-xl border p-4">
            <div class="text-xs text-slate-500">Jobs</div>
            <div class="font-semibold">Ensamble: {{ $mr->job_assembly ?? '-' }}</div>
            <div class="text-slate-700">Empaque: {{ $mr->job_packaging ?? '-' }}</div>
        </div>

        <div class="rounded-xl border p-4">
            <div class="text-xs text-slate-500">Destino / PO</div>
            <div class="font-semibold">{{ $mr->destination ?? '-' }}</div>
            <div class="text-slate-700">{{ $mr->po_number ?? '-' }}</div>
        </div>
    </div>

    <div class="mt-6">
        <h2 class="font-semibold text-slate-900">Folios</h2>

        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">Folio</th>
                    <th class="py-3 pr-3">Tipo</th>
                    <th class="py-3 pr-3">Qty</th>
                    <th class="py-3 pr-3">Status</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($mr->folios->sortBy('folio_number') as $f)
                    <tr>
                        <td class="py-3 pr-3 font-semibold">{{ str_pad($f->folio_number, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="py-3 pr-3">{{ $f->is_partial ? 'Parcial' : 'Normal' }}</td>
                        <td class="py-3 pr-3">{{ $f->qty_for_folio ?? '-' }}</td>
                        <td class="py-3 pr-3">{{ $f->status }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
