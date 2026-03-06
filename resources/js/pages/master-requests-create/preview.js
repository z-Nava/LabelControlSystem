export function getSelectedText(selectElement) {
    return selectElement?.selectedOptions?.[0]?.textContent?.trim() || '';
}

export function refreshPreview(fields, preview) {
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

        preview.jobs.textContent = (assemblyJob || packagingJob)
            ? [assemblyJob, packagingJob].filter(Boolean).join(' / ')
            : '—';
    }

    if (preview.type) {
        const requestTypeValue = (fields.requestType?.value || '').trim();
        preview.type.textContent = requestTypeValue ? requestTypeValue.replaceAll('_', ' ') : '—';
    }
}
