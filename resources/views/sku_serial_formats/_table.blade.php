@props([
    'title',
    'formats',
    'emptyMessage',
    'prefixLabel' => 'Prefix',
    'breakLabel' => 'Break',
    'plantLabel' => 'Plant',
])

<div class="mt-6 overflow-x-auto">
    <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ $title }}</h2>

    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b">
                <th class="py-3 pr-3">SKU</th>
                <th class="py-3 pr-3">Estándar</th>
                <th class="py-3 pr-3">Esquema</th>
                <th class="py-3 pr-3">{{ $prefixLabel }}</th>
                <th class="py-3 pr-3">{{ $breakLabel }}</th>
                <th class="py-3 pr-3">{{ $plantLabel }}</th>
                <th class="py-3 pr-3">Sep</th>
                <th class="py-3 pr-3">Año/Sem</th>
                <th class="py-3 pr-3">Unit</th>
                <th class="py-3 pr-3">Pattern</th>
                <th class="py-3 pr-3">Activo</th>
                <th class="py-3 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($formats as $format)
                <tr>
                    <td class="py-3 pr-3 font-semibold text-slate-900">{{ $format->sku }}</td>
                    <td class="py-3 pr-3">{{ $format->serial_standard ?? 'UL' }}</td>
                    <td class="py-3 pr-3">{{ $format->serial_scheme ?? 'ul_standard' }}</td>
                    <td class="py-3 pr-3">{{ $format->componentPrefix() ?: '-' }}</td>
                    <td class="py-3 pr-3">{{ $format->componentBreak() ?: '-' }}</td>
                    <td class="py-3 pr-3">{{ $format->componentPlantCode() ?: '-' }}</td>
                    <td class="py-3 pr-3">{{ $format->separator === '' ? '∅' : $format->separator }}</td>
                    <td class="py-3 pr-3">
                        {{ $format->include_year ? $format->year_digits : '-' }}/{{ $format->include_week ? $format->week_digits : '-' }}
                    </td>
                    <td class="py-3 pr-3">{{ $format->unit_length }}</td>
                    <td class="py-3 pr-3">{{ $format->pattern ?: 'segmentado' }}</td>
                    <td class="py-3 pr-3">
                        @if($format->is_active)
                            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-green-800">Sí</span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-slate-700">No</span>
                        @endif
                    </td>
                    <td class="py-3 text-right">
                        <div class="inline-flex gap-2">
                            <a href="{{ route('sku_serial_formats.edit', $format) }}" class="rounded-xl border px-3 py-2 hover:shadow transition">Editar</a>
                            <form method="POST" action="{{ route('sku_serial_formats.toggle', $format) }}">
                                @csrf
                                <button class="rounded-xl bg-slate-900 text-white px-3 py-2 hover:bg-slate-800 transition">
                                    {{ $format->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="12" class="py-6 text-center text-slate-500">{{ $emptyMessage }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
