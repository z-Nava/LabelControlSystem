export function mountLostLabelReworkFields(form, onChange = () => {}) {
    const mode = form.querySelector('#folioMode');
    const fields = form.querySelector('#lostReworkFields');
    const source = form.querySelector('#sourceLabelRequestId');
    const reason = form.querySelector('#reworkReason');
    const summary = form.querySelector('#reworkSourceSummary');
    const isStandard = form.id === 'kioskLabelRequestCreate';
    let lookupTimer;
    let lookupVersion = 0;

    const lookupSource = () => {
        clearTimeout(lookupTimer);
        const version = ++lookupVersion;
        source.setCustomValidity('');
        if (mode.value !== 'lost_rework' || !source.value) {
            summary.textContent = '';
            return;
        }

        summary.textContent = 'Consultando la requisición original…';
        lookupTimer = setTimeout(async () => {
            try {
                const url = new URL(source.dataset.lookupUrl, window.location.origin);
                url.searchParams.set('source_label_request_id', source.value);
                url.searchParams.set('request_kind', isStandard ? 'standard' : 'lpk');
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Source lookup failed');
                const data = await response.json();
                if (version !== lookupVersion) return;
                if (!data.found) {
                    source.setCustomValidity('La requisición original debe estar liberada y ser del mismo tipo.');
                    summary.textContent = 'No se encontró una requisición original liberada de este tipo.';
                    return;
                }

                const lines = data.lines.slice(0, 20).map((line) =>
                    `${line.type} ${line.part_number} · Job ${line.job_number} · ${line.model || 'Sin modelo'} · ${line.quantity} pzs`,
                );
                summary.textContent = `Origen #${source.value} · mercado ${data.market || 'por confirmar'}\n${lines.join('\n')}${data.lines.length > 20 ? '\n… y más etiquetas' : ''}`;
            } catch {
                if (version === lookupVersion) summary.textContent = 'No se pudo consultar la requisición original. Se validará al enviar.';
            }
        }, 300);
    };

    const update = () => {
        const active = mode.value === 'lost_rework';
        fields.classList.toggle('hidden', !active);
        source.required = active;
        reason.required = active;

        if (isStandard) {
            ['includeSerial', 'includeRating'].forEach((id) => {
                const input = form.querySelector(`#${id}`);
                if (active && !input.checked) {
                    input.checked = true;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            ['includeInner', 'includeShipping'].forEach((id) => {
                const input = form.querySelector(`#${id}`);
                if (active && input.checked) {
                    input.checked = false;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        onChange();
        lookupSource();
    };

    mode.addEventListener('change', update);
    source.addEventListener('input', lookupSource);
    if (isStandard) {
        ['includeSerial', 'includeRating', 'includeInner', 'includeShipping'].forEach((id) => {
            form.querySelector(`#${id}`).addEventListener('change', () => {
                if (mode.value === 'lost_rework') update();
            });
        });
    }
    update();
}
