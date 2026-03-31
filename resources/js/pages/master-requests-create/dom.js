export function getMasterRequestElements() {
    const form = document.getElementById('masterRequestCreate');

    if (!form) {
        return null;
    }

    return {
        form,
        lookupUrl: form.dataset.lookupUrl,
        fields: {
            requestDate: document.getElementById('requestDate'),
            lineTypeSelect: document.getElementById('lineTypeSelect'),
            lineSelect: document.getElementById('lineSelect'),
            shiftSelect: document.getElementById('shiftSelect'),
            requestType: document.getElementById('requestType'),
            jobAssembly: document.getElementById('jobAssembly'),
            jobPackaging: document.getElementById('jobPackaging'),
            poNumber: document.getElementById('poNumber'),
            destination: document.getElementById('destination'),
            qtyAssembly: document.getElementById('jobAssemblyQty'),
            qtyPackaging: document.getElementById('jobPackagingQty'),
            hintAssembly: document.getElementById('jobAssemblyHint'),
            hintPackaging: document.getElementById('jobPackagingHint'),
        },
        preview: {
            date: document.getElementById('previewDate'),
            lineShift: document.getElementById('previewLineShift'),
            jobs: document.getElementById('previewJobs'),
            type: document.getElementById('previewType'),
        },
    };
}

export function getFieldValue(form, fieldName) {
    return (form.elements.namedItem(fieldName)?.value || '').trim();
}

export function clearCustomValidity(form, fieldName) {
    form.elements.namedItem(fieldName)?.setCustomValidity('');
}
