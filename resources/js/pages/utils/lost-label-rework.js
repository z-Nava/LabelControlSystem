export function mountLostLabelReworkFields(form, onChange = () => {}) {
    const mode = form.querySelector('#folioMode');
    const fields = form.querySelector('#lostReworkFields');
    const source = form.querySelector('#sourceLabelRequestReference');
    const sourceId = form.querySelector('#sourceLabelRequestId');
    const reason = form.querySelector('#reworkReason');
    const summary = form.querySelector('#reworkSourceSummary');
    const isStandard = form.id === 'kioskLabelRequestCreate';
    let lookupTimer;
    let lookupVersion = 0;

    const unlockStandardTypes = () => {
        if (!isStandard) return;

        form.querySelectorAll('[data-rework-label-type]').forEach((input) => input.remove());
        ['includeSerial', 'includeRating', 'includeInner', 'includeShipping'].forEach((id) => {
            const input = form.querySelector(`#${id}`);
            input.disabled = false;
            input.closest('[data-label-type-card]')?.removeAttribute('aria-disabled');
        });
    };

    const applyStandardTypes = (types) => {
        if (!isStandard) return;

        const selected = new Set(types.map((type) => String(type).toLowerCase()));
        const inputs = {
            serial: form.querySelector('#includeSerial'),
            rating: form.querySelector('#includeRating'),
            inner: form.querySelector('#includeInner'),
            shipping: form.querySelector('#includeShipping'),
        };

        form.querySelectorAll('[data-rework-label-type]').forEach((input) => input.remove());

        Object.entries(inputs).forEach(([type, input]) => {
            const checked = selected.has(type);
            if (input.checked !== checked) {
                input.checked = checked;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }

            input.disabled = true;
            input.closest('[data-label-type-card]')?.setAttribute('aria-disabled', 'true');

            if (checked) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = input.name;
                hidden.value = input.value;
                hidden.dataset.reworkLabelType = type;
                form.append(hidden);
            }
        });
    };

    const lookupSource = () => {
        clearTimeout(lookupTimer);
        const version = ++lookupVersion;
        source.value = source.value.trim().toUpperCase();
        sourceId.value = '';
        source.setCustomValidity('');
        if (mode.value !== 'lost_rework' || !source.value) {
            summary.textContent = '';
            onChange(null);
            return;
        }

        applyStandardTypes([]);
        source.setCustomValidity('Espera a que termine la consulta de la requisición original.');
        summary.textContent = 'Consultando la requisición original…';
        lookupTimer = setTimeout(async () => {
            try {
                const url = new URL(source.dataset.lookupUrl, window.location.origin);
                url.searchParams.set('source_label_request_reference', source.value);
                url.searchParams.set('request_kind', isStandard ? 'standard' : 'lpk');
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Source lookup failed');
                const data = await response.json();
                if (version !== lookupVersion) return;
                if (!data.found) {
                    source.setCustomValidity('La requisición original debe estar liberada, ser del mismo tipo y contener Serial o Rating.');
                    summary.textContent = 'No se encontró una requisición original liberada de este tipo con etiquetas Serial o Rating.';
                    onChange(null);
                    return;
                }

                source.setCustomValidity('');
                sourceId.value = data.source_label_request_id;
                applyStandardTypes(data.label_types || []);
                const lines = data.lines.slice(0, 20).map((line) =>
                    `${line.type} ${line.part_number} · Job ${line.job_number} · ${line.model || 'Sin modelo'} · ${line.quantity} pzs`,
                );
                summary.textContent = `Origen #${data.source_label_request_id} · Job ${data.job_number || 'sin dato'} · mercado ${data.market || 'por confirmar'}\n${lines.join('\n')}${data.lines.length > 20 ? '\n… y más etiquetas' : ''}`;
                onChange(data);
            } catch {
                if (version === lookupVersion) {
                    source.setCustomValidity('No se pudo consultar la requisición original. Intenta nuevamente.');
                    summary.textContent = 'No se pudo consultar la requisición original. Intenta nuevamente.';
                    onChange(null);
                }
            }
        }, 300);
    };

    const update = () => {
        const active = mode.value === 'lost_rework';
        fields.classList.toggle('hidden', !active);
        source.required = active;
        reason.required = active;

        if (isStandard) {
            if (active) applyStandardTypes([]);
            else unlockStandardTypes();
        }

        onChange(null);
        lookupSource();
    };

    mode.addEventListener('change', update);
    source.addEventListener('input', lookupSource);
    update();
}
