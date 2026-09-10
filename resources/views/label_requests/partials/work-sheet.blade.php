<div class="detail-title border-b border-slate-300 bg-slate-50 px-4 py-2 field-label">Trabajo autorizado · {{ \App\Models\LabelRequest::FOLIO_MODES[$labelRequest->folio_mode] }}</div>
<table class="detail-table w-full border-collapse text-sm">
    <thead><tr class="border-b border-slate-300 text-left">
        @foreach(['Tipo / NP', 'Job / Modelo', 'Producción', 'Evidencia', 'Total', 'Folios del / hasta', 'Año / Semana', 'Imprimió / Turno'] as $heading)<th class="border-r border-slate-300">{{ $heading }}</th>@endforeach
    </tr></thead>
    <tbody>
        @foreach($labelRequest->workTasks as $task)
            <tr class="border-b border-slate-200">
                <td class="border-r">{{ ucfirst($task->label_type) }}<br>{{ $task->part_number }}</td>
                <td class="border-r">{{ collect($task->jobs)->map(fn($job) => $job['job_number'].' / '.($job['model'] ?? ''))->implode('; ') }}</td>
                <td class="border-r">{{ $task->quantity }}</td><td class="border-r">{{ $task->evidence_quantity }}@if($task->evidence_folio)<br>Folio {{ $task->evidence_folio }}@endif</td>
                <td class="border-r font-bold">{{ $task->quantity + $task->evidence_quantity }}</td>
                <td class="border-r font-bold">{{ $task->folio_start !== null ? $task->folio_start.' – '.$task->folio_end : 'No aplica' }}</td>
                <td class="border-r">{{ $task->folio_start !== null ? $task->control_year.' / '.$task->control_week : '—' }}</td>
                <td>{{ $task->printed_by_name ?? ($task->assignee?->name ? 'Asignada: '.$task->assignee->name : 'Pendiente') }}<br>{{ $task->printedShift?->code }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

