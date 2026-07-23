<section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                    <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    Requisición para Label Room
                </div>

                <h1 class="mt-3 text-2xl font-semibold text-slate-900">{{ $title }}</h1>
                <p class="mt-2 max-w-3xl text-slate-600">{{ $description }}</p>
            </div>

            <a href="{{ route('kiosk.dashboard') }}"
               class="inline-flex shrink-0 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Volver al menú
            </a>
        </div>

        <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="note">
            <span class="font-semibold">Importante:</span>
            esta pantalla no imprime ni entrega material. Solo envía la requisición a Label Room para que sea atendida.
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.65fr)]">
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Sigue estos pasos</h2>

            <ol class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($steps as $step)
                    <li class="flex gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">
                            {{ $loop->iteration }}
                        </span>
                        <div>
                            <div class="font-semibold text-slate-900">{{ $step['title'] }}</div>
                            <p class="mt-1 text-sm leading-5 text-slate-600">{{ $step['description'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <aside class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <h2 class="font-semibold text-blue-950">Antes de comenzar, ten a la mano:</h2>
            <ul class="mt-3 space-y-2 text-sm text-blue-900">
                @foreach($preparationItems as $item)
                    <li class="flex gap-2">
                        <span class="font-bold text-blue-700" aria-hidden="true">✓</span>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
</section>
