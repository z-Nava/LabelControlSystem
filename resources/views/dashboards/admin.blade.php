@extends('layouts.app', ['title' => 'Dashboard Admin'])

@section('content')
    <div class="bg-white rounded-2xl shadow p-6">
        <h1 class="text-2xl font-semibold text-slate-900">Dashboard (Admin)</h1>
        <p class="text-slate-600 mt-1">
            Configuración del sistema, usuarios, templates y perfiles de impresión.
        </p>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="#" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Usuarios</div>
                <div class="text-sm text-slate-600 mt-1">Altas, bajas, roles, estado activo.</div>
            </a>

            <a href="#" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Templates (ZPL)</div>
                <div class="text-sm text-slate-600 mt-1">Versiones, layouts, activación.</div>
            </a>

            <a href="#" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Print Profiles</div>
                <div class="text-sm text-slate-600 mt-1">Orientación, darkness, speed, X/Y, etc.</div>
            </a>

            <a href="#" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Bitácora</div>
                <div class="text-sm text-slate-600 mt-1">Eventos: print/reprint/rework, cambios.</div>
            </a>
        </div>
    </div>
@endsection
