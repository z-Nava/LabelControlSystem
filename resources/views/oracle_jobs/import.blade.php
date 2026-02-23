@extends('layouts.app', ['title' => 'Import Oracle Jobs'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Importar Oracle Jobs</h1>
            <p class="text-slate-600 mt-1">Arrastra un Excel (.xlsx) para llenar/actualizar la tabla oracle_jobs.</p>
        </div>

        <a href="{{ route('oracle_jobs.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form id="uploadForm" class="mt-6" method="POST" action="{{ route('oracle_jobs.import') }}" enctype="multipart/form-data">
        @csrf

        <input id="fileInput" name="file" type="file" accept=".xlsx,.xls" class="hidden" />

        <div id="dropZone"
             class="rounded-2xl border-2 border-dashed border-slate-300 p-10 text-center hover:border-red-600 transition cursor-pointer">
            <div class="text-lg font-semibold text-slate-900">Arrastra tu archivo aquí</div>
            <div class="text-slate-600 mt-1">o haz clic para seleccionar</div>
            <div id="fileName" class="mt-3 text-sm text-slate-500"></div>
        </div>

        <button id="submitBtn"
                type="submit"
                class="mt-5 w-full rounded-xl bg-red-600 text-white py-3 font-semibold hover:bg-red-500 transition disabled:opacity-50 disabled:cursor-not-allowed"
                disabled>
            Cargar a Base de Datos
        </button>
    </form>
</div>

@vite('resources/js/app.js')
@endsection
