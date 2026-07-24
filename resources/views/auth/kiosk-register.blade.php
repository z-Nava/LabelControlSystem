<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Completar perfil | Kiosko de Producción</title>
    <link rel="icon" type="image/png" href="{{ Vite::asset('resources/img/favicon.png') }}" />
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-100 p-4 sm:flex sm:items-center sm:justify-center">
    <div class="mx-auto w-full max-w-2xl rounded-3xl bg-white p-6 shadow-xl sm:p-8">
        <div class="flex items-center gap-4">
            <div class="h-14 w-14 shrink-0 rounded-2xl bg-red-600"></div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-red-600">Primer acceso</p>
                <h1 class="text-3xl font-semibold text-slate-900">Completa tu perfil</h1>
            </div>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Número de empleado</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">{{ $employeeNo }}</p>
        </div>

        <p class="mt-5 text-slate-600">
            Sólo tendrás que registrar esta información una vez. Verifica los datos antes de continuar.
        </p>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <p class="font-semibold">Revisa la información capturada:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="mt-6 space-y-5" method="POST" action="{{ route('kiosk.register.store') }}">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Nombre completo</label>
                <input
                    id="name"
                    name="name"
                    value="{{ old('name', $user?->name) }}"
                    class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-600"
                    autocomplete="name"
                    minlength="3"
                    maxlength="120"
                    autofocus
                    required
                />
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="production_line_id" class="block text-sm font-medium text-slate-700">Línea de producción</label>
                    <select
                        id="production_line_id"
                        name="production_line_id"
                        class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-600"
                        required
                    >
                        <option value="">Selecciona una línea</option>
                        @foreach ($productionLines->groupBy('line_type') as $lineType => $lines)
                            <optgroup label="{{ $lineType }}">
                                @foreach ($lines as $line)
                                    <option
                                        value="{{ $line->id }}"
                                        @selected(old('production_line_id', $user?->production_line_id) == $line->id)
                                    >
                                        {{ $line->code }} — {{ $line->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('production_line_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="shift_id" class="block text-sm font-medium text-slate-700">Turno</label>
                    <select
                        id="shift_id"
                        name="shift_id"
                        class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-600"
                        required
                    >
                        <option value="">Selecciona un turno</option>
                        @foreach ($shifts as $shift)
                            <option value="{{ $shift->id }}" @selected(old('shift_id', $user?->shift_id) == $shift->id)>
                                {{ $shift->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('shift_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <fieldset>
                <legend class="block text-sm font-medium text-slate-700">Puesto</legend>
                <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ($positions as $value => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-300 px-4 py-3 transition hover:border-red-300 hover:bg-red-50">
                            <input
                                type="radio"
                                name="position"
                                value="{{ $value }}"
                                class="border-slate-300 text-red-600 focus:ring-red-600"
                                @checked(old('position', $user?->position) === $value)
                                required
                            />
                            <span class="font-medium text-slate-800">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('position')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row">
                <a
                    href="{{ route('kiosk.login') }}"
                    class="rounded-2xl border border-slate-300 px-5 py-3 text-center font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Usar otro número
                </a>
                <button
                    type="submit"
                    class="flex-1 rounded-2xl bg-red-600 px-5 py-3 font-semibold text-white transition hover:bg-red-500"
                >
                    Guardar y entrar
                </button>
            </div>
        </form>
    </div>
</body>
</html>
