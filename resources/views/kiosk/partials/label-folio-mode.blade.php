<section class="rounded-2xl border border-slate-200 bg-white p-5">
    <label for="folioMode" class="block text-sm font-semibold text-slate-900">Tipo de trabajo solicitado</label>
    <select id="folioMode" name="folio_mode" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5">
        @foreach(\App\Models\LabelRequest::FOLIO_MODES as $value => $label)
            <option value="{{ $value }}" @selected(old('folio_mode', 'new') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <p class="mt-2 text-sm text-slate-600">Para reimprimir, entrega las etiquetas originales físicas a LabelRoom. En un retrabajo por faltantes, indica la requisición anterior y la cantidad faltante: sus folios permanecen registrados y la nueva solicitud recibirá folios del periodo que libere Label Room.</p>
    <div id="lostReworkFields" class="mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 p-4">
        <p class="text-sm font-semibold text-amber-950">Reposición por faltantes · Serial y Rating juntos</p>
        <p class="mt-1 text-sm text-amber-900">Usa la misma Job y los mismos NP de la requisición original. Captura sólo las piezas faltantes. El turno de arriba identifica quién reporta el faltante.</p>
        <div class="mt-3 grid gap-3 md:grid-cols-2">
            <label class="text-sm font-medium text-slate-800">Número de requisición original
                <input id="sourceLabelRequestId" type="number" name="source_label_request_id" min="1" value="{{ old('source_label_request_id') }}" data-lookup-url="{{ route('kiosk.label_requests.lookup_rework_source') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5" />
            </label>
            <label class="text-sm font-medium text-slate-800">Motivo del faltante y seguimiento
                <textarea id="reworkReason" name="rework_reason" rows="3" minlength="10" maxlength="2000" placeholder="Ejemplo: al cerrar la Job se detectaron 4 juegos faltantes; se solicita reposición." class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5">{{ old('rework_reason') }}</textarea>
            </label>
        </div>
        <div id="reworkSourceSummary" class="mt-3 whitespace-pre-line text-sm text-amber-950" aria-live="polite"></div>
    </div>
</section>

