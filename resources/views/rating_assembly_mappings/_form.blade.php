@csrf

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div>
        <label for="rating_part_number" class="block text-sm font-medium text-slate-700">NP Rating</label>
        <input id="rating_part_number" name="rating_part_number" maxlength="80" required
               value="{{ old('rating_part_number', $mapping->rating_part_number ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="055738001" />
        @error('rating_part_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="assembly_part_number" class="block text-sm font-medium text-slate-700">Ensamble / Empaque 018</label>
        <input id="assembly_part_number" name="assembly_part_number" maxlength="80" required
               value="{{ old('assembly_part_number', $mapping->assembly_part_number ?? '') }}"
               class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
               placeholder="3692-28S" />
        @error('assembly_part_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="market" class="block text-sm font-medium text-slate-700">Mercado</label>
        <select id="market" name="market" required
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-600">
            <option value="">Selecciona...</option>
            @foreach($markets as $market)
                <option value="{{ $market }}" @selected(old('market', $mapping->market ?? \App\Support\SerialStandards::UL) === $market)>{{ $market }}</option>
            @endforeach
        </select>
        @error('market') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    @foreach(['serial_part_number' => ['Serial', '950410000'], 'shipping_part_number' => ['Shipping', '950143000'], 'inner_part_number' => ['Inner', '950405000']] as $field => [$label, $placeholder])
        <div>
            <label for="{{ $field }}" class="block text-sm font-medium text-slate-700">NP {{ $label }} <span class="font-normal text-slate-500">(opcional)</span></label>
            <input id="{{ $field }}" name="{{ $field }}" maxlength="80"
                   value="{{ old($field, $mapping->{$field} ?? '') }}"
                   class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-red-600"
                   placeholder="{{ $placeholder }}" />
            @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endforeach

    <div class="md:col-span-3">
        <input type="hidden" name="active" value="0" />
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="active" value="1" class="rounded border-slate-300"
                   @checked(old('active', $mapping->active ?? true)) />
            Relación activa
        </label>
        @error('active') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
