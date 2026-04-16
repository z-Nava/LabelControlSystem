<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>403 | Acceso denegado</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <main class="min-h-screen flex items-center justify-center p-6">
        <section class="w-full max-w-2xl rounded-2xl bg-white shadow border border-slate-200 overflow-hidden">
            <div class="bg-red-600 px-6 py-5 text-white">
                <p class="text-xs uppercase tracking-widest opacity-90">Label Control System</p>
                <h1 class="text-2xl font-semibold mt-1">403 · Acceso denegado</h1>
            </div>

            <div class="p-6 space-y-4">
                <p class="text-sm text-slate-600">
                    Tu usuario no tiene permisos para entrar a esta sección.
                </p>

                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Si necesitas este módulo para tu operación (Master, Seriales, Dummy u Oracle),
                    solicita al administrador que habilite tu acceso en la sección de <strong>Usuarios</strong>.
                </div>

                <div class="flex flex-wrap gap-2 pt-2">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="rounded-xl bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-500 transition">
                            Ir al dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="rounded-xl bg-red-600 px-4 py-2 text-white font-medium hover:bg-red-500 transition">
                            Ir a iniciar sesión
                        </a>
                    @endauth

                    <button type="button"
                            onclick="window.history.back()"
                            class="rounded-xl border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-50 transition">
                        Regresar
                    </button>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
