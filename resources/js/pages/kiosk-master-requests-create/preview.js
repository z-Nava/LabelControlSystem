import { getOfficialContext } from './job-driven-context';

export function getSelectedText(selectElement) {
    return selectElement?.selectedOptions?.[0]?.textContent?.trim() || '';
}

function formatJobWithQty(jobNumber, jobLookup) {
    if (!jobNumber) {
        return '';
    }

    if (!jobLookup?.found || jobLookup.job_number !== jobNumber) {
        return jobNumber;
    }

    const qty = jobLookup.job_qty ?? '—';

    return `${jobNumber} (${qty})`;
}

export function refreshPreview(fields, preview, jobLookupState = {}) {
    if (preview.date) {
        preview.date.textContent = fields.requestDate?.value || '—';
    }

    if (preview.lineShift) {
        const officialContext = getOfficialContext(fields, jobLookupState).context;
        const lineText = getSelectedText(fields.lineSelect) || officialContext?.line_code || '';
        const shiftText = getSelectedText(fields.shiftSelect);

        preview.lineShift.textContent = fields.shiftSelect
            ? ((lineText || shiftText) ? `${lineText || '—'} · ${shiftText || '—'}` : '—')
            : (lineText || '—');
    }

    if (preview.jobs) {
        const assemblyJob = (fields.jobAssembly?.value || '').trim();
        const packagingJob = (fields.jobPackaging?.value || '').trim();
        const assemblyDisplay = formatJobWithQty(assemblyJob, jobLookupState.assembly);
        const packagingDisplay = formatJobWithQty(packagingJob, jobLookupState.packaging);

        preview.jobs.textContent = (assemblyJob || packagingJob)
            ? [assemblyDisplay, packagingDisplay].filter(Boolean).join(' / ')
            : '—';
    }

    if (preview.type) {
        const requestTypeValue = (fields.requestType?.value || '').trim();
        preview.type.textContent = requestTypeValue ? getSelectedText(fields.requestType) : '—';
    }
}

function fieldValue(form, name) {
    return (form.elements.namedItem(name)?.value || '').trim();
}

function selectedFieldText(form, name) {
    const field = form.elements.namedItem(name);

    return field?.selectedOptions?.[0]?.textContent?.trim() || '';
}

export function refreshLabelRoomSummary(form, fields, summary, jobLookupState = {}) {
    if (!summary?.jobs) {
        return;
    }

    const assemblyJob = fieldValue(form, 'job_assembly');
    const packagingJob = fieldValue(form, 'job_packaging');
    const jobs = [
        assemblyJob ? `Ensamble: ${formatJobWithQty(assemblyJob, jobLookupState.assembly)}` : '',
        packagingJob ? `Empaque: ${formatJobWithQty(packagingJob, jobLookupState.packaging)}` : '',
    ].filter(Boolean);
    const official = getOfficialContext(fields, jobLookupState);
    const context = official.context;
    const local = fields.localInput?.value.trim() || '';
    const subinventory = fields.subinventoryInput?.value.trim() || '';
    const foliosFrom = fieldValue(form, 'folios_from');
    const foliosTo = fieldValue(form, 'folios_to');
    const partialFolio = fieldValue(form, 'partial_folio');
    const partialQty = fieldValue(form, 'partial_qty');
    const requestKind = selectedFieldText(form, 'kind');

    summary.jobs.textContent = jobs.length > 0 ? jobs.join(' / ') : '—';
    summary.type.textContent = fields.requestType?.value ? getSelectedText(fields.requestType) : '—';
    summary.officialLine.textContent = context?.line_code
        ? `${context.line_code}${context.line_type ? ` · ${context.line_type}` : ''}`
        : '—';
    summary.inventory.textContent = local || subinventory
        ? `${local || '—'} · ${subinventory || '—'}`
        : '—';
    summary.folios.textContent = foliosFrom || foliosTo
        ? `${foliosFrom || '—'} al ${foliosTo || '—'}`
        : '—';
    summary.request.textContent = partialFolio || partialQty
        ? `${requestKind || '—'} · Parcial ${partialFolio || '—'} (${partialQty || '—'} pzas)`
        : (requestKind || '—');
}

