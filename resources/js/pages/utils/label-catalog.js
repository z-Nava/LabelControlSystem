export const normalizePart = (value) => String(value || '').trim().toUpperCase();

export function matchingOptions(options, type, part) {
    const value = normalizePart(part);
    return options.filter((option) => normalizePart(option[type + '_part_number']) === value && value);
}

export function automaticSelection(options, type, part) {
    const matches = part ? matchingOptions(options, type, part) : options;
    if (matches.length === 1) return matches[0];
    return null;
}

export function commonPart(options, type) {
    const parts = new Set(options.map((option) => normalizePart(option[type + '_part_number'])));
    return parts.size === 1 ? [...parts][0] : '';
}

// The hidden ID is only a selection hint. The server validates the Job, active mapping and NP.
export function mountCatalogPicker({ container, partInput, idInput, type, limitToPart = false, allowManual = () => false, onSelect = () => {} }) {
    let options = [];
    let optionsLoaded = false;
    let automaticId = null;
    let currentType = type;
    const wrapper = document.createElement('label');
    wrapper.className = 'catalog-picker block min-w-0 sm:col-span-full md:col-span-full';
    const title = document.createElement('span');
    title.className = 'text-xs font-semibold text-slate-600';
    title.textContent = 'Relación de etiquetas del empaque';
    const select = document.createElement('select');
    select.className = 'mt-1 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm';
    select.setAttribute('aria-label', 'Rating y mercado de la etiqueta');
    const hint = document.createElement('span');
    hint.className = 'mt-1 block text-xs text-slate-600';
    wrapper.append(title, select, hint);
    container.append(wrapper);

    const selected = () => options.find((option) => String(option.id) === idInput.value);
    const column = () => currentType + '_part_number';
    const compatible = (option) => !option[column()] || normalizePart(option[column()]) === normalizePart(partInput.value);
    const setPart = (value) => {
        if (value) {
            partInput.value = value;
            partInput.dataset.catalogAuto = value;
        }
    };
    const refresh = ({ autofill = true } = {}) => {
        if (!optionsLoaded) {
            select.disabled = true;
            select.required = false;
            select.setCustomValidity('');
            hint.textContent = 'Valida el Job para consultar el catálogo.';
            return;
        }
        const manualReprint = allowManual();
        if (manualReprint && automaticId === idInput.value) idInput.value = '';
        autofill = autofill && !partInput.disabled && !manualReprint;
        if (selected() && partInput.value && !compatible(selected())) idInput.value = '';
        if (!selected()) {
            idInput.value = '';
            const candidate = automaticSelection(options, currentType, normalizePart(partInput.value));
            if (!manualReprint && candidate && (partInput.value || autofill)) {
                idInput.value = String(candidate.id);
                automaticId = idInput.value;
                if (!partInput.value && autofill) setPart(candidate[column()]);
            } else if (!partInput.value && autofill && !['serial', 'rating'].includes(currentType)) {
                setPart(commonPart(options, currentType));
            }
        }
        select.replaceChildren(new Option(options.length ? 'Selecciona Rating y mercado…' : 'Sin relación en catálogo · captura manual', ''));
        options.forEach((option) => {
            const part = option[column()] || 'NP pendiente de captura';
            const choice = new Option('Rating ' + option.rating_part_number + ' · ' + option.market + ' · ' + currentType + ': ' + part, String(option.id));
            choice.disabled = limitToPart && Boolean(partInput.value) && !compatible(option);
            select.add(choice);
        });
        select.value = idInput.value;
        select.disabled = partInput.disabled || !options.length;
        select.required = false;
        select.setCustomValidity('');
        const matches = matchingOptions(options, currentType, partInput.value);
        if (!manualReprint && !selected() && matches.length > 1 && ['serial', 'rating'].includes(currentType)) {
            select.required = !partInput.disabled;
            hint.textContent = 'Este NP se comparte: selecciona su Rating de control y mercado.';
        } else if (selected()) {
            const row = selected();
            hint.textContent = 'Rating ' + row.rating_part_number + ' · ' + row.market + ' · Serial ' + (row.serial_part_number || 'pendiente') + ' · Shipping ' + (row.shipping_part_number || 'pendiente') + ' · Inner ' + (row.inner_part_number || 'pendiente');
        } else if (!allowManual() && options.length && partInput.value && !matches.length && options.every((row) => row[column()])) {
            select.setCustomValidity(partInput.disabled ? '' : 'El NP no corresponde al empaque de este Job. Selecciona una relación compatible o usa otro grupo.');
            hint.textContent = 'El NP capturado no corresponde al catálogo de este Job.';
        } else {
            hint.textContent = manualReprint ? 'Selecciona el catálogo solo si corresponde a los originales. Label Room verificará su Rating y periodo original.' : options.length ? 'Elige la relación para completar el NP. Los campos sin NP en el catálogo se capturan manualmente.' : 'Sin datos de catálogo para este Job. Puedes capturar el NP manualmente.';
        }
    };
    const choose = (id) => {
        const row = options.find((option) => String(option.id) === String(id));
        idInput.value = row ? String(row.id) : '';
        automaticId = null;
        if (row) {
            if (!row[column()] && partInput.dataset.catalogAuto === partInput.value) {
                partInput.value = '';
                delete partInput.dataset.catalogAuto;
            }
            setPart(row[column()]);
        }
        refresh({ autofill: false });
        return row;
    };
    select.addEventListener('change', () => {
        const previousId = idInput.value;
        const row = choose(select.value);
        onSelect(row, previousId);
    });
    return {
        select, idInput, partInput, selected,
        choose,
        refresh,
        setOptions(nextOptions, nextType = type, settings = {}) {
            optionsLoaded = true;
            options = Array.isArray(nextOptions) ? nextOptions : [];
            currentType = nextType;
            refresh(settings);
        },
        clear({ clearPart = false } = {}) {
            if (clearPart && (idInput.value || partInput.dataset.catalogAuto === partInput.value)) partInput.value = '';
            delete partInput.dataset.catalogAuto;
            automaticId = null;
            idInput.value = '';
            options = [];
            optionsLoaded = false;
            select.replaceChildren();
            refresh({ autofill: false });
        },
    };
}
