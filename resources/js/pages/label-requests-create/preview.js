import { setHint, setText } from './dom';

function formatDate(value) {
    if (!value) {
        return 'Fecha pendiente';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('es-MX', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(date);
}

function updateLabelPreview(elements) {
    const { inputs, hints, preview } = elements;
    const selectedOption = inputs.labelPartNumber?.selectedOptions?.[0];
    const labelPartNumber = selectedOption?.value || '';

    if (!labelPartNumber) {
        setText(preview.label, 'Sin SKU / Label PN');
        setText(preview.labelDescription, 'Selecciona una opción para ver el detalle.');
        setHint(hints.label, 'muted', 'Selecciona un registro para mostrar su descripción en el resumen.');

        return;
    }

    const sku = selectedOption?.dataset.sku || 'SKU';
    const description = selectedOption?.dataset.description || 'Sin descripción';

    setText(preview.label, `${sku} · ${labelPartNumber}`);
    setText(preview.labelDescription, description);
    setHint(hints.label, 'ok', `${sku}: ${description}`);
}

function updateLineShiftPreview(elements) {
    const { inputs, preview } = elements;
    const lineText = inputs.line?.selectedOptions?.[0]?.textContent?.trim() || 'Selecciona línea';
    const shiftText = inputs.shift?.selectedOptions?.[0]?.textContent?.trim() || 'turno';
    const hasLine = Boolean(inputs.line?.value);
    const hasShift = Boolean(inputs.shift?.value);

    setText(
        preview.lineShift,
        hasLine || hasShift
            ? `${hasLine ? lineText : 'Línea pendiente'} / ${hasShift ? shiftText : 'Turno pendiente'}`
            : 'Selecciona línea y turno',
    );
}

function updateTypePreview(elements) {
    const { inputs, preview } = elements;
    const selectedTypes = [];

    if (inputs.includeSerial?.checked) {
        selectedTypes.push('Serial');
    }

    if (inputs.includeRating?.checked) {
        selectedTypes.push('Rating');
    }

    setText(preview.types, selectedTypes.length ? `Tipo seleccionado: ${selectedTypes.join(' + ')}` : 'Tipo pendiente');
}

function updateExtrasPreview(elements) {
    const { inputs, preview } = elements;
    const extras = [];

    if (inputs.poNumber?.value?.trim()) {
        extras.push(`PO ${inputs.poNumber.value.trim()}`);
    }

    if (inputs.destination?.value?.trim()) {
        extras.push(`Destino ${inputs.destination.value.trim()}`);
    }

    if (inputs.model?.value?.trim()) {
        extras.push(`Modelo ${inputs.model.value.trim()}`);
    }

    setText(preview.extras, extras.length ? extras.join(' · ') : 'PO, destino y modelo pendientes.');
}

function updatePreview(elements) {
    const { inputs, preview } = elements;

    updateLineShiftPreview(elements);
    setText(preview.leader, inputs.leader?.value?.trim() ? `Responsable: ${inputs.leader.value.trim()}` : 'Sin líder capturado');
    setText(preview.dateWeek, `${formatDate(inputs.date?.value)} · Semana ${inputs.week?.value || '—'}`);
    setText(preview.quantity, inputs.quantity?.value ? `${inputs.quantity.value} etiqueta(s) solicitada(s)` : 'Cantidad no definida');
    setText(preview.job, inputs.jobNumber?.value?.trim() ? `Job: ${inputs.jobNumber.value.trim()}` : 'Job no capturado');

    updateTypePreview(elements);
    updateExtrasPreview(elements);
    updateLabelPreview(elements);
}

function syncLineTypeFromSelectedLine(elements) {
    const { inputs } = elements;

    if (!inputs.lineType || !inputs.line?.value) {
        return;
    }

    const selectedLineType = inputs.line.selectedOptions?.[0]?.dataset.lineType || '';

    if (selectedLineType) {
        inputs.lineType.value = selectedLineType;
    }
}

function updateLineOptionsByType(elements, { preserveSelection = true } = {}) {
    const { inputs, hints, lineOptions } = elements;

    if (!inputs.line) {
        return;
    }

    const selectedLineType = inputs.lineType?.value || '';
    const selectedLineValue = inputs.line.value;
    let availableCount = 0;

    lineOptions.forEach((option) => {
        const optionLineType = option.dataset.lineType || '';
        const shouldShow = selectedLineType === '' || optionLineType === selectedLineType;

        option.hidden = !shouldShow;
        option.disabled = !shouldShow;

        if (shouldShow) {
            availableCount += 1;
        }
    });

    if (preserveSelection && selectedLineValue) {
        const selectedOption = lineOptions.find((option) => option.value === selectedLineValue);

        if (selectedOption?.hidden || selectedOption?.disabled) {
            inputs.line.value = '';
        }
    }

    if (!selectedLineType) {
        setHint(hints.lineType, 'muted', `Mostrando todas las líneas (${availableCount} disponibles).`);
        return;
    }

    if (availableCount > 0) {
        setHint(hints.lineType, 'ok', `Mostrando ${availableCount} línea(s) para "${selectedLineType}".`);
        return;
    }

    setHint(hints.lineType, 'warn', `No hay líneas activas para "${selectedLineType}".`);
}

export {
    syncLineTypeFromSelectedLine,
    updateLineOptionsByType,
    updatePreview,
};
