@extends('layouts.app', ['title' => 'Nuevo template Dummy QR'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Dummy QR Templates</p>
            <h1 class="text-2xl font-bold text-slate-900">Nuevo template Dummy QR</h1>
            <p class="mt-1 text-sm text-slate-500">Crea un template con layout visual, conexión de impresora y prueba ZPL.</p>
        </div>
        <a href="{{ route('admin.dummy_qr_templates.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Ver templates
        </a>
    </div>

    <form class="space-y-6" method="POST" action="{{ route('admin.dummy_qr_templates.store') }}">
        @include('admin.dummy_qr_templates._form', ['template' => $template])
        <div class="sticky bottom-4 z-10 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
            <button
                type="submit"
                data-dummy-template-submit
                data-loading-label="Guardando template..."
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 py-3 font-semibold text-white hover:bg-red-500 disabled:cursor-wait disabled:opacity-70"
            >
                <span data-dummy-template-submit-spinner class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                <span data-dummy-template-submit-label>Guardar template Dummy QR</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
    @vite('resources/js/pages/dummy-qr-templates-create.js')
@endpush
