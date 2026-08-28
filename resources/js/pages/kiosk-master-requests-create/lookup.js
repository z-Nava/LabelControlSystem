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

async function lookupJob(lookupUrl, jobNumber, role, counterpartJobNumber = '') {
    const url = new URL(lookupUrl, window.location.origin);
    url.searchParams.set('job_number', jobNumber);
    url.searchParams.set('role', role);

    if (counterpartJobNumber) {
        url.searchParams.set('counterpart_job_number', counterpartJobNumber);
    }

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

function resolveRelevantRequestTypes(lookup, role, requestType) {
    if (requestType) {
        return [requestType];
    }

    return (lookup.production_context?.allowed_request_types || [])
        .filter((type) => role === 'packaging'
            ? type === 'assembly_packaging'
            : type !== 'assembly_packaging');
}

function resolveDisplayModel(lookup, relevantRequestTypes) {
    const candidateModels = relevantRequestTypes
        .map((type) => lookup.models_by_request_type?.[type] || '')
        .filter(Boolean);
    const uniqueModels = [...new Set(candidateModels)];

    return uniqueModels.length === 1 ? uniqueModels[0] : '';
}

export function updateModelField(fields, jobLookups = {}) {
    const modelInput = fields.modelDisplay;

    if (!modelInput) {
        return;
    }

    const hasPackagingJob = Boolean((fields.jobPackaging?.value || '').trim());
    const role = hasPackagingJob ? 'packaging' : 'assembly';
    const jobInput = role === 'packaging' ? fields.jobPackaging : fields.jobAssembly;
    const lookup = jobLookups?.[role];
    const requestType = (fields.requestType?.value || '').trim();
    const validFlag = role === 'packaging' ? 'valid_for_packaging' : 'valid_for_assembly';
    const isResolvedJob = Boolean(lookup?.found && lookup[validFlag]);
    const relevantRequestTypes = isResolvedJob
        ? resolveRelevantRequestTypes(lookup, role, requestType)
        : [];
    const model = isResolvedJob
        ? resolveDisplayModel(lookup, relevantRequestTypes)
        : '';
    const hasRelevantModel = relevantRequestTypes.some(
        (type) => Boolean(lookup.models_by_request_type?.[type]),
    );
    const shouldWarnMissingModel = Boolean(
        isResolvedJob && relevantRequestTypes.length > 0 && !hasRelevantModel,
    );

    modelInput.value = model;
    modelInput.dataset.modelMappingMissing = shouldWarnMissingModel ? 'true' : 'false';
    modelInput.classList.toggle('border-slate-300', !shouldWarnMissingModel);
    modelInput.classList.toggle('bg-slate-100', !shouldWarnMissingModel);
    modelInput.classList.toggle('text-slate-700', !shouldWarnMissingModel);
    modelInput.classList.toggle('border-red-500', shouldWarnMissingModel);
    modelInput.classList.toggle('bg-red-50', shouldWarnMissingModel);
    modelInput.classList.toggle('text-red-900', shouldWarnMissingModel);
    modelInput.classList.toggle('ring-1', shouldWarnMissingModel);
    modelInput.classList.toggle('ring-red-500', shouldWarnMissingModel);

    if (shouldWarnMissingModel) {
        modelInput.setAttribute('aria-invalid', 'true');
    } else {
        modelInput.removeAttribute('aria-invalid');
    }

    if (fields.modelMappingWarning) {
        const assembly = (lookup?.assembly || '').trim();

        const warningSuffix = requestType
            ? 'para este tipo de Master.'
            : 'en Master Model Mapping.';

        fields.modelMappingWarning.textContent = shouldWarnMissingModel
            ? `El assembly${assembly ? ` ${assembly}` : ''} no tiene un modelo cargado ${warningSuffix} Regístralo antes de enviar la requisición.`
            : '';
        fields.modelMappingWarning.classList.toggle('hidden', !shouldWarnMissingModel);
    }

    if (!((jobInput?.value || '').trim())) {
        modelInput.placeholder = 'Captura un Job para consultar el modelo';
    } else if (!lookup) {
        modelInput.placeholder = 'Esperando validación del Job';
    } else if (!isResolvedJob) {
        modelInput.placeholder = `Job ${role === 'packaging' ? 'Empaque' : 'Ensamble'} no válido`;
    } else if (!requestType) {
        modelInput.placeholder = 'Selecciona el Tipo de Master';
    } else if (!model) {
        modelInput.placeholder = 'Sin modelo activo en Master Model Mapping';
    } else {
        modelInput.placeholder = '';
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
        inputElement?.setCustomValidity(data.classification_messages?.assembly || 'El Job no coincide con una regla activa de Ensamble.');
        return {
            type: 'warn',
            message: 'Tipo inválido para Ensamble.',
        };
    }

    if (role === 'packaging' && !data.valid_for_packaging) {
        inputElement?.setCustomValidity(data.classification_messages?.packaging || 'El Job no coincide con una regla activa de Empaque.');
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
        const counterpartJobNumber = role === 'assembly'
            ? (fields.jobPackaging?.value || '').trim()
            : (fields.jobAssembly?.value || '').trim();

        try {
            data = await lookupJob(lookupUrl, jobNumber, role, counterpartJobNumber);
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
