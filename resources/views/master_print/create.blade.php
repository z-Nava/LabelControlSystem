@extends('layouts.app', ['title' => 'Imprimir Master'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Imprimir Master</h1>
            <p class="text-slate-600 mt-1">
                Requisición #{{ $mr->id }} · {{ $mr->line?->code }} · Turno {{ $mr->shift?->code }}
            </p>
        </div>
       <div class="flex items-center gap-2">
            <a href="{{ route('master_requests.reprints.index', $mr->id) }}"
               class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">
                Historial
            </a>

            <a href="{{ route('master_requests.show', $mr->id) }}"
               class="rounded-xl border px-4 py-2 text-sm hover:bg-slate-50">
                Volver
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="mt-6" method="POST" action="{{ route('master_requests.print.store', $mr) }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="text-sm text-slate-600">Tipo</label>
                <select name="batch_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    <option value="print" @selected(old('batch_type')=='print')>Impresión</option>
                    <option value="reprint" @selected(old('batch_type')=='reprint')>Reimpresión</option>
                    <option value="rework" @selected(old('batch_type')=='rework')>Retrabajo</option>
                </select>
            </div>

            <div>
                <label class="text-sm text-slate-600">Copias</label>
                <input name="copies" type="number" min="1" max="20" value="{{ old('copies', 1) }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2" required>
            </div>

            <div class="md:col-span-1">
                <label class="text-sm text-slate-600">Motivo (obligatorio en reprint/rework)</label>
                <input name="reason" value="{{ old('reason') }}"
                       class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2">
            </div>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="button" id="selectAll"
                    class="rounded-xl border px-3 py-2 text-sm hover:bg-slate-50">
                Seleccionar todos
            </button>

            <button type="button" id="clearAll"
                    class="rounded-xl border px-3 py-2 text-sm hover:bg-slate-50">
                Limpiar
            </button>

            <div class="ml-auto text-sm text-slate-600">
                Tip: imprime por rango seleccionando varios folios.
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500 border-b">
                    <th class="py-3 pr-3">Sel</th>
                    <th class="py-3 pr-3">Folio</th>
                    <th class="py-3 pr-3">Tipo</th>
                    <th class="py-3 pr-3">Qty</th>
                    <th class="py-3 pr-3">Status</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($mr->folios as $f)
                    <tr>
                        <td class="py-3 pr-3">
                            <input type="checkbox" name="folio_ids[]" value="{{ $f->id }}"
                                   class="h-4 w-4 rounded border-slate-300"
                                   @checked(in_array($f->id, old('folio_ids', [])))>
                        </td>
                        <td class="py-3 pr-3 font-semibold">{{ str_pad($f->folio_number, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="py-3 pr-3">{{ $f->is_partial ? 'Parcial' : 'Normal' }}</td>
                        <td class="py-3 pr-3">{{ $f->qty_for_folio ?? '-' }}</td>
                        <td class="py-3 pr-3">
                            <span class="rounded-full px-2 py-1 text-xs
                                {{ $f->status === 'printed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $f->status }}
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition">
                Crear batch e ir a imprimir
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const selectAllBtn = document.getElementById('selectAll');
    const clearAllBtn = document.getElementById('clearAll');

    selectAllBtn.addEventListener('click', () => {
        document.querySelectorAll('input[name="folio_ids[]"]').forEach(cb => cb.checked = true);
    });

    clearAllBtn.addEventListener('click', () => {
        document.querySelectorAll('input[name="folio_ids[]"]').forEach(cb => cb.checked = false);
    });
})();
</script>
@endsection
