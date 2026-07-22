<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kiosko de Producción | Label Control</title>
    <link rel="icon" type="image/png" href="{{ Vite::asset('resources/img/favicon.png') }}" />
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-lg rounded-3xl bg-white p-8 shadow-xl">
        <div class="flex items-center gap-4">
            <div class="h-14 w-14 rounded-2xl bg-red-600"></div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-red-600">Producción</p>
                <h1 class="text-3xl font-semibold text-slate-900">Kiosko de requisiciones</h1>
            </div>
        </div>

        <p class="mt-6 text-slate-600">Ingresa o escanea tu número de empleado para continuar.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form class="mt-6 space-y-5" method="POST" action="{{ route('kiosk.login.attempt') }}">
            @csrf

            <div>
                <label for="employee_no" class="block text-sm font-medium text-slate-700">Número de empleado</label>
                <input
                    id="employee_no"
                    name="employee_no"
                    value="{{ old('employee_no') }}"
                    class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-4 text-center text-2xl tracking-wider focus:outline-none focus:ring-2 focus:ring-red-600"
                    placeholder="Ej. 12345"
                    autocomplete="off"
                    inputmode="numeric"
                    minlength="3"
                    maxlength="5"
                    pattern="[0-9]{3,5}"
                    autofocus
                    required
                />
            </div>

            <button type="submit" class="w-full rounded-2xl bg-red-600 py-4 text-lg font-semibold text-white transition hover:bg-red-500">
                Continuar
            </button>
        </form>
    </div>
</body>
</html>
