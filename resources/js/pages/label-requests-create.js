import { collectElements } from './label-requests-create/dom';
import { createJobLookupHandler } from './label-requests-create/lookup';
import Swal from 'sweetalert2';
import {
    syncLineTypeFromSelectedLine,
    updateLabelOptionsByStandard,
    updateLineOptionsByType,
    updatePreview,
} from './label-requests-create/preview';

(function initializeLabelRequestCreateForm() {
    const elements = collectElements();

    if (!elements) {
        return;
    }

    const { inputs } = elements;

    const updateUI = () => updatePreview(elements);
    const lookupJob = createJobLookupHandler(elements, updateUI);

    [
        inputs.date,
        inputs.week,
        inputs.lineType,
        inputs.line,
        inputs.shift,
        inputs.leader,
        inputs.serialStandard,
        inputs.labelPartNumber,
        inputs.quantity,
        inputs.includeSerial,
        inputs.includeRating,
        inputs.poNumber,
        inputs.destination,
        inputs.model,
    ].forEach((element) => {
        if (!element) {
            return;
        }

        element.addEventListener('input', updateUI);
        element.addEventListener('change', updateUI);
    });

    if (inputs.lineType) {
        inputs.lineType.addEventListener('change', () => {
            updateLineOptionsByType(elements, { preserveSelection: true });
            updateUI();
        });
    }

    if (inputs.serialStandard) {
        inputs.serialStandard.addEventListener('change', () => {
            updateLabelOptionsByStandard(elements, { preserveSelection: true });
            updateUI();
        });
    }

    if (inputs.jobNumber && lookupJob) {
        inputs.jobNumber.addEventListener('input', () => {
            updateUI();
            lookupJob();
        });
        inputs.jobNumber.addEventListener('change', lookupJob);

        if (inputs.jobNumber.value.trim() !== '') {
            lookupJob();
        }
    }

    syncLineTypeFromSelectedLine(elements);
    updateLabelOptionsByStandard(elements, { preserveSelection: true });
    updateLineOptionsByType(elements, { preserveSelection: true });
    updateUI();

    const form = elements.form;
    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const selectedLabelOption = inputs.labelPartNumber?.selectedOptions?.[0];
            const selectedLine = inputs.line?.selectedOptions?.[0]?.textContent?.trim() || 'Línea pendiente';
            const selectedShift = inputs.shift?.selectedOptions?.[0]?.textContent?.trim() || 'Turno pendiente';
            const labelPartNumber = selectedLabelOption?.value || 'Sin SKU / Label PN';
            const sku = selectedLabelOption?.dataset?.sku || 'SKU';
            const serialStandard = inputs.serialStandard?.value || 'UL';
            const labelDescription = selectedLabelOption?.dataset?.description || 'Sin descripción';
            const quantity = inputs.quantity?.value || '0';
            const requestDate = inputs.date?.value || 'Fecha pendiente';
            const week = inputs.week?.value || '—';
            const leader = inputs.leader?.value?.trim() || 'Sin líder capturado';
            const job = inputs.jobNumber?.value?.trim() || 'No capturado';
            const po = inputs.poNumber?.value?.trim() || 'No capturada';
            const destination = inputs.destination?.value?.trim() || 'No capturado';
            const model = inputs.model?.value?.trim() || 'No capturado';
            const includeSerial = inputs.includeSerial?.checked ? 'Sí' : 'No';
            const includeRating = inputs.includeRating?.checked ? 'Sí' : 'No';

            const escapeHtml = (value) => String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const html = `
                <div class="text-left text-sm">
                    <ul style="margin:0;padding-left:1rem;display:grid;gap:0.25rem;">
                        <li><strong>Fecha / Semana:</strong> ${escapeHtml(requestDate)} · ${escapeHtml(week)}</li>
                        <li><strong>Línea / Turno:</strong> ${escapeHtml(selectedLine)} / ${escapeHtml(selectedShift)}</li>
                        <li><strong>Líder:</strong> ${escapeHtml(leader)}</li>
                        <li><strong>Job / PO:</strong> ${escapeHtml(job)} / ${escapeHtml(po)}</li>
                        <li><strong>Destino / Modelo:</strong> ${escapeHtml(destination)} / ${escapeHtml(model)}</li>
                        <li><strong>Etiqueta:</strong> ${escapeHtml(serialStandard)} · ${escapeHtml(sku)} · ${escapeHtml(labelPartNumber)}</li>
                        <li><strong>Descripción:</strong> ${escapeHtml(labelDescription)}</li>
                        <li><strong>Cantidad:</strong> ${escapeHtml(quantity)} etiqueta(s)</li>
                        <li><strong>Incluye Serial:</strong> ${escapeHtml(includeSerial)} · <strong>Incluye Rating:</strong> ${escapeHtml(includeRating)}</li>
                    </ul>
                </div>
            `;

            const result = await Swal.fire({
                title: '¿Confirmas crear la requisición?',
                html,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, crear requisición',
                cancelButtonText: 'Revisar datos',
                confirmButtonColor: '#dc2626',
                focusCancel: true,
            });

            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
})();
