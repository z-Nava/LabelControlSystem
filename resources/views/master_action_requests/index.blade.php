@extends('layouts.app', ['title' => 'Solicitudes Master por revisar'])

@section('content')
<div class="rounded-2xl bg-white p-6 shadow">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Solicitudes Master de Kiosk</h1>
            <p class="mt-1 text-slate-600">Confirma o rechaza los retrabajos y las reimpresiones antes de crear una revisión o un lote.</p>
        </div>
        <a href="{{ route('master_requests.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-center text-sm hover:bg-slate-50">Requisiciones Master</a>
    </div>

    <nav class="mt-6 flex flex-wrap gap-2" aria-label="Estado de solicitudes">
        @foreach(['pending' => 'Pendientes', 'approved' => 'Confirmadas', 'rejected' => 'Rechazadas', 'all' => 'Todas'] as $value => $label)
            <a href="{{ route('master_action_requests.index', ['status' => $value]) }}"
               @class(['rounded-xl border px-4 py-2 text-sm font-semibold', 'border-slate-900 bg-slate-900 text-white' => $status === $value, 'border-slate-300 text-slate-700 hover:bg-slate-50' => $status !== $value])>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-3 py-3">Solicitud</th>
                    <th class="px-3 py-3">Tipo</th>
                    <th class="px-3 py-3">Requisición origen</th>
                    <th class="px-3 py-3">Solicitó</th>
                    <th class="px-3 py-3">Fecha</th>
                    <th class="px-3 py-3">Estado</th>
                    <th class="px-3 py-3">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($actions as $action)
                    <tr>
                        <td class="px-3 py-3 font-semibold">#{{ $action->id }}</td>
                        <td class="px-3 py-3">{{ $action->typeLabel() }}</td>
                        <td class="px-3 py-3">#{{ $action->master_request_id }}<div class="text-xs text-slate-500">{{ $action->masterRequest?->job_assembly ?: $action->masterRequest?->job_packaging ?: '—' }}</div></td>
                        <td class="px-3 py-3">{{ $action->requested_by_name }}</td>
                        <td class="px-3 py-3">{{ $action->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-3">{{ ['pending' => 'Pendiente', 'approved' => 'Confirmada', 'rejected' => 'Rechazada'][$action->status] ?? $action->status }}</td>
                        <td class="px-3 py-3"><a href="{{ route('master_action_requests.show', $action) }}" class="font-semibold text-red-600 hover:underline">Revisar →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">No hay solicitudes con este estado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $actions->links() }}</div>
</div>
@endsection
