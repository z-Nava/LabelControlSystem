const JOB_ROLES = [
    { role: 'assembly', label: 'Job Ensamble' },
    { role: 'packaging', label: 'Job Empaque' },
];

function positiveInteger(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const number = Number(value);

    return Number.isInteger(number) && number > 0 ? number : null;
}

function nonNegativeInteger(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const number = Number(value);

    return Number.isInteger(number) && number >= 0 ? number : null;
}

function normalizedFolios(folios) {
    return [...new Set((Array.isArray(folios) ? folios : [])
        .map((folio) => positiveInteger(folio))
        .filter((folio) => folio !== null))]
        .sort((left, right) => left - right);
}

function normalizeJobNumber(value) {
    return String(value || '').trim().toUpperCase();
}

function requestedValues(values) {
    const foliosFrom = positiveInteger(values.folios_from);
    const foliosTo = positiveInteger(values.folios_to);
    const standardPack = positiveInteger(values.std_pack_qty);
    const partialFolio = positiveInteger(values.partial_folio);
    const partialQuantity = positiveInteger(values.partial_qty);
    const hasValue = (value) => value !== null
        && value !== undefined
        && String(value).trim() !== '';
    const hasPartialInput = hasValue(values.partial_folio) || hasValue(values.partial_qty);
    const hasCompletePartial = !hasPartialInput || (partialFolio !== null && partialQuantity !== null);
    const hasValidRange = foliosFrom !== null && foliosTo !== null && foliosTo >= foliosFrom;
    const ready = hasValidRange && standardPack !== null && hasCompletePartial;
    const folioQuantities = new Map();

    if (ready) {
        for (let folio = foliosFrom; folio <= foliosTo; folio += 1) {
            folioQuantities.set(folio, standardPack);
        }

        if (partialFolio !== null && partialQuantity !== null) {
            folioQuantities.set(partialFolio, partialQuantity);
        }
    }

    const quantity = ready
        ? [...folioQuantities.values()].reduce((total, folioQuantity) => total + folioQuantity, 0)
        : null;

    return {
        requestType: String(values.request_type || '').trim(),
        assemblyJob: normalizeJobNumber(values.job_assembly),
        packagingJob: normalizeJobNumber(values.job_packaging),
        foliosFrom,
        foliosTo,
        standardPack,
        partialFolio,
        partialQuantity,
        rangeReady: hasValidRange,
        ready,
        quantity,
        folioQuantities,
        requestedFolios: [...folioQuantities.keys()],
    };
}

function duplicateFolios(registeredFolios, request) {
    if (!request.ready) {
        return [];
    }

    return registeredFolios.filter((folio) => request.folioQuantities.has(folio));
}

function matchingPairState(request, jobLookups) {
    return Object.values(jobLookups)
        .map((lookup) => lookup?.master_request_pair_state)
        .find((state) => state
            && normalizeJobNumber(state.assembly_job) === request.assemblyJob
            && normalizeJobNumber(state.packaging_job) === request.packagingJob) || null;
}

export function evaluateFolioValidation(values, jobLookups = {}) {
    const request = requestedValues(values);
    const isAssemblyPackaging = request.requestType === 'assembly_packaging';
    const expectedRoles = isAssemblyPackaging
        ? [request.assemblyJob ? 'assembly' : null, 'packaging'].filter(Boolean)
        : ['assembly'];
    const pairState = isAssemblyPackaging ? matchingPairState(request, jobLookups) : null;
    const pairStateRequired = isAssemblyPackaging && request.packagingJob !== '';
    const pairRegisteredFolios = normalizedFolios(pairState?.registered_folios);
    const pairDuplicateFolios = duplicateFolios(pairRegisteredFolios, request);
    const jobs = JOB_ROLES
        .map(({ role, label }) => {
            const lookup = jobLookups[role];
            const isBlocking = expectedRoles.includes(role);

            if (!lookup?.found || !isBlocking) {
                return null;
            }

            const state = lookup.master_request_state;
            const registeredFolios = normalizedFolios(state?.registered_folios);
            const foliosWithoutQuantity = normalizedFolios(state?.folios_without_quantity);
            const quantityConflicts = normalizedFolios(state?.quantity_conflicts);
            const jobQuantity = nonNegativeInteger(lookup.job_qty);
            const reservedQuantity = nonNegativeInteger(state?.reserved_quantity);
            const overlappingFolios = duplicateFolios(registeredFolios, request);
            const duplicates = isAssemblyPackaging
                ? (role === 'packaging' ? pairDuplicateFolios : [])
                : overlappingFolios;
            const errors = [];
            const notices = [];

            if (!state) {
                errors.push(`No fue posible consultar los folios registrados del ${label} ${lookup.job_number}.`);
            }

            if (foliosWithoutQuantity.length > 0) {
                errors.push(`Hay folios sin cantidad registrada: ${foliosWithoutQuantity.join(', ')}.`);
            }

            if (quantityConflicts.length > 0) {
                errors.push(`Hay cantidades diferentes registradas para folios compartidos: ${quantityConflicts.join(', ')}.`);
            }

            if (duplicates.length > 0) {
                errors.push(isAssemblyPackaging
                    ? `La combinación exacta de Job Ensamble y Job Empaque ya tiene los folios: ${duplicates.join(', ')}.`
                    : `Los folios solicitados que ya existen son: ${duplicates.join(', ')}.`);
            }

            if (state && jobQuantity === null) {
                errors.push('El Job no tiene una cantidad válida en Oracle Jobs.');
            }

            const alreadyReservedFolios = isAssemblyPackaging
                ? pairRegisteredFolios
                : registeredFolios;
            const additionalQuantity = request.ready
                ? request.requestedFolios
                    .filter((folio) => !alreadyReservedFolios.includes(folio))
                    .reduce((total, folio) => total + request.folioQuantities.get(folio), 0)
                : null;
            const resultingQuantity = additionalQuantity !== null && reservedQuantity !== null
                ? reservedQuantity + additionalQuantity
                : null;

            if (
                resultingQuantity !== null
                && jobQuantity !== null
                && resultingQuantity > jobQuantity
            ) {
                const excess = resultingQuantity - jobQuantity;
                const pieceLabel = excess === 1 ? 'pieza' : 'piezas';

                errors.push(`La cantidad del Job es ${formatNumber(jobQuantity)}; ya hay ${formatNumber(reservedQuantity)} registradas y esta requisición agrega ${formatNumber(additionalQuantity)} piezas nuevas para este Job. El total sería ${formatNumber(resultingQuantity)} y se excede por ${formatNumber(excess)} ${pieceLabel}.`);
            }

            if (isAssemblyPackaging && duplicates.length === 0) {
                const sharedFolios = overlappingFolios
                    .filter((folio) => !pairDuplicateFolios.includes(folio));

                if (sharedFolios.length > 0) {
                    const counterpart = role === 'assembly' ? 'Empaque' : 'Ensamble';
                    notices.push(`Esta Job también usa los folios ${sharedFolios.join(', ')} en otras combinaciones de ${counterpart}. Para esta combinación se contabilizan como piezas adicionales.`);
                }
            }

            const status = errors.length > 0
                ? 'invalid'
                : (request.ready ? (notices.length > 0 ? 'valid_with_notice' : 'valid') : 'pending');

            return {
                role,
                label,
                isBlocking,
                jobNumber: lookup.job_number,
                registeredFolios,
                duplicateFolios: duplicates,
                foliosWithoutQuantity,
                jobQuantity,
                reservedQuantity,
                requestedQuantity: request.quantity,
                additionalQuantity,
                resultingQuantity,
                remainingQuantity: resultingQuantity !== null && jobQuantity !== null
                    ? jobQuantity - resultingQuantity
                    : null,
                errors,
                notices,
                status,
            };
        })
        .filter(Boolean);

    let status = 'idle';
    const hasEveryExpectedJob = expectedRoles.every(
        (role) => jobs.some((job) => job.role === role),
    );

    if (jobs.some((job) => job.status === 'invalid')) {
        status = 'invalid';
    } else if (
        hasEveryExpectedJob
        && request.ready
        && (!pairStateRequired || pairState !== null)
    ) {
        status = 'valid';
    } else if (jobs.length > 0) {
        status = 'pending';
    }

    return {
        status,
        validationRoles: expectedRoles,
        pairStateReady: !pairStateRequired || pairState !== null,
        request,
        jobs,
    };
}

function formatNumber(value) {
    return value === null ? '—' : new Intl.NumberFormat('es-MX').format(value);
}

function element(tagName, className, text) {
    const node = document.createElement(tagName);
    node.className = className;

    if (text !== undefined) {
        node.textContent = text;
    }

    return node;
}

function renderJob(container, job) {
    const classNames = {
        invalid: 'rounded-xl border border-red-200 bg-red-50 p-4 text-red-950',
        valid: 'rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-950',
        valid_with_notice: 'rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-950',
        pending: 'rounded-xl border border-slate-200 bg-slate-50 p-4 text-slate-900',
    };
    const card = element('section', classNames[job.status]);
    const heading = element(
        'p',
        'font-semibold',
        `${job.label}: ${job.jobNumber}`,
    );
    const registered = job.registeredFolios.length > 0
        ? job.registeredFolios.join(', ')
        : 'Ninguno';

    card.appendChild(heading);
    card.appendChild(element(
        'p',
        'mt-2 break-words text-sm',
        `Folios registrados (${job.registeredFolios.length}): ${registered}`,
    ));
    if (job.isBlocking) {
        card.appendChild(element(
            'p',
            'mt-2 text-sm',
            `Cantidad del Job: ${formatNumber(job.jobQuantity)} · Cantidad ya registrada: ${formatNumber(job.reservedQuantity)}`,
        ));
    }

    if (job.isBlocking && job.requestedQuantity !== null) {
        const balance = job.remainingQuantity !== null && job.remainingQuantity < 0
            ? `Exceso resultante: ${formatNumber(Math.abs(job.remainingQuantity))}`
            : `Disponible después: ${formatNumber(job.remainingQuantity)}`;

        card.appendChild(element(
            'p',
            'mt-1 text-sm font-medium',
            `Cantidad de la requisición: ${formatNumber(job.requestedQuantity)} · Piezas nuevas para este Job: ${formatNumber(job.additionalQuantity)} · Total resultante: ${formatNumber(job.resultingQuantity)} · ${balance}`,
        ));
    } else if (job.isBlocking) {
        card.appendChild(element(
            'p',
            'mt-1 text-sm text-slate-600',
            'Captura Folios del, al y Std pack para calcular la cantidad inmediatamente.',
        ));
    }

    job.errors.forEach((message) => {
        card.appendChild(element('p', 'mt-2 text-sm font-semibold text-red-700', message));
    });

    job.notices.forEach((message) => {
        card.appendChild(element('p', 'mt-2 text-sm font-semibold text-amber-800', message));
    });

    if (['valid', 'valid_with_notice'].includes(job.status)) {
        card.appendChild(element(
            'p',
            'mt-2 text-sm font-semibold text-emerald-700',
            'Folios y cantidad disponibles para este Job.',
        ));
    }

    container.appendChild(card);
}

export function refreshFolioValidation(form, container, jobLookups = {}) {
    const fieldValue = (name) => (form.elements.namedItem(name)?.value || '').trim();
    const result = evaluateFolioValidation({
        folios_from: fieldValue('folios_from'),
        folios_to: fieldValue('folios_to'),
        std_pack_qty: fieldValue('std_pack_qty'),
        partial_folio: fieldValue('partial_folio'),
        partial_qty: fieldValue('partial_qty'),
        request_type: fieldValue('request_type'),
        job_assembly: fieldValue('job_assembly'),
        job_packaging: fieldValue('job_packaging'),
    }, jobLookups);

    if (!container) {
        return result;
    }

    container.replaceChildren();
    container.classList.toggle('hidden', result.jobs.length === 0);
    result.jobs.forEach((job) => renderJob(container, job));

    return result;
}

export function folioValidationAlertMessage(result) {
    const messages = result.jobs
        .filter((job) => job.errors.length > 0)
        .map((job) => {
            const registered = job.registeredFolios.length > 0
                ? job.registeredFolios.join(', ')
                : 'Ninguno';

            return `${job.label} ${job.jobNumber}: ${job.errors.join(' ')} Folios registrados: ${registered}.`;
        });

    if (messages.length > 0) {
        return messages.join(' ');
    }

    return result.pairStateReady === false
        ? 'Espera a que termine la consulta de la combinación Job Ensamble / Job Empaque.'
        : 'Completa los folios y cantidades para validar la requisición.';
}
