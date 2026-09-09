<section class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
    <h2 class="font-semibold text-slate-900">{{ $title ?? 'Notas de la requisición' }}</h2>
    @if(filled($notes))
        <div class="mt-2 whitespace-pre-line break-words text-sm text-slate-700">{{ $notes }}</div>
    @else
        <p class="mt-2 text-sm text-slate-500">Sin notas registradas.</p>
    @endif
</section>
