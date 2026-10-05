<span
    aria-label="{{ $count }} {{ $label }}"
    @class([
        'inline-flex min-w-7 items-center justify-center rounded-full bg-red-600 px-2 py-1 text-xs font-bold leading-none text-white shadow-sm',
        'hidden' => $count === 0,
    ])
>
    {{ $count }}
</span>
