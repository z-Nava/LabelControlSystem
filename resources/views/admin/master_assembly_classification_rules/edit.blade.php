@extends('layouts.app', ['title' => 'Editar Master Assembly Classification Rule'])

@section('content')
<div class="bg-white rounded-2xl shadow p-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Editar Master Assembly Classification Rule</h1>
            <p class="mt-1 text-sm text-slate-600">{{ \App\Models\MasterAssemblyClassificationRule::fieldLabel($rule->match_field) }} · {{ $rule->prefix }}</p>
        </div>
        <a href="{{ route('admin.master_assembly_classification_rules.index') }}" class="text-slate-600 hover:text-slate-900">Volver</a>
    </div>

    <form class="mt-6 space-y-4" method="POST" action="{{ route('admin.master_assembly_classification_rules.update', $rule) }}">
        @method('PUT')
        @include('admin.master_assembly_classification_rules._form', ['rule' => $rule])

        <button class="w-full rounded-xl bg-slate-900 text-white py-3 font-semibold hover:bg-slate-800 transition">Actualizar</button>
    </form>
</div>
@endsection
