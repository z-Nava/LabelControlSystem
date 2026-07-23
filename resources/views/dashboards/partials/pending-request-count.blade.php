<span
    data-pending-request-count="{{ $module }}"
    data-pending-request-label="{{ $label }}"
    aria-label="{{ $count }} {{ $label }}"
    aria-live="polite"
    @class([
        'inline-flex min-w-7 items-center justify-center rounded-full bg-red-600 px-2 py-1 text-xs font-bold leading-none text-white shadow-sm',
        'hidden' => $count === 0,
    ])
>
    {{ $count }}
</span>
