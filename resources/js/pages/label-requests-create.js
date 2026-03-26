import { debounce } from './utils/debounce';

function setHint(element, type, message = '') {
    if (!element) {
        return;
    }

    const colorClassByType = {
        ok: 'text-emerald-700',
        warn: 'text-amber-700',
        muted: 'text-slate-500',
    };

    element.className = `text-xs mt-2 ${colorClassByType[type] || colorClassByType.muted}`;
    element.textContent = message;
}

function setText(element, message) {
    if (!element) {
        return;
    }

    element.textContent = message;
}

(function () {
    const form = document.getElementById('labelRequestCreate');

    if (!form) {
        return;
    }

    const lookupUrl = form.dataset.lookupUrl;
    const jobInput = document.getElementById('jobNumber');
    const poInput = document.getElementById('poNumber');
    const destinationInput = document.getElementById('destination');
    const hint = document.getElementById('jobHint');
    const dateInput = document.getElementById('requestDate');
    const weekInput = document.getElementById('requestWeek');
    const lineTypeSelect = document.getElementById('lineTypeSelect');
    const lineSelect = document.getElementById('lineSelect');
    const lineTypeHint = document.getElementById('lineTypeHint');
    const shiftSelect = document.getElementById('shiftSelect');
    const leaderInput = document.getElementById('leaderName');
    const labelSelect = document.getElementById('labelPartNumber');
    const quantityInput = document.getElementById('quantityRequested');
    const includeSerialInput = document.getElementById('includeSerial');
    const includeRatingInput = document.getElementById('includeRating');
    const modelInput = document.getElementById('modelInput');
    const labelHint = document.getElementById('labelHint');

    const previewLineShift = document.getElementById('previewLineShift');
    const previewLeader = document.getElementById('previewLeader');
    const previewDateWeek = document.getElementById('previewDateWeek');
    const previewQuantity = document.getElementById('previewQuantity');
    const previewLabel = document.getElementById('previewLabel');
    const previewLabelDescription = document.getElementById('previewLabelDescription');
    const previewTypes = document.getElementById('previewTypes');
    const previewJob = document.getElementById('previewJob');
    const previewExtras = document.getElementById('previewExtras');
    const lineOptions = lineSelect
        ? Array.from(lineSelect.querySelectorAll('option')).filter((option) => option.value !== '')
        : [];

    const syncLineTypeFromSelectedLine = () => {
        if (!lineTypeSelect || !lineSelect?.value) {
            return;
        }

        const selectedLineOption = lineSelect.selectedOptions?.[0];
        const selectedLineType = selectedLineOption?.dataset.lineType || '';

        if (selectedLineType) {
            lineTypeSelect.value = selectedLineType;
        }
    };

    const updateLineOptionsByType = ({ preserveSelection = true } = {}) => {
        if (!lineSelect) {
            return;
        }

        const selectedLineType = lineTypeSelect?.value || '';
        const currentLineValue = lineSelect.value;
        let visibleCount = 0;

        lineOptions.forEach((option) => {
            const optionLineType = option.dataset.lineType || '';
            const shouldShow = selectedLineType === '' || optionLineType === selectedLineType;

            option.hidden = !shouldShow;
            option.disabled = !shouldShow;

            if (shouldShow) {
                visibleCount += 1;
            }
        });

        if (preserveSelection && currentLineValue) {
            const selectedOption = lineOptions.find((option) => option.value === currentLineValue);
            if (selectedOption?.hidden || selectedOption?.disabled) {
                lineSelect.value = '';
            }
        }

        if (lineTypeHint) {
            if (!selectedLineType) {
                setHint(lineTypeHint, 'muted', `Mostrando todas las líneas (${visibleCount} disponibles).`);
            } else if (visibleCount > 0) {
                setHint(lineTypeHint, 'ok', `Mostrando ${visibleCount} línea(s) para "${selectedLineType}".`);
            } else {
                setHint(lineTypeHint, 'warn', `No hay líneas activas para "${selectedLineType}".`);
            }
        }
    };

    const formatDate = (value) => {
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
    };

    const updateLabelPreview = () => {
        const selectedOption = labelSelect?.selectedOptions?.[0];
        const selectedValue = selectedOption?.value || '';

        if (!selectedValue) {
            setText(previewLabel, 'Sin SKU / Label PN');
            setText(previewLabelDescription, 'Selecciona una opción para ver el detalle.');
            setHint(labelHint, 'muted', 'Selecciona un registro para mostrar su descripción en el resumen.');
            return;
        }

        const sku = selectedOption.dataset.sku || 'SKU';
        const description = selectedOption.dataset.description || 'Sin descripción';

        setText(previewLabel, `${sku} · ${selectedValue}`);
        setText(previewLabelDescription, description);
        setHint(labelHint, 'ok', `${sku}: ${description}`);
    };

    const updatePreview = () => {
        const lineText = lineSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Selecciona línea';
        const shiftText = shiftSelect?.selectedOptions?.[0]?.textContent?.trim() || 'turno';
        const hasLine = Boolean(lineSelect?.value);
        const hasShift = Boolean(shiftSelect?.value);

        setText(
            previewLineShift,
            hasLine || hasShift ? `${hasLine ? lineText : 'Línea pendiente'} / ${hasShift ? shiftText : 'Turno pendiente'}` : 'Selecciona línea y turno',
        );

        setText(previewLeader, leaderInput?.value?.trim() ? `Responsable: ${leaderInput.value.trim()}` : 'Sin líder capturado');
        setText(previewDateWeek, `${formatDate(dateInput?.value)} · Semana ${weekInput?.value || '—'}`);
        setText(previewQuantity, quantityInput?.value ? `${quantityInput.value} etiqueta(s) solicitada(s)` : 'Cantidad no definida');

        const types = [];
        if (includeSerialInput?.checked) {
            types.push('Serial');
        }
        if (includeRatingInput?.checked) {
            types.push('Rating');
        }
        setText(previewTypes, types.length ? `Tipo seleccionado: ${types.join(' + ')}` : 'Tipo pendiente');

        setText(previewJob, jobInput?.value?.trim() ? `Job: ${jobInput.value.trim()}` : 'Job no capturado');

        const extras = [];
        if (poInput?.value?.trim()) {
            extras.push(`PO ${poInput.value.trim()}`);
        }
        if (destinationInput?.value?.trim()) {
            extras.push(`Destino ${destinationInput.value.trim()}`);
        }
        if (modelInput?.value?.trim()) {
            extras.push(`Modelo ${modelInput.value.trim()}`);
        }
        setText(previewExtras, extras.length ? extras.join(' · ') : 'PO, destino y modelo pendientes.');

        updateLabelPreview();
    };

    const performLookup = debounce(async () => {
        const jobNumber = (jobInput?.value || '').trim();

        if (jobInput) {
            jobInput.setCustomValidity('');
        }

        if (!jobNumber) {
            setHint(hint, 'muted', '');
            updatePreview();
            return;
        }

        setHint(hint, 'muted', 'Buscando en Oracle…');

        const url = new URL(lookupUrl, window.location.origin);
        url.searchParams.set('job_number', jobNumber);

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (!data.found) {
                if (jobInput) {
                    jobInput.setCustomValidity('No encontrado en Oracle Jobs.');
                }
                setHint(hint, 'warn', 'No encontrado en Oracle Jobs.');
                updatePreview();
                return;
            }

            if (!data.valid_for_packaging) {
                if (jobInput) {
                    jobInput.setCustomValidity('El Job debe pertenecer a Empaque (assembly 018/055/001).');
                }
                setHint(hint, 'warn', 'Tipo inválido para Empaque.');
                updatePreview();
                return;
            }

            if (destinationInput) {
                destinationInput.value = data.ship_code || '';
            }

            if (poInput) {
                poInput.value = data.ttl_cust_po || '';
            }

            setHint(hint, 'ok', `NP: ${data.assembly || '-'} | ${data.part_description || ''}`);
            updatePreview();
        } catch (error) {
            if (jobInput) {
                jobInput.setCustomValidity('No fue posible validar el Job en este momento.');
            }
            setHint(hint, 'warn', 'No fue posible consultar Oracle. Intenta de nuevo.');
            updatePreview();
        }
    }, 350);

    [
        dateInput,
        weekInput,
        lineTypeSelect,
        lineSelect,
        shiftSelect,
        leaderInput,
        labelSelect,
        quantityInput,
        includeSerialInput,
        includeRatingInput,
        poInput,
        destinationInput,
        modelInput,
    ].forEach((element) => {
        if (!element) {
            return;
        }

        element.addEventListener('input', updatePreview);
        element.addEventListener('change', updatePreview);
    });

    if (jobInput && lookupUrl) {
        jobInput.addEventListener('input', () => {
            updatePreview();
            performLookup();
        });
        jobInput.addEventListener('change', performLookup);

        if (jobInput.value.trim() !== '') {
            performLookup();
        }
    }

    if (lineTypeSelect) {
        lineTypeSelect.addEventListener('change', () => {
            updateLineOptionsByType({ preserveSelection: true });
            updatePreview();
        });
    }

    syncLineTypeFromSelectedLine();
    updateLineOptionsByType({ preserveSelection: true });
    updatePreview();
})();
