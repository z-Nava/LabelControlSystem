@extends('layouts.app', ['title' => 'Dashboard Admin'])

@section('content')
    <div class="bg-white rounded-2xl shadow p-6">
        <h1 class="text-2xl font-semibold text-slate-900">Dashboard (Admin)</h1>
        <p class="text-slate-600 mt-1">
            Configuración del sistema, usuarios, templates y perfiles de impresión.
        </p>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('users.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Usuarios</div>
                <div class="text-sm text-slate-600 mt-1">Altas, bajas, roles, estado activo.</div>
            </a>

            <a href="{{ route('label_templates.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Templates (ZPL)</div>
                <div class="text-sm text-slate-600 mt-1">Versiones, layouts, activación.</div>
            </a>

            <a href="{{ route('label_print_profiles.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Print Profiles</div>
                <div class="text-sm text-slate-600 mt-1">Orientación, darkness, speed, X/Y, etc.</div>
            </a>

            <a href="#" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Bitácora</div>
                <div class="text-sm text-slate-600 mt-1">Eventos: print/reprint/rework, cambios.</div>
            </a>

            <a href="{{ route('production_lines.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Production Lines</div>
                <div class="text-sm text-slate-600 mt-1">Catálogo de líneas (MXC007, MXMR003...).</div>
            </a>

            <a href="{{ route('label_skus.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Label SKU Tools</div>
                <div class="text-sm text-slate-600 mt-1">Catálogo SKU ↔ Label PN y estado activo/inactivo.</div>
            </a>

            <a href="{{ route('sku_serial_formats.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">SKU Serial Formats</div>
                <div class="text-sm text-slate-600 mt-1">Definir prefix, serial_break, plant_code, pattern y unit_length por SKU.</div>
            </a>

            <a href="{{ route('oracle_jobs.index') }}"
                class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Oracle Jobs</div>
                <div class="text-sm text-slate-600 mt-1">
                    Fuente central de producción (Excel)
                </div>
            </a>

            <a href="{{ route('oracle_jobs.import_view') }}"
                class="rounded-2xl border p-5 hover:shadow transition">
                <div class="font-semibold">Importar Oracle Jobs</div>
                <div class="text-sm text-slate-600 mt-1">
                    Cargar archivo Excel desde Oracle
                </div>
            </a>
        </div>
    </div>
@endsection
