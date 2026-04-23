@extends('layouts.app', ['title' => 'Editar configuración de template'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-semibold">Editar configuración de template por SKU</h1>
    <form class="mt-6 space-y-4" method="POST" action="{{ route('admin.sku_template_configurations.update', $configuration) }}">
        @method('PUT')
        @include('admin.sku_template_configurations._form', ['configuration' => $configuration, 'formState' => $formState])
        <button class="w-full rounded-xl bg-slate-900 text-white py-3 font-semibold hover:bg-slate-800">Actualizar</button>
    </form>
</div>
@vite('resources/js/app.js')
<script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@endsection
