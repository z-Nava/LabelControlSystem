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
<body class="min-h-screen bg-slate-100">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('kiosk.dashboard') }}" class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-red-600"></div>
                <div>
                    <div class="font-semibold text-slate-900">Kiosko de Producción</div>
                    <div class="text-xs text-slate-500">
                        Número de empleado: {{ session('kiosk_employee_no') }}
                    </div>
                </div>
            </a>

            <form id="kioskLogoutForm" method="POST" action="{{ route('kiosk.logout') }}">
                @csrf
                <button class="rounded-xl bg-slate-900 px-4 py-2 text-white transition hover:bg-slate-800">
                    Terminar sesión
                </button>
            </form>
        </div>
    </header>

    <main class="{{ $mainClass ?? 'max-w-6xl' }} mx-auto px-4 py-6">
        @yield('content')
    </main>

    @stack('scripts')

    <script>
        (() => {
            const logoutForm = document.getElementById('kioskLogoutForm');
            if (!logoutForm) return;

            const timeoutMilliseconds = 5 * 60 * 1000;
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
