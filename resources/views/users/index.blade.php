@extends('layouts.app', ['title' => 'Usuarios'])

@section('content')
<div class="space-y-6">
    <section class="rounded-2xl bg-white p-6 shadow">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Usuarios</h1>
                <p class="mt-1 text-slate-600">Administración de usuarios organizada por tipo de acceso.</p>
            </div>

            <a href="{{ route('users.create') }}"
               class="w-fit rounded-xl bg-red-600 px-4 py-2 font-semibold text-white transition hover:bg-red-500">
                + Nuevo usuario
            </a>
        </div>

        @if(session('success'))
            <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <form class="mt-5 flex flex-col gap-2 sm:flex-row" method="GET" action="{{ route('users.index') }}">
            <input name="q" value="{{ $search }}"
                   class="w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600"
                   placeholder="Buscar por no. empleado o nombre..." />
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-white transition hover:bg-slate-800">
                Buscar
            </button>
            @if($search)
                <a href="{{ route('users.index') }}"
                   class="rounded-xl border border-slate-300 px-4 py-2 text-center text-slate-700 transition hover:bg-slate-50">
                    Limpiar
                </a>
            @endif
        </form>
    </section>

    @include('users._role-table', [
        'title' => 'Administradores',
        'description' => 'Usuarios con acceso administrativo al sistema.',
        'role' => 'admin',
        'users' => $adminUsers,
    ])

    @include('users._role-table', [
        'title' => 'Label Room',
        'description' => 'Personal que procesa, imprime y administra requisiciones.',
        'role' => 'label_room',
        'users' => $labelRoomUsers,
    ])

    @include('users._role-table', [
        'title' => 'Kiosk · Producción',
        'description' => 'Personal de producción registrado desde el kiosko.',
        'role' => 'kiosk',
        'users' => $kioskUsers,
    ])
</div>
@endsection
