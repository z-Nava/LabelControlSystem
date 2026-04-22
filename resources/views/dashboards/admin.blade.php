@extends('layouts.app', ['title' => 'Dashboard Admin'])

@section('content')
    <div class="bg-white rounded-2xl shadow p-6">
        <h1 class="text-2xl font-semibold text-slate-900">Dashboard (Admin)</h1>
        <p class="text-slate-600 mt-1">
            Módulos organizados por responsabilidad para operación y configuración.
        </p>

        <div class="mt-6 space-y-8">
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 text-slate-700 text-sm">A</span>
                    <h2 class="text-lg font-semibold text-slate-900">Administración</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <a href="{{ route('users.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                        <div class="font-semibold">Usuarios</div>
                        <div class="text-sm text-slate-600 mt-1">Altas, bajas, roles, estado activo.</div>
                    </a>

                    <a href="{{ route('production_lines.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                        <div class="font-semibold">Production Lines</div>
                        <div class="text-sm text-slate-600 mt-1">Catálogo de líneas (MXC007, MXMR003...).</div>
                    </a>

                    
                </div>
            </section>

            <section>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-100 text-red-700 text-sm">M</span>
                    <h2 class="text-lg font-semibold text-slate-900">Masters</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

                    <a href="{{ route('stock_locators.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                        <div class="font-semibold">Locals by Oracle Line</div>
                        <div class="text-sm text-slate-600 mt-1">Mapeo de STOCK_LOCATOR → SUBINVENTORY para masters.</div>
                    </a>
                </div>
            </section>

            <section>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-sm">S</span>
                    <h2 class="text-lg font-semibold text-slate-900">Seriales</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <a href="{{ route('label_skus.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                        <div class="font-semibold">Label SKU Tools</div>
                        <div class="text-sm text-slate-600 mt-1">Catálogo SKU ↔ Label PN y estado activo/inactivo.</div>
                    </a>

                    <a href="{{ route('sku_serial_formats.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                        <div class="font-semibold">SKU Serial Formats</div>
                        <div class="text-sm text-slate-600 mt-1">Definir segmentos UL/EMEA/ANZ y formatos serial.</div>
                    </a>

                    <a href="{{ route('admin.sku_template_configurations.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                        <div class="font-semibold">Templates por SKU</div>
                        <div class="text-sm text-slate-600 mt-1">Templates ZPL + Print Profiles en una vista por SKU/PN.</div>
                    </a>
                </div>
            </section>
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-purple-100 text-purple-700 text-sm">D</span>
                    <h2 class="text-lg font-semibold text-slate-900">Dummies QR</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <a href="{{ route('admin.dummy_qr_templates.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                        <div class="font-semibold">Templates Dummy QR (RMT/RW)</div>
                        <div class="text-sm text-slate-600 mt-1">Configura posiciones FG/JOB/Consecutivo y prueba Zebra.</div>
                    </a>
                </div>
            </section>

            <section>
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-sm">O</span>
                    <h2 class="text-lg font-semibold text-slate-900">Oracle</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <a href="{{ route('oracle_jobs.index') }}" class="rounded-2xl border p-5 hover:shadow transition">
                        <div class="font-semibold">Oracle Jobs</div>
                        <div class="text-sm text-slate-600 mt-1">Fuente central de producción (Excel).</div>
                    </a>

                    <a href="{{ route('oracle_jobs.import_view') }}" class="rounded-2xl border p-5 hover:shadow transition">
                        <div class="font-semibold">Importar Oracle Jobs</div>
                        <div class="text-sm text-slate-600 mt-1">Cargar archivo Excel desde Oracle.</div>
                    </a>
                </div>
            </section>
        </div>
    </div>
@endsection
