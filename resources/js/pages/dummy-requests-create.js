import { debounce } from './utils/debounce';

(function initializeDummyRequestCreateForm() {
    const form = document.querySelector('#dummyRequestCreate');

    if (!form) {
        return;
    }

    const lookupUrl = form.dataset.lookupUrl;
    const jobInput = form.querySelector('#jobNumber');
    const assemblyInput = form.querySelector('#jobAssembly');
    const lineInput = form.querySelector('#jobLine');
    const quantityInput = form.querySelector('#quantityRequested');
    const jobInfoHint = form.querySelector('#jobInfoHint');
    const quantityHint = form.querySelector('#quantityHint');
    const lineTypeSelect = form.querySelector('#lineTypeSelect');
    const lineIdSelect = form.querySelector('#lineIdSelect');
    const requestDateInput = form.querySelector('input[name="request_date"]');
    const weekInput = form.querySelector('input[name="week"]');
    const leaderInput = form.querySelector('input[name="leader_name"]');
    const shiftSelect = form.querySelector('select[name="shift_id"]');
    const requestTypeSelect = form.querySelector('select[name="request_type"]');
    const notesInput = form.querySelector('textarea[name="notes"]');

    if (!lookupUrl || !jobInput || !assemblyInput || !lineInput || !quantityInput || !jobInfoHint || !quantityHint) {
        return;
    }

    let latestLookupToken = 0;
    let jobQtyLimit = null;
    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const clearOracleInfo = () => {
        assemblyInput.value = '';
        lineInput.value = '';
        jobInfoHint.textContent = '';
        quantityHint.textContent = 'La cantidad no puede exceder el Job Qty de Oracle.';
        jobQtyLimit = null;
        quantityInput.max = '100000';
        quantityInput.setCustomValidity('');
    };

    const enforceQtyLimit = () => {
        const quantityValue = Number(quantityInput.value || 0);

        quantityInput.setCustomValidity('');

        if (jobQtyLimit === null || !Number.isFinite(jobQtyLimit) || quantityValue <= 0) {
            return;
        }

        if (quantityValue > jobQtyLimit) {
            quantityInput.setCustomValidity(`La cantidad solicitada no puede superar el Job Qty (${jobQtyLimit}).`);
        }
    };

    const lookupJob = debounce(async () => {
        const jobNumber = jobInput.value.trim().toUpperCase();
        jobInput.value = jobNumber;

        if (jobNumber.length < 3) {
            clearOracleInfo();
            return;
        }

        const currentToken = ++latestLookupToken;

        try {
            const response = await fetch(`${lookupUrl}?job_number=${encodeURIComponent(jobNumber)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (currentToken !== latestLookupToken) {
                return;
            }

            if (!response.ok) {
                throw new Error('lookup_failed');
            }

            const data = await response.json();

            if (!data.found) {
                clearOracleInfo();
                jobInput.setCustomValidity('El Job no existe en Oracle Jobs.');
                jobInput.reportValidity();
                return;
            }

            jobInput.setCustomValidity('');
            const assembly = String(data.assembly || '').trim();
            const oracleLine = String(data.line || '').trim();
            const partDescription = String(data.part_description || '').trim();
            const jobQty = Number(data.job_qty);

            assemblyInput.value = assembly;
            lineInput.value = oracleLine;
            jobInfoHint.textContent = `NP: ${assembly || '-'} | Línea: ${oracleLine || '-'}${partDescription ? ` | ${partDescription}` : ''}`;

            if (Number.isFinite(jobQty) && jobQty > 0) {
                jobQtyLimit = jobQty;
                quantityInput.max = String(jobQty);
                quantityHint.textContent = `Máximo permitido por Job ${jobNumber}: ${jobQty}.`;
            } else {
                jobQtyLimit = null;
                quantityInput.max = '100000';
                quantityHint.textContent = 'Job sin Job Qty válido en Oracle; se mantiene el máximo general.';
            }

            enforceQtyLimit();
        } catch (error) {
            if (currentToken !== latestLookupToken) {
                return;
            }

            clearOracleInfo();
            jobInput.setCustomValidity('No se pudo validar el Job con Oracle. Intenta de nuevo.');
        }
    }, 300);

    jobInput.addEventListener('input', lookupJob);
    jobInput.addEventListener('change', lookupJob);
    quantityInput.addEventListener('input', enforceQtyLimit);
    quantityInput.addEventListener('change', enforceQtyLimit);

    if (jobInput.value.trim() !== '') {
        lookupJob();
    }

    if (lineTypeSelect && lineIdSelect) {
        const lineOptions = Array.from(lineIdSelect.options)
            .filter((option) => option.value !== '');

        const filterLinesByType = () => {
            const selectedType = lineTypeSelect.value.trim();
            let shouldResetLineSelection = true;

            lineOptions.forEach((option) => {
                const lineType = String(option.dataset.lineType || '').trim();
                const visible = selectedType === '' || lineType === selectedType;

                option.hidden = !visible;

                if (visible && option.value === lineIdSelect.value) {
                    shouldResetLineSelection = false;
                }
            });

            if (shouldResetLineSelection) {
                lineIdSelect.value = '';
            }
        };

        lineTypeSelect.addEventListener('change', filterLinesByType);
        filterLinesByType();
    }

    form.addEventListener('submit', async (event) => {
        if (!window.Swal) {
            return;
        }

        event.preventDefault();

        const selectedLine = lineIdSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Sin línea seleccionada';
        const selectedShift = shiftSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Sin turno seleccionado';
        const selectedRequestType = requestTypeSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Sin tipo seleccionado';
        const notesPreview = (notesInput?.value || '').trim();

        const result = await window.Swal.fire({
            title: '¿Confirmas crear la requisición Dummy QR?',
            icon: 'question',
            html: `
                <div class="text-left text-sm space-y-1">
                    <p><strong>Job:</strong> ${escapeHtml(jobInput.value.trim().toUpperCase() || '-')}</p>
                    <p><strong>Cantidad solicitada:</strong> ${escapeHtml(quantityInput.value || '-')}</p>
                    <p><strong>Tipo de requisición:</strong> ${escapeHtml(selectedRequestType)}</p>
                    <p><strong>Fecha / Semana:</strong> ${escapeHtml(requestDateInput?.value || '-')} / ${escapeHtml(weekInput?.value || '-')}</p>
                    <p><strong>Líder:</strong> ${escapeHtml(leaderInput?.value?.trim() || '-')}</p>
                    <p><strong>Línea:</strong> ${escapeHtml(selectedLine)}</p>
                    <p><strong>Turno:</strong> ${escapeHtml(selectedShift)}</p>
                    <p><strong>Assembly:</strong> ${escapeHtml(assemblyInput.value || '-')}</p>
                    <p><strong>Línea Oracle:</strong> ${escapeHtml(lineInput.value || '-')}</p>
                    ${notesPreview ? `<p><strong>Notas:</strong> ${escapeHtml(notesPreview)}</p>` : ''}
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Sí, crear requisición',
            cancelButtonText: 'Cancelar',
            focusCancel: true,
        });

        if (!result.isConfirmed) {
            return;
        }

        form.submit();
    });
})();
