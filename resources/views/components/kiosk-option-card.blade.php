@props(['option', 'href', 'action', 'accent' => 'red'])

<a
    @if($option['available']) href="{{ $href }}" @else aria-disabled="true" @endif
    @class([
        'group flex min-h-64 flex-col rounded-3xl border p-6 shadow-sm',
        'border-amber-200 bg-amber-50/40' => $accent === 'amber',
        'border-slate-200 bg-white' => $accent !== 'amber',
        'transition hover:-translate-y-1 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2' => $option['available'],
        'hover:border-amber-400 focus:ring-amber-600' => $option['available'] && $accent === 'amber',
        'hover:border-blue-300 focus:ring-blue-600' => $option['available'] && $accent === 'blue',
        'hover:border-red-300 focus:ring-red-600' => $option['available'] && $accent !== 'amber' && $accent !== 'blue',
        'opacity-65' => ! $option['available'],
    ])
>
    {{ $slot }}

    @if($option['available'])
        <div @class([
            'mt-auto pt-5 font-semibold',
            'text-amber-700 group-hover:text-amber-600' => $accent === 'amber',
            'text-blue-600 group-hover:text-blue-500' => $accent === 'blue',
            'text-red-600 group-hover:text-red-500' => $accent !== 'amber' && $accent !== 'blue',
        ])>{{ $action }} →</div>
    @else
        <div class="mt-auto pt-5">
            <span class="inline-flex rounded-full border border-slate-300 bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700">
                {{ $option['statusLabel'] }}
            </span>
        </div>
    @endif
</a>
