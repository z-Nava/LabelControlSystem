<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'Kiosko de requisiciones' }}</title>
    <link rel="icon" type="image/png" href="{{ Vite::asset('resources/img/favicon.png') }}" />
    @vite('resources/css/app.css')
</head>
<body class="kiosk-shell min-h-screen bg-slate-100 text-slate-900">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto flex min-h-20 max-w-[1600px] items-center justify-between gap-4 px-5 py-3 lg:px-8">
            <a href="{{ route('kiosk.dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-600 text-white shadow-sm" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 4.75h14a1.25 1.25 0 0 1 1.25 1.25v8A1.25 1.25 0 0 1 19 15.25H5A1.25 1.25 0 0 1 3.75 14V6A1.25 1.25 0 0 1 5 4.75Z" />
                        <path stroke-linecap="round" d="M8.5 19.25h7M12 15.5v3.5" />
                    </svg>
                </div>
                <div>
                    <div class="font-semibold leading-tight text-slate-900">Kiosko de Producción</div>
                    <div class="truncate text-sm font-medium text-slate-700">
                        {{ $kioskUser->name }} · #{{ $kioskUser->employee_no }}
                    </div>
                    <div class="hidden text-xs text-slate-500 sm:block">
                        {{ $kioskUser->productionLine->code }} · {{ $kioskUser->shift->name }} · {{ $kioskUser->position_label }}
                    </div>
                </div>
            </a>

            <form id="kioskLogoutForm" method="POST" action="{{ route('kiosk.logout') }}">
                @csrf
                <button class="inline-flex min-h-12 items-center gap-2 whitespace-nowrap rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-600 focus:ring-offset-2">
                    <svg viewBox="0 0 24 24" class="hidden h-5 w-5 sm:block" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 5H6.75A1.75 1.75 0 0 0 5 6.75v10.5C5 18.22 5.78 19 6.75 19H10M14.5 8.5 18 12l-3.5 3.5M18 12H9" />
                    </svg>
                    <span class="hidden sm:inline">Terminar sesión</span>
                    <span class="sm:hidden">Salir</span>
                </button>
            </form>
        </div>
    </header>

    <main class="{{ $mainClass ?? 'max-w-[1600px]' }} mx-auto w-full px-5 py-6 lg:px-8 lg:py-8">
        @yield('content')
    </main>

    @stack('scripts')

    <script>
        (() => {
            const logoutForm = document.getElementById('kioskLogoutForm');
            if (!logoutForm) return;

            const timeoutMilliseconds = 2 * 60 * 1000;
            let timeoutId;

            const resetTimeout = () => {
                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(() => logoutForm.submit(), timeoutMilliseconds);
            };

            ['click', 'keydown', 'input', 'change', 'touchstart'].forEach((eventName) => {
                document.addEventListener(eventName, resetTimeout, { passive: true });
            });

            resetTimeout();
        })();
    </script>
</body>
</html>
