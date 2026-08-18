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

    if (!response.ok) {
        throw new Error(`Oracle lookup failed with status ${response.status}`);
    }

    return response.json();
}

function updatePackagingFields(fields, data = null) {
    if (fields.destination) {
        fields.destination.value = data?.ship_code || '';
    }

    if (fields.poNumber) {
        fields.poNumber.value = data?.ttl_cust_po || '';
    }
}

function formatJobQty(jobQty) {
    if (jobQty === null || jobQty === undefined || jobQty === '') {
        return '—';
    }

    const parsedJobQty = Number(jobQty);

    if (!Number.isFinite(parsedJobQty)) {
        return String(jobQty);
    }

    return new Intl.NumberFormat('es-MX').format(parsedJobQty);
}

function setJobQtyText(element, jobQty) {
    if (!element) {
        return;
    }

    element.textContent = `Cantidad del job: ${formatJobQty(jobQty)}`;
}

function validateLookupByRole(inputElement, data, role) {
    inputElement?.setCustomValidity('');

    if (role === 'assembly' && !data.valid_for_assembly) {
        inputElement?.setCustomValidity('El Job Ensamble debe pertenecer a Ensamble/Subensamble (001/103/130/208/270/290/291/299/399/770) o a Motores-Moldeo (MEXMI/MXM).');
        return {
            type: 'warn',
            message: 'Tipo inválido para Ensamble.',
        };
    }

    if (role === 'packaging' && !data.valid_for_packaging) {
        inputElement?.setCustomValidity('El Job Empaque debe pertenecer a Empaque (assembly 018/055/001/270).');
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

export function createJobLookupHandler({
    inputElement,
    hintElement,
    qtyElement,
    lookupUrl,
    fields,
    refreshPreview,
    role,
    onResolved,
}) {
    return async () => {
        const jobNumber = (inputElement?.value || '').trim();
        refreshPreview();

        if (!jobNumber) {
            inputElement?.setCustomValidity('');
            setHint(hintElement, 'muted');
            setJobQtyText(qtyElement);
            onResolved?.(null);
            if (role === 'packaging') {
                updatePackagingFields(fields);
            }
            refreshPreview();
            return;
        }

        onResolved?.(null);
        setHint(hintElement, 'muted', 'Buscando en Oracle…');
        let data;

        try {
            data = await lookupJob(lookupUrl, jobNumber);
        } catch {
            if ((inputElement?.value || '').trim() !== jobNumber) {
                return;
            }

            inputElement?.setCustomValidity('No se pudo consultar el Job en Oracle. Intenta nuevamente.');
            setHint(hintElement, 'warn', 'No se pudo consultar Oracle. Intenta nuevamente.');
            setJobQtyText(qtyElement);
            onResolved?.(null);
            if (role === 'packaging') {
                updatePackagingFields(fields);
            }
            refreshPreview();
            return;
        }

        if ((inputElement?.value || '').trim() !== jobNumber) {
            return;
        }

        if (!data.found) {
            inputElement?.setCustomValidity('No encontrado en Oracle Jobs.');
            setHint(hintElement, 'warn', 'No encontrado en Oracle Jobs.');
            setJobQtyText(qtyElement);
            onResolved?.(data);
            if (role === 'packaging') {
                updatePackagingFields(fields);
            }
            refreshPreview();
            return;
        }

        const validation = validateLookupByRole(inputElement, data, role);
        setHint(hintElement, validation.type, validation.message);
        setJobQtyText(qtyElement, data.job_qty);
        onResolved?.(data);

        if (role === 'packaging') {
            updatePackagingFields(fields, validation.type === 'ok' ? data : null);
        }
        refreshPreview();
    };
}
