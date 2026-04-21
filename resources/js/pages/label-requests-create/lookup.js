import { debounce } from '../utils/debounce';
import { setHint } from './dom';

function normalizeComparableValue(value) {
    return (value || '').trim().toUpperCase();
}

function normalizePartNumber(value) {
    return normalizeComparableValue(value).replace(/[^0-9A-Z]/g, '');
}

function trimLeadingZeros(value) {
    return value.replace(/^0+/, '') || '0';
}

function splitPartNumbers(value) {
    return String(value || '')
        .split(/[\s,;|]+/)
        .map((token) => token.trim())
        .filter(Boolean);
}

function partNumbersMatch(jobAssembly, skuPartNumber) {
    const normalizedJobAssembly = normalizePartNumber(jobAssembly);

    if (!normalizedJobAssembly) {
        return false;
    }

    return splitPartNumbers(skuPartNumber).some((partNumber) => {
        const normalizedSkuPartNumber = normalizePartNumber(partNumber);

        if (!normalizedSkuPartNumber) {
            return false;
        }

        if (normalizedJobAssembly === normalizedSkuPartNumber) {
            return true;
        }

        const jobAssemblyWithoutZeros = trimLeadingZeros(normalizedJobAssembly);
        const skuPartNumberWithoutZeros = trimLeadingZeros(normalizedSkuPartNumber);

        return jobAssemblyWithoutZeros === skuPartNumberWithoutZeros;
    });
}

function autoSelectSkuByAssembly(elements, assembly) {
    const { inputs, labelOptions } = elements;
    const normalizedAssembly = normalizeComparableValue(assembly);

    if (!normalizedAssembly || !inputs.labelPartNumber || !inputs.serialStandard) {
        return false;
    }

    const matchingOption = labelOptions.find((option) => {
        const assemblyPartNumber = option.dataset.assemblyPartNumber || '';
        const packagingPartNumber = option.dataset.packagingPartNumber || '';

        return partNumbersMatch(normalizedAssembly, assemblyPartNumber)
            || partNumbersMatch(normalizedAssembly, packagingPartNumber);
    });

    if (!matchingOption) {
        return false;
    }

    const matchedStandard = matchingOption.dataset.standard || 'UL';
    const shouldNotifyStandardChange = inputs.serialStandard.value !== matchedStandard;
    inputs.serialStandard.value = matchedStandard;
    if (shouldNotifyStandardChange) {
        inputs.serialStandard.dispatchEvent(new Event('change', { bubbles: true }));
    }
    matchingOption.hidden = false;
    matchingOption.disabled = false;
    inputs.labelPartNumber.value = matchingOption.value;

    return true;
}

function createJobLookupHandler(elements, onStateChange) {
    const { lookupUrl, hints, inputs } = elements;

    if (!inputs.jobNumber || !lookupUrl) {
        return null;
    }

    return debounce(async () => {
        const jobNumber = inputs.jobNumber.value.trim();
        inputs.jobNumber.setCustomValidity('');

        if (!jobNumber) {
            setHint(hints.job, 'muted', '');
            onStateChange();
            return;
        }

        setHint(hints.job, 'muted', 'Buscando en Oracle…');

        try {
            const url = new URL(lookupUrl, window.location.origin);
            url.searchParams.set('job_number', jobNumber);

            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(`Oracle lookup failed with HTTP ${response.status}`);
            }

            const data = await response.json();

            if (!data.found) {
                inputs.jobNumber.setCustomValidity('No encontrado en Oracle Jobs.');
                setHint(hints.job, 'warn', 'No encontrado en Oracle Jobs.');
                onStateChange();
                return;
            }

            if (!data.valid_for_packaging) {
                inputs.jobNumber.setCustomValidity('El Job debe pertenecer a Empaque (assembly 018/055/001).');
                setHint(hints.job, 'warn', 'Tipo inválido para Empaque.');
                onStateChange();
                return;
            }

            if (inputs.destination) {
                inputs.destination.value = data.ship_code || '';
            }

            if (inputs.poNumber) {
                inputs.poNumber.value = data.ttl_cust_po || '';
            }

            const wasSkuAutoSelected = autoSelectSkuByAssembly(elements, data.assembly);
            setHint(hints.job, 'ok', `NP: ${data.assembly || '-'} | ${data.part_description || ''}`);
            if (wasSkuAutoSelected) {
                setHint(hints.label, 'ok', 'SKU seleccionado automáticamente con el assembly del Job.');
            } else {
                setHint(hints.label, 'warn', 'Job encontrado en Oracle, pero no hubo coincidencia automática de SKU por part number.');
            }
            onStateChange();
        } catch (error) {
            inputs.jobNumber.setCustomValidity('No fue posible validar el Job en este momento.');
            setHint(hints.job, 'warn', 'No fue posible consultar Oracle. Intenta de nuevo.');
            onStateChange();
        }
    }, 350);
}

export { createJobLookupHandler };
