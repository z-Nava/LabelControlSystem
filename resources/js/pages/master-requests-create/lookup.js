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

function validateLookupByRole(inputElement, data, role) {
    inputElement?.setCustomValidity('');

    if (role === 'assembly' && !data.valid_for_assembly) {
        inputElement?.setCustomValidity('El Job Ensamble debe pertenecer a Ensamble/Subensamble (103/130) o a Motores-Moldeo (MEXMI/MXM).');
        return {
            type: 'warn',
            message: 'Tipo inválido para Ensamble.',
        };
    }

    if (role === 'packaging' && !data.valid_for_packaging) {
        inputElement?.setCustomValidity('El Job Empaque debe pertenecer a Empaque (assembly 018/055/001).');
        return {
            type: 'warn',
            message: 'Tipo inválido para Empaque.',
        };
    }

    return {
        type: 'ok',
        message: `NP: ${data.assembly || '-'} | ${data.part_description || ''}`,
    };
}

export function createJobLookupHandler({ inputElement, hintElement, lookupUrl, fields, refreshPreview, role }) {
    return async () => {
        const jobNumber = (inputElement?.value || '').trim();
        refreshPreview();

        if (!jobNumber) {
            inputElement?.setCustomValidity('');
            setHint(hintElement, 'muted');
            return;
        }

        setHint(hintElement, 'muted', 'Buscando en Oracle…');
        const data = await lookupJob(lookupUrl, jobNumber);

        if (!data.found) {
            inputElement?.setCustomValidity('No encontrado en Oracle Jobs.');
            setHint(hintElement, 'warn', 'No encontrado en Oracle Jobs.');
            return;
        }

        const validation = validateLookupByRole(inputElement, data, role);
        setHint(hintElement, validation.type, validation.message);

        updateAutoCompletedFields(fields, data);
        refreshPreview();
    };
}