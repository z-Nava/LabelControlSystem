<div class="mt-4 inline-flex rounded-xl border border-slate-200 p-1" role="group" aria-label="Mercado template SKU">
    @foreach(['UL', 'EMEA', 'ANZ'] as $market)
        <a href="{{ route('admin.sku_template_configurations.create_by_standard', ['standard' => $market]) }}"
           class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ ($forcedStandard ?? 'UL') === $market ? 'bg-red-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            {{ $market }}
        </a>
    @endforeach
</div>
