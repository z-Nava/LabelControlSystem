import { debounce } from '../utils/debounce';
import { setHint } from './dom';

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

            setHint(hints.job, 'ok', `NP: ${data.assembly || '-'} | ${data.part_description || ''}`);
            onStateChange();
        } catch (error) {
            inputs.jobNumber.setCustomValidity('No fue posible validar el Job en este momento.');
            setHint(hints.job, 'warn', 'No fue posible consultar Oracle. Intenta de nuevo.');
            onStateChange();
        }
    }, 350);
}

export { createJobLookupHandler };
