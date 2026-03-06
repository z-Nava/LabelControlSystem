import { debounce } from './utils/debounce';

function setHint(element, type, message = '') {
    if (!element) {
        return;
    }

    const colorClassByType = {
        ok: 'text-emerald-700',
        warn: 'text-amber-700',
        muted: 'text-slate-500',
    };

    element.className = `text-xs mt-2 ${colorClassByType[type] || colorClassByType.muted}`;
    element.textContent = message;
}

(function () {
    const form = document.getElementById('labelRequestCreate');

    if (!form) {
        return;
    }

    const lookupUrl = form.dataset.lookupUrl;
    const jobInput = document.getElementById('jobNumber');
    const poInput = document.getElementById('poNumber');
    const destinationInput = document.getElementById('destination');
    const hint = document.getElementById('jobHint');

    if (!lookupUrl || !jobInput) {
        return;
    }

    const performLookup = debounce(async () => {
        const jobNumber = (jobInput.value || '').trim();
        jobInput.setCustomValidity('');

        if (!jobNumber) {
            setHint(hint, 'muted', '');
            return;
        }

        setHint(hint, 'muted', 'Buscando en Oracle…');

        const url = new URL(lookupUrl, window.location.origin);
        url.searchParams.set('job_number', jobNumber);

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
            },
        });

        const data = await response.json();

        if (!data.found) {
            jobInput.setCustomValidity('No encontrado en Oracle Jobs.');
            setHint(hint, 'warn', 'No encontrado en Oracle Jobs.');
            return;
        }

        if (!data.valid_for_packaging) {
            jobInput.setCustomValidity('El Job debe pertenecer a Empaque (assembly 018/055/001).');
            setHint(hint, 'warn', 'Tipo inválido para Empaque.');
            return;
        }

        if (!destinationInput?.value) {
            destinationInput.value = data.ship_code || '';
        }

        if (!poInput?.value) {
            poInput.value = data.ttl_cust_po || '';
        }

        setHint(hint, 'ok', `NP: ${data.assembly || '-'} | ${data.part_description || ''}`);
    }, 350);

    jobInput.addEventListener('input', performLookup);
    jobInput.addEventListener('change', performLookup);
})();
