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

async function lookupJob(lookupUrl, jobNumber) {
    const url = new URL(lookupUrl, window.location.origin);
    url.searchParams.set('job_number', jobNumber);

    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    return response.json();
}

function updateAutoCompletedFields(fields, data) {
    if (!fields.destination?.value) {
        fields.destination.value = data.ship_code || '';
    }

    if (!fields.poNumber?.value) {
        fields.poNumber.value = data.ttl_cust_po || '';
    }
}

export function createJobLookupHandler({ inputElement, hintElement, lookupUrl, fields, refreshPreview }) {
    return async () => {
        const jobNumber = (inputElement?.value || '').trim();
        refreshPreview();

        if (!jobNumber) {
            setHint(hintElement, 'muted');
            return;
        }

        setHint(hintElement, 'muted', 'Buscando en Oracle…');
        const data = await lookupJob(lookupUrl, jobNumber);

        if (!data.found) {
            setHint(hintElement, 'warn', 'No encontrado en Oracle Jobs.');
            return;
        }

        setHint(hintElement, 'ok', `NP: ${data.assembly || '-'} | ${data.part_description || ''}`);
        updateAutoCompletedFields(fields, data);
        refreshPreview();
    };
}
