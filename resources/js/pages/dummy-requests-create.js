import { debounce } from './utils/debounce';

(function initializeDummyRequestCreateForm() {
    const form = document.querySelector('#dummyRequestCreate');

    if (!form) {
        return;
    }

    const lookupUrl = form.dataset.lookupUrl;
    const jobInput = form.querySelector('#jobNumber');
    const assemblyInput = form.querySelector('#jobAssembly');
    const quantityInput = form.querySelector('#quantityRequested');
    const jobInfoHint = form.querySelector('#jobInfoHint');
    const quantityHint = form.querySelector('#quantityHint');

    if (!lookupUrl || !jobInput || !assemblyInput || !quantityInput || !jobInfoHint || !quantityHint) {
        return;
    }

    let latestLookupToken = 0;
    let jobQtyLimit = null;

    const clearOracleInfo = () => {
        assemblyInput.value = '';
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
            const partDescription = String(data.part_description || '').trim();
            const jobQty = Number(data.job_qty);

            assemblyInput.value = assembly;
            jobInfoHint.textContent = `NP: ${assembly || '-'} | ${partDescription || ''}`;

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
})();
