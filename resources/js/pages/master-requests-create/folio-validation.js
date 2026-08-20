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
    const quantity = ready
        ? ((foliosTo - foliosFrom + 1) * standardPack) + (partialQuantity || 0)
        : null;

    return {
        foliosFrom,
        foliosTo,
        standardPack,
        partialFolio,
        partialQuantity,
        rangeReady: hasValidRange,
        ready,
        quantity,
    };
}

function duplicateFolios(registeredFolios, request) {
    if (!request.rangeReady) {
        return [];
    }

    return registeredFolios.filter((folio) => (
        (folio >= request.foliosFrom && folio <= request.foliosTo)
        || (request.partialFolio !== null && folio === request.partialFolio)
    ));
}

export function evaluateFolioValidation(values, jobLookups = {}) {
    const request = requestedValues(values);
    const jobs = JOB_ROLES
        .map(({ role, label }) => {
            const lookup = jobLookups[role];

            if (!lookup?.found) {
                return null;
            }

            const state = lookup.master_request_state;
            const registeredFolios = normalizedFolios(state?.registered_folios);
            const foliosWithoutQuantity = normalizedFolios(state?.folios_without_quantity);
            const jobQuantity = nonNegativeInteger(lookup.job_qty);
            const reservedQuantity = nonNegativeInteger(state?.reserved_quantity);
            const duplicates = duplicateFolios(registeredFolios, request);
            const errors = [];

            if (!state) {
                errors.push(`No fue posible consultar los folios registrados del ${label} ${lookup.job_number}.`);
            }

            if (foliosWithoutQuantity.length > 0) {
                errors.push(`Hay folios sin cantidad registrada: ${foliosWithoutQuantity.join(', ')}.`);
            }

            if (duplicates.length > 0) {
                errors.push(`Los folios solicitados que ya existen son: ${duplicates.join(', ')}.`);
            }

            if (state && jobQuantity === null) {
                errors.push('El Job no tiene una cantidad válida en Oracle Jobs.');
            }

            const resultingQuantity = request.quantity !== null && reservedQuantity !== null
                ? reservedQuantity + request.quantity
                : null;

            if (
                resultingQuantity !== null
                && jobQuantity !== null
                && resultingQuantity > jobQuantity
            ) {
                const excess = resultingQuantity - jobQuantity;
                const pieceLabel = excess === 1 ? 'pieza' : 'piezas';

                errors.push(`La cantidad del Job es ${formatNumber(jobQuantity)}; ya hay ${formatNumber(reservedQuantity)} registradas y esta requisición agrega ${formatNumber(request.quantity)}. El total sería ${formatNumber(resultingQuantity)} y se excede por ${formatNumber(excess)} ${pieceLabel}.`);
            }

            const status = errors.length > 0
                ? 'invalid'
                : (request.ready ? 'valid' : 'pending');

            return {
                role,
                label,
                jobNumber: lookup.job_number,
                registeredFolios,
                duplicateFolios: duplicates,
                foliosWithoutQuantity,
                jobQuantity,
                reservedQuantity,
                requestedQuantity: request.quantity,
                resultingQuantity,
                remainingQuantity: resultingQuantity !== null && jobQuantity !== null
                    ? jobQuantity - resultingQuantity
                    : null,
                errors,
                status,
            };
        })
        .filter(Boolean);

    let status = 'idle';

    if (jobs.some((job) => job.status === 'invalid')) {
        status = 'invalid';
    } else if (jobs.length > 0 && request.ready) {
        status = 'valid';
    } else if (jobs.length > 0) {
        status = 'pending';
    }

    return {
        status,
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
    card.appendChild(element(
        'p',
        'mt-2 text-sm',
        `Cantidad del Job: ${formatNumber(job.jobQuantity)} · Cantidad ya registrada: ${formatNumber(job.reservedQuantity)}`,
    ));

    if (job.requestedQuantity !== null) {
        const balance = job.remainingQuantity !== null && job.remainingQuantity < 0
            ? `Exceso resultante: ${formatNumber(Math.abs(job.remainingQuantity))}`
            : `Disponible después: ${formatNumber(job.remainingQuantity)}`;

        card.appendChild(element(
            'p',
            'mt-1 text-sm font-medium',
            `Nueva requisición: ${formatNumber(job.requestedQuantity)} · Total resultante: ${formatNumber(job.resultingQuantity)} · ${balance}`,
        ));
    } else {
        card.appendChild(element(
            'p',
            'mt-1 text-sm text-slate-600',
            'Captura Folios del, al y Std pack para calcular la cantidad inmediatamente.',
        ));
    }

    job.errors.forEach((message) => {
        card.appendChild(element('p', 'mt-2 text-sm font-semibold text-red-700', message));
    });

    if (job.status === 'valid') {
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

    return 'Completa los folios y cantidades para validar la requisición.';
}
