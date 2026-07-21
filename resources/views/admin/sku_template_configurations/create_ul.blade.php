@extends('layouts.app', ['title' => 'Nueva configuración template UL', 'mainClass' => 'max-w-screen-2xl'])

@section('content')
<div class="bg-white rounded-2xl shadow p-4 sm:p-6 xl:p-8">
    <h1 class="text-2xl font-semibold">Nueva configuración de template por SKU · UL</h1>
    @include('admin.sku_template_configurations._create_market_nav', ['forcedStandard' => 'UL'])

    <form class="mt-6 space-y-4" method="POST" action="{{ route('admin.sku_template_configurations.store') }}">
        @include('admin.sku_template_configurations._form_ul', ['configuration' => $configuration, 'formState' => $formState, 'forcedStandard' => 'UL'])
        <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500">Guardar</button>
    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/zebra/BrowserPrint-3.1.250.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    @vite('resources/js/pages/sku-template-configurations-form.js')
@endpush
