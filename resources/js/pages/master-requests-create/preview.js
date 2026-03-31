export function getSelectedText(selectElement) {
    return selectElement?.selectedOptions?.[0]?.textContent?.trim() || '';
}

function formatJobWithQty(jobNumber, jobLookup) {
    if (!jobNumber) {
        return '';
    }

    if (!jobLookup || jobLookup.job_number !== jobNumber) {
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
        const lineText = getSelectedText(fields.lineSelect);
        const shiftText = getSelectedText(fields.shiftSelect);

        preview.lineShift.textContent = (lineText || shiftText)
            ? `${lineText || '—'} · ${shiftText || '—'}`
            : '—';
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
        preview.type.textContent = requestTypeValue ? requestTypeValue.replaceAll('_', ' ') : '—';
    }
}
