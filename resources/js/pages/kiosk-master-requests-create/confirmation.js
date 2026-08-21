import Swal from '../../lib/sweetalert';
import { getFieldValue } from './dom';
import { getOfficialContext } from './job-driven-context';
import { getSelectedText } from './preview';

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function getConfirmationHtml(form, fields, jobLookupState) {
    const leaderInput = form.elements.namedItem('leader_name');
    const requestDateInput = form.elements.namedItem('request_date');
    const leader = leaderInput ? (getFieldValue(form, 'leader_name') || '—') : null;
    const date = requestDateInput ? (getFieldValue(form, 'request_date') || '—') : null;
    const official = getOfficialContext(fields, jobLookupState);
    const line = getSelectedText(fields.lineSelect) || official.context?.line_code || '—';
    const local = (fields.localInput?.value || '').trim() || '—';
    const subinventory = (fields.subinventoryInput?.value || '').trim() || '—';
    const assemblyJob = getFieldValue(form, 'job_assembly') || '—';
    const packagingJob = getFieldValue(form, 'job_packaging') || '—';
    const type = getFieldValue(form, 'request_type')
        ? getFieldValue(form, 'request_type').replaceAll('_', ' ')
        : '—';

    const foliosFrom = getFieldValue(form, 'folios_from');
    const foliosTo = getFieldValue(form, 'folios_to');
    const folios = (foliosFrom || foliosTo)
        ? `${foliosFrom || '—'} al ${foliosTo || '—'}`
        : '—';

    const partialFolio = getFieldValue(form, 'partial_folio');
    const partialQty = getFieldValue(form, 'partial_qty');
    const partialInfo = (partialFolio || partialQty)
        ? `${partialFolio || '—'} (${partialQty || '—'} pzas)`
        : 'No';
    const productionContext = [
        leader === null ? '' : `<p><strong>Líder:</strong> ${escapeHtml(leader)}</p>`,
        date === null ? '' : `<p><strong>Fecha:</strong> ${escapeHtml(date)}</p>`,
    ].join('');
    const lineDifference = official.lineDifference
        ? `<div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-900"><strong>Advertencia:</strong> ${escapeHtml(official.lineDifference.message)}</div>`
        : '';

    return `
        <div class="text-left text-sm space-y-1">
            ${lineDifference}
            ${productionContext}
            <p><strong>Línea:</strong> ${escapeHtml(line)}</p>
            <p><strong>Local:</strong> ${escapeHtml(local)}</p>
            <p><strong>Subinventory:</strong> ${escapeHtml(subinventory)}</p>
            <p><strong>Jobs:</strong> ${escapeHtml(assemblyJob)} / ${escapeHtml(packagingJob)}</p>
            <p><strong>Tipo de Master:</strong> ${escapeHtml(type)}</p>
            <p><strong>Folios:</strong> ${escapeHtml(folios)}</p>
            <p><strong>Folio parcial:</strong> ${escapeHtml(partialInfo)}</p>
        </div>
    `;
}

export async function confirmSubmit(form, fields, jobLookupState = {}, submissionAction = 'save') {
    const shouldPrint = submissionAction === 'save_and_print';
    const result = await Swal.fire({
        title: shouldPrint
            ? '¿Guardar e imprimir la requisición?'
            : '¿Confirmas el envío de la requisición?',
        html: getConfirmationHtml(form, fields, jobLookupState),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: shouldPrint ? 'Sí, guardar e imprimir' : 'Sí, guardar',
        cancelButtonText: 'Cancelar',
        focusCancel: true,
        reverseButtons: true,
    });

    return result.isConfirmed;
}

