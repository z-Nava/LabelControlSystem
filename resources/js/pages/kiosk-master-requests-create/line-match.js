import Swal from '../../lib/sweetalert';
import { evaluateMasterRequestLine } from './line-match-rules';

export function getMasterRequestLineResult(fields, jobLookups) {
    const selectedLineOption = fields.lineSelect?.selectedOptions?.[0];

    return evaluateMasterRequestLine({
        requestType: fields.requestType?.value,
        selectedLineCode: selectedLineOption?.dataset.lineCode || selectedLineOption?.textContent,
        jobNumbers: {
            assembly: fields.jobAssembly?.value,
            packaging: fields.jobPackaging?.value,
        },
        jobLookups,
    });
}

export function renderMasterRequestLineResult(statusElements, result) {
    const container = statusElements?.container;

    if (!container) {
        return;
    }

    const styles = {
        pending: {
            container: 'rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 md:col-span-2',
            title: 'Validando línea con Oracle',
        },
        match: {
            container: 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 md:col-span-2',
            title: 'La línea coincide',
        },
        mismatch: {
            container: 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 md:col-span-2',
            title: 'La línea no corresponde al Job',
        },
        unverifiable: {
            container: 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 md:col-span-2',
            title: 'No se puede validar la línea',
        },
    };
    const style = styles[result.status];

    if (!style) {
        container.className = 'hidden';
        statusElements.title.textContent = '';
        statusElements.message.textContent = '';
        return;
    }

    container.className = style.container;
    statusElements.title.textContent = style.title;
    statusElements.message.textContent = result.message;
}

export async function showMasterRequestLineAlert(result) {
    const isPending = result.status === 'pending';

    await Swal.fire({
        title: isPending ? 'Espera la validación de Oracle' : 'No se puede enviar la requisición',
        text: result.message,
        icon: isPending ? 'warning' : 'error',
        confirmButtonText: 'Corregir',
    });
}

