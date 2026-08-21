import Swal from '../../lib/sweetalert';

export const ASSEMBLY_PACKAGING_TYPE = 'assembly_packaging';
export const ORT_ASSEMBLY_TYPE = 'ort_assembly';

const ROLE_CONFIG = {
    assembly: {
        validFlag: 'valid_for_assembly',
        label: 'Ensamble',
    },
    packaging: {
        validFlag: 'valid_for_packaging',
        label: 'Empaque',
    },
};

function normalize(value) {
    return String(value || '').trim().toUpperCase();
}

function lookupForRole(jobLookups, role) {
    const lookup = jobLookups?.[role];

    return lookup?.found ? lookup : null;
}

function roleForRequestType(requestType) {
    return requestType === ASSEMBLY_PACKAGING_TYPE ? 'packaging' : 'assembly';
}

function isValidRoleLookup(lookup, role) {
    return Boolean(lookup?.found && lookup[ROLE_CONFIG[role].validFlag]);
}

function productionContext(lookup) {
    return lookup?.production_context || null;
}

function isRequestTypeAvailable(requestType, jobLookups) {
    const role = roleForRequestType(requestType);
    const lookup = lookupForRole(jobLookups, role);
    const context = productionContext(lookup);

    if (!isValidRoleLookup(lookup, role) || !context?.production_line_configured) {
        return false;
    }

    return (context.allowed_request_types || []).includes(requestType);
}

function renderJobContext(elements, lookup) {
    const container = elements?.container;

    if (!container) {
        return;
    }

    if (!lookup?.found) {
        container.className = 'hidden';
        return;
    }

    const context = productionContext(lookup);
    const lineCode = context?.line_code || normalize(lookup.line);
    const lineType = context?.line_type || '';
    let inventoryMessage = '';
    let isConfigured = false;

    if (!lineCode) {
        inventoryMessage = 'Oracle no reportó una línea para este Job.';
    } else if (!context?.production_line_configured) {
        inventoryMessage = 'La línea no existe o no está activa en el catálogo de producción.';
    } else if (!context.inventory_configured) {
        const missingFields = [
            !context.stock_locator ? 'Local' : null,
            !context.subinventory ? 'Subinventory' : null,
        ].filter(Boolean);

        inventoryMessage = `Aviso: falta ${missingFields.join(' y ')}. Puedes continuar con la requisición.`;
    } else {
        inventoryMessage = `Local: ${context.stock_locator || '—'} · Subinventory: ${context.subinventory || '—'}`;
        isConfigured = true;
    }

    container.className = isConfigured
        ? 'mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm'
        : 'mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm';
    elements.line.textContent = lineCode || 'Sin línea';
    elements.lineType.textContent = lineType ? ` · ${lineType}` : '';
    elements.inventory.textContent = inventoryMessage;
}

function filterRequestTypes(fields, jobLookups) {
    const select = fields.requestType;

    if (!select) {
        return [];
    }

    const placeholder = Array.from(select.options).find((option) => option.value === '');
    const typeOptions = Array.from(select.options).filter((option) => option.value !== '');
    const previousValue = select.value;
    const initialValue = select.dataset.initialValue || '';
    const availableOptions = typeOptions.filter((option) => {
        const available = isRequestTypeAvailable(option.value, jobLookups);

        option.hidden = !available;
        option.disabled = !available;

        return available;
    });

    if (!availableOptions.some((option) => option.value === previousValue)) {
        select.value = '';
    }

    if (initialValue && availableOptions.some((option) => option.value === initialValue)) {
        select.value = initialValue;
        select.dataset.initialValue = '';
    } else if (availableOptions.length === 1 && select.value === '') {
        select.value = availableOptions[0].value;
    }

    select.disabled = availableOptions.length === 0;

    if (placeholder) {
        placeholder.textContent = availableOptions.length > 0
            ? 'Selecciona el tipo de hoja Master...'
            : 'Captura y valida primero los Jobs...';
    }

    return availableOptions.map((option) => option.value);
}

function setInventoryFieldState(fields, isEditable) {
    const localInput = fields.localInput;
    const subinventoryInput = fields.subinventoryInput;

    [localInput, subinventoryInput].forEach((input) => {
        if (!input) {
            return;
        }

        input.readOnly = !isEditable;
        input.required = false;
        input.classList.toggle('bg-slate-100', !isEditable);
        input.classList.toggle('bg-white', isEditable);
    });
}

function applyOfficialProductionContext(fields, jobLookups) {
    const requestType = fields.requestType?.value || '';
    const role = roleForRequestType(requestType);
    const lookup = lookupForRole(jobLookups, role);
    const context = productionContext(lookup);
    const isOrtAssembly = requestType === ORT_ASSEMBLY_TYPE;
    const previousRequestType = fields.requestType?.dataset.appliedRequestType || '';

    setInventoryFieldState(fields, isOrtAssembly);

    if (!requestType || !context) {
        if (fields.localInput) {
            fields.localInput.value = '';
        }
        if (fields.subinventoryInput) {
            fields.subinventoryInput.value = '';
        }
    } else if (isOrtAssembly) {
        const switchedToOrt = previousRequestType !== ORT_ASSEMBLY_TYPE;
        const initialLocal = fields.localInput?.dataset.initialValue || '';
        const initialSubinventory = fields.subinventoryInput?.dataset.initialValue || '';

        if (fields.localInput && switchedToOrt) {
            fields.localInput.value = initialLocal || fields.localInput.dataset.ortDefaultValue || '';
            fields.localInput.dataset.initialValue = '';
        }
        if (fields.subinventoryInput && switchedToOrt) {
            fields.subinventoryInput.value = initialSubinventory || fields.subinventoryInput.dataset.ortDefaultValue || '';
            fields.subinventoryInput.dataset.initialValue = '';
        }
    } else {
        if (fields.localInput) {
            fields.localInput.value = context.stock_locator || '';
        }
        if (fields.subinventoryInput) {
            fields.subinventoryInput.value = context.subinventory || '';
        }
    }

    if (fields.requestType) {
        fields.requestType.dataset.appliedRequestType = requestType;
    }

    setInventoryFieldState(fields, isOrtAssembly);

    return {
        role,
        lookup,
        context,
    };
}

function getLineDifference(jobLookups, requestType) {
    if (requestType !== ASSEMBLY_PACKAGING_TYPE) {
        return null;
    }

    const assemblyLookup = lookupForRole(jobLookups, 'assembly');
    const packagingLookup = lookupForRole(jobLookups, 'packaging');

    if (
        !isValidRoleLookup(assemblyLookup, 'assembly')
        || !isValidRoleLookup(packagingLookup, 'packaging')
    ) {
        return null;
    }

    const assemblyLine = normalize(productionContext(assemblyLookup)?.line_code || assemblyLookup.line);
    const packagingLine = normalize(productionContext(packagingLookup)?.line_code || packagingLookup.line);

    if (!assemblyLine || !packagingLine || assemblyLine === packagingLine) {
        return null;
    }

    return {
        assemblyLine,
        packagingLine,
        message: `El Job Ensamble pertenece a ${assemblyLine} y el Job Empaque pertenece a ${packagingLine}. Se utilizará ${packagingLine}, la línea oficial del Job Empaque.`,
    };
}

function renderStatus(elements, result) {
    const container = elements?.container;

    if (!container) {
        return;
    }

    const styles = {
        pending: {
            classes: 'rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900',
            title: 'Validando Jobs con Oracle',
        },
        ready: {
            classes: 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900',
            title: 'Contexto de producción validado',
        },
        invalid: {
            classes: 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800',
            title: 'No se puede continuar',
        },
    };
    const style = styles[result.status];

    if (!style) {
        container.className = 'hidden';
        elements.title.textContent = '';
        elements.message.textContent = '';
        return;
    }

    container.className = style.classes;
    elements.title.textContent = style.title;
    elements.message.textContent = result.message;
}

function evaluateContext(fields, jobLookups, availableRequestTypes) {
    const assemblyNumber = normalize(fields.jobAssembly?.value);
    const packagingNumber = normalize(fields.jobPackaging?.value);
    const requestType = fields.requestType?.value || '';
    const hasPendingLookup = [
        ['assembly', assemblyNumber],
        ['packaging', packagingNumber],
    ].some(([role, number]) => number && !jobLookups?.[role]);

    if (hasPendingLookup) {
        return {
            status: 'pending',
            message: 'Espera a que el sistema termine de consultar los Jobs capturados.',
        };
    }

    if (!requestType) {
        if (availableRequestTypes.length > 0) {
            return {
                status: 'idle',
                message: '',
            };
        }

        if (assemblyNumber || packagingNumber) {
            return {
                status: 'invalid',
                message: 'Los Jobs capturados no tienen un Tipo de Master disponible. Revisa su función, línea y configuración de inventario.',
            };
        }

        return {
            status: 'idle',
            message: '',
        };
    }

    const role = roleForRequestType(requestType);
    const lookup = lookupForRole(jobLookups, role);
    const context = productionContext(lookup);
    const roleLabel = ROLE_CONFIG[role].label;

    if (!isValidRoleLookup(lookup, role)) {
        return {
            status: 'invalid',
            message: `Captura y valida un Job ${roleLabel} para el Tipo de Master seleccionado.`,
        };
    }

    if (!context?.line_code) {
        return {
            status: 'invalid',
            message: `El Job ${roleLabel} no tiene una línea registrada en Oracle.`,
        };
    }

    if (!context.production_line_configured) {
        return {
            status: 'invalid',
            message: `La línea ${context.line_code} no existe o no está activa en el catálogo de producción.`,
        };
    }

    if (!isRequestTypeAvailable(requestType, jobLookups)) {
        return {
            status: 'invalid',
            message: `El Tipo de Master seleccionado no está disponible para la línea ${context.line_code}.`,
        };
    }

    return {
        status: 'ready',
        message: `Se utilizará la línea oficial ${context.line_code} (${context.line_type}).`,
        role,
        lookup,
        context,
        lineDifference: getLineDifference(jobLookups, requestType),
    };
}

export function refreshJobDrivenContext(fields, elements, jobLookups) {
    renderJobContext(elements?.jobs?.assembly, jobLookups?.assembly);
    renderJobContext(elements?.jobs?.packaging, jobLookups?.packaging);

    const availableRequestTypes = filterRequestTypes(fields, jobLookups);
    const official = applyOfficialProductionContext(fields, jobLookups);
    const result = evaluateContext(fields, jobLookups, availableRequestTypes);
    const lineDifference = getLineDifference(jobLookups, fields.requestType?.value || '');

    if (elements?.officialLine) {
        const context = official.context;
        elements.officialLine.textContent = context?.line_code
            ? `${context.line_code}${context.line_type ? ` · ${context.line_type}` : ''}`
            : '—';
    }

    if (elements?.lineDifference?.container) {
        elements.lineDifference.container.classList.toggle('hidden', !lineDifference);
        elements.lineDifference.message.textContent = lineDifference?.message || '';
    }

    renderStatus(elements?.status, result);

    return {
        ...result,
        lineDifference,
    };
}

export function getOfficialContext(fields, jobLookups) {
    const requestType = fields.requestType?.value || '';
    const role = roleForRequestType(requestType);
    const lookup = lookupForRole(jobLookups, role);

    return {
        role,
        lookup,
        context: productionContext(lookup),
        lineDifference: getLineDifference(jobLookups, requestType),
    };
}

export async function showJobDrivenContextAlert(result) {
    const isPending = result.status === 'pending';

    await Swal.fire({
        title: isPending ? 'Espera la validación de Oracle' : 'No se puede enviar la requisición',
        text: result.message || 'Completa y valida el contexto de producción.',
        icon: isPending ? 'warning' : 'error',
        confirmButtonText: 'Corregir',
    });
}

