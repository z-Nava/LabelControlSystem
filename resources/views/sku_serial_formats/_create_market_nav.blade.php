<div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
    @foreach(['UL', 'EMEA', 'ANZ'] as $market)
        <a href="{{ route('sku_serial_formats.create', ['standard' => $market]) }}"
           class="rounded-xl border px-4 py-3 text-sm font-semibold transition {{ ($forcedStandard ?? 'UL') === $market ? 'border-red-600 bg-red-50 text-red-700' : 'border-slate-200 text-slate-600 hover:border-red-300 hover:text-red-700' }}">
            Formulario {{ $market }}
        </a>
    @endforeach
</div>
