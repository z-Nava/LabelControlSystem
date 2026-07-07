<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $title ?? 'Label Printing Control System' }}</title>
    <link rel="icon" type="image/png" href="{{ Vite::asset('resources/img/favicon.png') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100">
    <header class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-red-600"></div>
                <div>
                    <div class="font-semibold text-slate-900">Label Printing Control System</div>
                    <div class="text-xs text-slate-500">
                        {{ auth()->user()->name }} • {{ auth()->user()->employee_no }}
                        @if(auth()->user()->shift_label)
                            • {{ auth()->user()->shift_label }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button"
                    onclick="window.history.back()"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-50 transition">
                    Regresar
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-xl bg-slate-900 text-white px-4 py-2 hover:bg-slate-800 transition">
                        Salir
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="{{ $mainClass ?? 'max-w-6xl' }} mx-auto px-4 py-6">
        @yield('content')
    </main>
</body>
</html>
