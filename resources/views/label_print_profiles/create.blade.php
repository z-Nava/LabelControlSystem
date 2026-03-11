@extends('layouts.app', ['title' => 'Nuevo print profile'])
@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-semibold">Nuevo print profile</h1>
    @if(session('success'))
        <div class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if(session()->has('printer_detected'))
        <div class="mt-4 rounded-xl border p-3 text-sm {{ session('printer_detected') ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
            Estado de impresora:
            <span class="font-semibold">{{ session('printer_detected') ? 'Detectada (puerto 9100 abierto)' : 'No detectada' }}</span>
        </div>
    @endif
    <form class="mt-6 space-y-4" method="POST" action="{{ route('label_print_profiles.store') }}">
        @include('label_print_profiles._form')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <button
                type="submit"
                formaction="{{ route('label_print_profiles.test_print') }}"
                formmethod="POST"
                class="w-full rounded-xl border border-slate-300 bg-white text-slate-700 py-3 font-semibold hover:bg-slate-50"
            >
                Probar impresión
            </button>
            <button class="w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500">Guardar</button>
        </div>
    </form>
</div>
@endsection
