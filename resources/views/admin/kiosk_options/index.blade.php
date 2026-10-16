@extends('layouts.app', ['title' => 'Opciones de Kiosk'])

@section('content')
    <div class="rounded-2xl bg-white p-6 shadow">
        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-red-700 hover:text-red-600">← Volver al dashboard</a>
        <h1 class="mt-4 text-2xl font-semibold text-slate-900">Opciones de Kiosk</h1>
        <p class="mt-1 text-slate-600">Define qué puede abrir Producción. Cada cambio se aplica al guardar.</p>

        @if(session('success'))
            <div role="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($kioskOptions as $option => $label)
                <form method="POST" action="{{ route('admin.kiosk_options.update', $option) }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    @csrf
                    @method('PUT')
                    <label for="kiosk-option-{{ $option }}" class="block font-semibold text-slate-900">{{ $label }}</label>
                    <div class="mt-3 flex flex-wrap items-end gap-3">
                        <div class="min-w-48 flex-1">
                            <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-600">Estado</span>
                            <select id="kiosk-option-{{ $option }}" name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500">
                                @foreach($kioskStatuses as $status => $statusLabel)
                                    <option value="{{ $status }}" @selected($kioskOptionStatuses[$option] === $status)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2">Guardar</button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>
@endsection
