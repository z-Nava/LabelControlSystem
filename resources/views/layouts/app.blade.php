<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title ?? 'Label Control System' }}</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-100">
    <header class="bg-white border-b">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-red-600"></div>
                <div>
                    <div class="font-semibold text-slate-900">Label Control System</div>
                    <div class="text-xs text-slate-500">
                        {{ auth()->user()->name }} • {{ auth()->user()->employee_no }}
                        @if(auth()->user()->shift_label)
                            • {{ auth()->user()->shift_label }}
                        @endif
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-xl bg-slate-900 text-white px-4 py-2 hover:bg-slate-800 transition">
                    Salir
                </button>
            </form>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
        @yield('content')
    </main>
</body>
</html>
