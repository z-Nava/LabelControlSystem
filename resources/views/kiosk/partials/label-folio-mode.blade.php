<section class="rounded-2xl border border-slate-200 bg-white p-5">
    <label for="folioMode" class="block text-sm font-semibold text-slate-900">Tipo de trabajo solicitado</label>
    <select id="folioMode" name="folio_mode" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5">
        @foreach(\App\Models\LabelRequest::FOLIO_MODES as $value => $label)
            <option value="{{ $value }}" @selected(old('folio_mode', 'new') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <p class="mt-2 text-sm text-slate-600">Para reimprimir, entrega las etiquetas originales físicas a LabelRoom. La líder verificará los folios y la requisición se firmará físicamente.</p>
</section>

