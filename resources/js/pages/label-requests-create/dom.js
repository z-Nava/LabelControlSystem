const HINT_COLOR_CLASS = {
    ok: 'text-emerald-700',
    warn: 'text-amber-700',
    muted: 'text-slate-500',
};

function byId(id) {
    return document.getElementById(id);
}

function collectElements() {
    const form = byId('labelRequestCreate');

    if (!form) {
        return null;
    }

    const lineSelect = byId('lineSelect');

    return {
        form,
        lookupUrl: form.dataset.lookupUrl || '',
        inputs: {
            date: byId('requestDate'),
            week: byId('requestWeek'),
            lineType: byId('lineTypeSelect'),
            line: lineSelect,
            shift: byId('shiftSelect'),
            leader: byId('leaderName'),
            serialStandard: byId('serialStandard'),
            labelPartNumber: byId('labelPartNumber'),
            quantity: byId('quantityRequested'),
            includeSerial: byId('includeSerial'),
            includeRating: byId('includeRating'),
            jobNumber: byId('jobNumber'),
            poNumber: byId('poNumber'),
            destination: byId('destination'),
            model: byId('modelInput'),
        },
        hints: {
            job: byId('jobHint'),
            label: byId('labelHint'),
            lineType: byId('lineTypeHint'),
        },
        preview: {
            lineShift: byId('previewLineShift'),
            leader: byId('previewLeader'),
            dateWeek: byId('previewDateWeek'),
            quantity: byId('previewQuantity'),
            label: byId('previewLabel'),
            labelDescription: byId('previewLabelDescription'),
            types: byId('previewTypes'),
            job: byId('previewJob'),
            extras: byId('previewExtras'),
        },
        lineOptions: lineSelect
            ? Array.from(lineSelect.querySelectorAll('option')).filter((option) => option.value !== '')
            : [],
        labelOptions: byId('labelPartNumber')
            ? Array.from(byId('labelPartNumber').querySelectorAll('option')).filter((option) => option.value !== '')
            : [],
    };
}

function setText(element, message) {
    if (!element) {
        return;
    }

    element.textContent = message;
}

function setHint(element, type, message = '') {
    if (!element) {
        return;
    }

    const colorClass = HINT_COLOR_CLASS[type] || HINT_COLOR_CLASS.muted;
    element.className = `mt-2 text-xs ${colorClass}`;
    element.textContent = message;
}

export {
    collectElements,
    setHint,
    setText,
};
