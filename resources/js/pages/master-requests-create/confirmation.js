import Swal from '../../lib/sweetalert';
import { getFieldValue } from './dom';
import { getSelectedText } from './preview';

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function getConfirmationHtml(form, fields) {
    const leader = getFieldValue(form, 'leader_name') || '—';
    const date = getFieldValue(form, 'request_date') || '—';
    const line = getSelectedText(fields.lineSelect) || '—';
    const local = getFieldValue(form, 'local') || '—';
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

    return `
        <div class="text-left text-sm space-y-1">
            <p><strong>Líder:</strong> ${escapeHtml(leader)}</p>
            <p><strong>Fecha:</strong> ${escapeHtml(date)}</p>
            <p><strong>Línea:</strong> ${escapeHtml(line)}</p>
            <p><strong>Local:</strong> ${escapeHtml(local)}</p>
            <p><strong>Jobs:</strong> ${escapeHtml(assemblyJob)} / ${escapeHtml(packagingJob)}</p>
            <p><strong>Tipo de Master:</strong> ${escapeHtml(type)}</p>
            <p><strong>Folios:</strong> ${escapeHtml(folios)}</p>
            <p><strong>Folio parcial:</strong> ${escapeHtml(partialInfo)}</p>
        </div>
    `;
}

export async function confirmSubmit(form, fields) {
    const result = await Swal.fire({
        title: '¿Confirmas el envío de la requisición?',
        html: getConfirmationHtml(form, fields),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Cancelar',
        focusCancel: true,
        reverseButtons: true,
    });

    return result.isConfirmed;
}
