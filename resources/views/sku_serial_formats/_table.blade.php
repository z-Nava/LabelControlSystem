@props([
    'title',
    'formats',
    'emptyMessage',
    'standard',
])

<div class="mt-6 overflow-x-auto">
    <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ $title }}</h2>

    @php
        $isUl = $standard === 'UL';
        $isEmea = $standard === 'EMEA';
        $isAnz = $standard === 'ANZ';
    @endphp

    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-slate-500 border-b">
                <th class="py-3 pr-3">SKU</th>
                <th class="py-3 pr-3">Descripción</th>
                @if($isUl)
                    <th class="py-3 pr-3">UL Prefix</th>
                    <th class="py-3 pr-3">UL Break</th>
                    <th class="py-3 pr-3">UL Plant</th>
                    <th class="py-3 pr-3">Usa planta</th>
                    <th class="py-3 pr-3">Año/Sem</th>
                    <th class="py-3 pr-3">Unit digits</th>
                @elseif($isEmea)
                    <th class="py-3 pr-3">SAP console</th>
                    <th class="py-3 pr-3">Conformity</th>
                    <th class="py-3 pr-3">Unit digits</th>
                    <th class="py-3 pr-3">Month map</th>
                @else
                    <th class="py-3 pr-3">ANZ Product Prefix</th>
                    <th class="py-3 pr-3">Tool version</th>
                    <th class="py-3 pr-3">Unit digits</th>
                    <th class="py-3 pr-3">Customer tool</th>
                    <th class="py-3 pr-3">QR sep</th>
                    <th class="py-3 pr-3">Print format</th>
                @endif
                <th class="py-3 pr-3">QR payload</th>
                @unless($isEmea)
                    <th class="py-3 pr-3">Reset scope</th>
                @endunless
                <th class="py-3 pr-3">Activo</th>
                <th class="py-3 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($formats as $format)
                <tr>
                    <td class="py-3 pr-3 font-semibold text-slate-900">{{ $format->sku }}</td>
                    <td class="py-3 pr-3">{{ $format->description ?: '-' }}</td>

                    @if($isUl)
                        <td class="py-3 pr-3">{{ $format->ul_prefix ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->ul_serial_break ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->ul_plant_code ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->ul_use_plant_code ? 'Sí' : 'No' }}</td>
                        <td class="py-3 pr-3">{{ $format->year_digits }}/{{ $format->week_digits }}</td>
                        <td class="py-3 pr-3">{{ $format->unit_digits ?? $format->unit_length }}</td>
                    @elseif($isEmea)
                        <td class="py-3 pr-3">{{ $format->emea_prefix ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->emea_conformity_code ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->emea_unit_digits ?? $format->unit_digits ?? $format->unit_length }}</td>
                        <td class="py-3 pr-3">{{ $format->month_letter_map ?: '-' }}</td>
                    @else
                        <td class="py-3 pr-3">{{ $format->anz_product_prefix ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->anz_tool_version ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->anz_unit_digits ?? $format->unit_digits ?? $format->unit_length }}</td>
                        <td class="py-3 pr-3">{{ $format->anz_customer_tool_code ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->anz_qr_separator ?: '-' }}</td>
                        <td class="py-3 pr-3">{{ $format->anz_serial_print_format ?: '-' }}</td>
                    @endif

                    <td class="py-3 pr-3">{{ $format->qr_payload_format ?: '-' }}</td>
                    @unless($isEmea)
                        <td class="py-3 pr-3">{{ $format->reset_scope }}</td>
                    @endunless
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
