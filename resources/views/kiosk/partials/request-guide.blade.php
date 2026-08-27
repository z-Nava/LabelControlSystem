<section class="overflow-hidden rounded-3xl bg-slate-900 text-white shadow-lg ring-1 ring-slate-800">
    <div class="flex flex-col gap-5 px-5 py-5 lg:flex-row lg:items-center lg:justify-between lg:px-7">
        <div class="flex min-w-0 items-start gap-4">
            <div class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-600 sm:flex" aria-hidden="true">
                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h8.5L19.25 7.5v12A1.75 1.75 0 0 1 17.5 21h-11a1.75 1.75 0 0 1-1.75-1.75V5.5A1.75 1.75 0 0 1 6.5 3.75H7Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 3.75V8h4.25M8.5 12h7M8.5 16h5" />
                </svg>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-red-300">Requisición para Label Room</div>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight lg:text-3xl">{{ $title }}</h1>
                <p class="mt-1 max-w-4xl text-sm leading-6 text-slate-300 lg:text-base">{{ $description }}</p>
            </div>
        </div>

        <a href="{{ route('kiosk.dashboard') }}"
           class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-3 font-semibold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.5 6.5-5.5 5.5 5.5 5.5" />
            </svg>
            Volver al menú
        </a>
    </div>

    <div class="border-t border-white/10 bg-white/5 px-5 py-4 lg:px-7">
        <div class="flex flex-col gap-4 2xl:flex-row 2xl:items-center 2xl:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Sigue estos pasos</h2>
                <ol @class([
                    'mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2',
                    'xl:grid-cols-3' => count($steps) === 3,
                    'xl:grid-cols-4' => count($steps) !== 3,
                ])>
                    @foreach($steps as $step)
                        <li class="flex min-h-16 min-w-0 items-start gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-sm font-bold text-slate-900">
                                {{ $loop->iteration }}
                            </span>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold leading-tight text-white">{{ $step['title'] }}</div>
                                <p class="mt-1 text-xs leading-4 text-slate-400">{{ $step['description'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            @if(!empty($preparationItems))
                <details class="group shrink-0 2xl:w-80">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 rounded-xl border border-amber-300/30 bg-amber-300/10 px-4 py-2.5 text-sm font-semibold text-amber-100">
                        Antes de comenzar
                        <span class="transition group-open:rotate-180" aria-hidden="true">⌄</span>
                    </summary>
                    <ul class="mt-2 grid gap-1.5 rounded-xl bg-white p-3 text-sm text-slate-700 shadow-lg">
                        @foreach($preparationItems as $item)
                            <li class="flex gap-2">
                                <span class="font-bold text-emerald-600" aria-hidden="true">✓</span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>

        <p class="mt-3 text-xs text-slate-400" role="note">
            <span class="font-semibold text-amber-200">Importante:</span>
            esta pantalla no imprime ni entrega material; envía la requisición a Label Room para que sea atendida.
        </p>
    </div>
</section>
