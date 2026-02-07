<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login | Label Control</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow p-6">
        <h1 class="text-2xl font-semibold text-slate-900">Iniciar sesión</h1>
        <p class="text-slate-600 mt-1">Acceso por número de empleado.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="mt-6 space-y-4" method="POST" action="{{ route('login.attempt') }}">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700">Employee No</label>
                <input
                    name="employee_no"
                    value="{{ old('employee_no') }}"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                    placeholder="Ej. 0001"
                    autocomplete="username"
                    required
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">
                    Password <span class="text-xs text-slate-500">(solo administradores)</span>
                </label>

                <input
                    type="password"
                    name="password"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                    placeholder="********"
                    autocomplete="current-password"
                    required
                />
            </div>

            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300">
                    Recuérdame
                </label>
            </div>

            <button
                type="submit"
                class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition"
            >
                Entrar
            </button>
        </form>
    </div>
</body>
</html>
