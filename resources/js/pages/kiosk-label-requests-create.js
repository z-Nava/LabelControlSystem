import Swal from '../lib/sweetalert';
import { debounce } from './utils/debounce';

(function initializeKioskLabelRequestCreateForm() {
    const form = document.getElementById('kioskLabelRequestCreate');

    if (!form) {
        return;
    }

    const byId = (id) => document.getElementById(id);
    const inputs = {
        date: byId('requestDate'),
        week: byId('requestWeek'),
        lineType: byId('lineTypeFilter'),
        line: byId('lineSelect'),
        shift: byId('shiftSelect'),
        leader: byId('leaderName'),
        job: byId('jobNumber'),
        assembly: byId('assemblyInfo'),
        model: byId('modelInput'),
        po: byId('poNumber'),
        destination: byId('destination'),
        quantity: byId('quantityRequested'),
        shippingQuantity: byId('shippingQuantity'),
        serial: byId('includeSerial'),
        rating: byId('includeRating'),
        inner: byId('includeInner'),
        shipping: byId('includeShipping'),
        innerPartNumber: byId('innerPartNumber'),
        innerModel: byId('innerModel'),
        shippingPartNumber: byId('shippingPartNumber'),
        shippingModel: byId('shippingModel'),
    };
    const serialFields = byId('serialFields');
    const serialPartNumbersContainer = byId('serialPartNumbers');
    const serialPartNumberTemplate = byId('serialPartNumberTemplate');
    const addSerialPartNumberButton = byId('addSerialPartNumber');
    const ratingFields = byId('ratingFields');
    const ratingPartNumbersContainer = byId('ratingPartNumbers');
    const ratingPartNumberTemplate = byId('ratingPartNumberTemplate');
    const addRatingPartNumberButton = byId('addRatingPartNumber');
    const innerFields = byId('innerFields');
    const shippingFields = byId('shippingFields');
    const typeCards = Array.from(form.querySelectorAll('[data-label-type-card]'));
    const jobHint = byId('jobHint');
    const modelMappingHint = byId('modelMappingHint');
    const lineTypeHint = byId('lineTypeHint');
    const quantityHint = byId('quantityHint');
    const typeHint = byId('typeHint');
    const serialItemsHint = byId('serialItemsHint');
    const ratingItemsHint = byId('ratingItemsHint');
    const capacity = {
        container: byId('jobCapacitySummary'),
        jobQty: byId('jobQtyValue'),
        reserved: byId('reservedQuantityValue'),
        available: byId('availableQuantityValue'),
    };

    let validatedJobNumber = '';
    let availableQuantity = null;
    let mappedModel = '';

    const normalize = (value) => String(value || '').trim().toUpperCase();
    const setText = (element, value) => {
        if (element) element.textContent = value;
    };
    const setHint = (element, message, colorClass = 'text-slate-500') => {
        if (!element) return;
        element.className = `mt-2 text-xs ${colorClass}`;
        element.textContent = message;
    };
    const selectedTypes = () => [
        inputs.serial.checked ? 'Serial' : null,
        inputs.rating.checked ? 'Rating' : null,
        inputs.inner.checked ? 'Inner' : null,
        inputs.shipping.checked ? 'Shipping' : null,
    ].filter(Boolean);
    const serialPartNumberInputs = () => Array.from(
        serialPartNumbersContainer.querySelectorAll('.serial-part-number-input'),
    );
    const serialItems = () => Array.from(
        serialPartNumbersContainer.querySelectorAll('.serial-part-number-row'),
    ).map((row) => ({
        partNumber: row.querySelector('.serial-part-number-input')?.value.trim() || '',
        model: row.querySelector('.part-model-input')?.value.trim() || '',
    })).filter((item) => item.partNumber);
    const ratingPartNumberInputs = () => Array.from(
        ratingPartNumbersContainer.querySelectorAll('.rating-part-number-input'),
    );
    const ratingItems = () => Array.from(
        ratingPartNumbersContainer.querySelectorAll('.rating-part-number-row'),
    ).map((row) => ({
        partNumber: row.querySelector('.rating-part-number-input')?.value.trim() || '',
        model: row.querySelector('.part-model-input')?.value.trim() || '',
    })).filter((item) => item.partNumber);
    const formatItems = (items) => items
        .map((item) => item.model ? `${item.partNumber} (${item.model})` : item.partNumber)
        .join(', ');
    const modelInputs = () => Array.from(form.querySelectorAll('.mapped-model-input'));
    const lineOptions = Array.from(inputs.line.options).filter((option) => option.value !== '');

    function configureModelInput(input) {
        if (!input || input.dataset.modelInputConfigured === 'true') return;

        input.dataset.modelInputConfigured = 'true';
        input.dataset.manualPlaceholder = input.placeholder || 'Modelo (opcional)';
        input.dataset.hadWhiteBackground = input.classList.contains('bg-white') ? 'true' : 'false';
    }

    function setModelInputState(input, model, { clearManual = false } = {}) {
        if (!input) return;

        configureModelInput(input);

        const hasMappedModel = Boolean(model);

        if (hasMappedModel) {
            input.value = model;
        } else if (clearManual) {
            input.value = '';
        }

        input.readOnly = hasMappedModel;
        input.dataset.mappedModel = hasMappedModel ? model : '';
        input.placeholder = hasMappedModel
            ? 'Modelo mapeado'
            : input.dataset.manualPlaceholder;
        input.classList.toggle('cursor-not-allowed', hasMappedModel);
        input.classList.toggle('bg-slate-100', hasMappedModel);

        if (input.dataset.hadWhiteBackground === 'true') {
            input.classList.toggle('bg-white', !hasMappedModel);
        }
    }

    function applyMappedModel(model, { clearManual = false, status = 'resolved' } = {}) {
        mappedModel = normalize(model);
        modelInputs().forEach((input) => setModelInputState(input, mappedModel, { clearManual }));

        if (mappedModel) {
            setHint(
                modelMappingHint,
                `Modelo ${mappedModel} obtenido de Master Model Mapping.`,
                'text-emerald-700',
            );
        } else if (status === 'resolved') {
            setHint(
                modelMappingHint,
                'El Assembly no tiene un modelo activo en Master Model Mapping; captura el modelo manualmente.',
                'text-amber-700',
            );
        } else {
            setHint(modelMappingHint, 'Se consultará en Master Model Mapping.');
        }
    }

    function syncConditionalFields() {
        const hasSerial = inputs.serial.checked;
        const hasRating = inputs.rating.checked;
        const hasInner = inputs.inner.checked;
        const hasShipping = inputs.shipping.checked;

        serialFields.classList.toggle('hidden', !hasSerial);
        serialPartNumberInputs().forEach((input) => {
            input.required = hasSerial;
        });
        serialFields.querySelectorAll('input').forEach((input) => { input.disabled = !hasSerial; });

        ratingFields.classList.toggle('hidden', !hasRating);
        ratingPartNumberInputs().forEach((input) => {
            input.required = hasRating;
        });
        ratingFields.querySelectorAll('input').forEach((input) => { input.disabled = !hasRating; });

        innerFields.classList.toggle('hidden', !hasInner);
        inputs.innerPartNumber.required = hasInner;
        innerFields.querySelectorAll('input').forEach((input) => { input.disabled = !hasInner; });

        shippingFields.classList.toggle('hidden', !hasShipping);
        inputs.shippingPartNumber.required = hasShipping;
        shippingFields.querySelectorAll('input').forEach((input) => { input.disabled = !hasShipping; });

        inputs.shippingQuantity.required = hasShipping;
        inputs.shippingQuantity.disabled = !hasShipping;

        if (!hasShipping) inputs.shippingQuantity.value = '';

        inputs.serial.setCustomValidity(selectedTypes().length ? '' : 'Selecciona al menos un tipo de etiqueta.');
        setHint(
            typeHint,
            selectedTypes().length ? `Seleccionado: ${selectedTypes().join(' + ')}` : 'Selecciona al menos un tipo.',
            selectedTypes().length ? 'text-emerald-700' : 'text-slate-500',
        );

        updateTypeCards();
    }

    function updateTypeCards() {
        typeCards.forEach((card) => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            const state = card.querySelector('[data-label-type-state]');
            const isSelected = Boolean(checkbox?.checked);

            card.classList.toggle('border-red-300', isSelected);
            card.classList.toggle('bg-red-50', isSelected);
            card.classList.toggle('ring-1', isSelected);
            card.classList.toggle('ring-red-200', isSelected);
            card.classList.toggle('border-slate-200', !isSelected);

            if (state) {
                state.textContent = isSelected ? 'Seleccionada' : 'Elegir';
                state.classList.toggle('bg-red-600', isSelected);
                state.classList.toggle('text-white', isSelected);
                state.classList.toggle('bg-slate-100', !isSelected);
                state.classList.toggle('text-slate-500', !isSelected);
            }
        });
    }

    function filterLinesByType({ preserveSelection = true } = {}) {
        const selectedType = inputs.lineType.value;
        const selectedLineId = inputs.line.value;
        let visibleCount = 0;

        lineOptions.forEach((option) => {
            const isVisible = selectedType === '' || option.dataset.lineType === selectedType;
            option.hidden = !isVisible;
            option.disabled = !isVisible;
            if (isVisible) visibleCount += 1;
        });

        if (preserveSelection && selectedLineId) {
            const selectedOption = lineOptions.find((option) => option.value === selectedLineId);
            if (selectedOption?.disabled) inputs.line.value = '';
        }

        setHint(
            lineTypeHint,
            selectedType
                ? `${visibleCount} línea(s) activa(s) para ${selectedType}.`
                : `Mostrando todos los tipos (${visibleCount} líneas activas).`,
            visibleCount > 0 ? 'text-emerald-700' : 'text-red-700',
        );
    }

    function initializeLineTypeFilter() {
        const selectedLineType = inputs.line.selectedOptions?.[0]?.dataset?.lineType || '';
        if (selectedLineType) inputs.lineType.value = selectedLineType;
        filterLinesByType();
    }

    function validateQuantityAvailability() {
        inputs.quantity.setCustomValidity('');

        if (availableQuantity === null) {
            return true;
        }

        const formattedAvailability = availableQuantity.toLocaleString('es-MX');
        const requestedQuantity = Number(inputs.quantity.value || 0);

        if (!inputs.quantity.value) {
            setHint(
                quantityHint,
                `Disponible para solicitar: ${formattedAvailability}.`,
                availableQuantity > 0 ? 'text-emerald-700' : 'text-red-700',
            );
            return true;
        }

        if (requestedQuantity < 1) {
            inputs.quantity.setCustomValidity('La cantidad debe ser al menos 1.');
            setHint(quantityHint, 'Captura una cantidad de al menos 1 etiqueta.', 'text-red-700');
            return false;
        }

        if (requestedQuantity > availableQuantity) {
            inputs.quantity.setCustomValidity(`La cantidad no puede superar ${availableQuantity}.`);
            setHint(
                quantityHint,
                `Excede la disponibilidad por ${(requestedQuantity - availableQuantity).toLocaleString('es-MX')}. Reduce la cantidad a ${formattedAvailability} o menos.`,
                'text-red-700',
            );
            return false;
        }

        setHint(
            quantityHint,
            `Cantidad válida. Quedarán ${(availableQuantity - requestedQuantity).toLocaleString('es-MX')} disponibles en el Job.`,
            'text-emerald-700',
        );

        return true;
    }

    function validateDistinctPartNumbers(partNumberInputs, hint, label, isEnabled) {
        const seenValues = new Set();
        const duplicatedValues = new Set();

        partNumberInputs.forEach((input) => {
            input.setCustomValidity('');
            input.classList.remove('border-red-400', 'bg-red-50');

            if (!isEnabled) return;

            const value = normalize(input.value);
            if (!value) return;

            if (seenValues.has(value)) duplicatedValues.add(value);
            seenValues.add(value);
        });

        partNumberInputs.forEach((input) => {
            const isDuplicate = isEnabled && duplicatedValues.has(normalize(input.value));
            input.setCustomValidity(isDuplicate ? `El NP ${label} está repetido.` : '');
            input.classList.toggle('border-red-400', isDuplicate);
            input.classList.toggle('bg-red-50', isDuplicate);
        });

        if (duplicatedValues.size) {
            setHint(hint, `NP repetido: ${Array.from(duplicatedValues).join(', ')}. Conserva solo una fila por NP.`, 'text-red-700');
            return false;
        }

        setHint(hint, `No repitas el mismo NP ${label}.`);
        return true;
    }

    function updateFormGuidance() {
        updateTypeCards();
        validateDistinctPartNumbers(serialPartNumberInputs(), serialItemsHint, 'Serial', inputs.serial.checked);
        validateDistinctPartNumbers(ratingPartNumberInputs(), ratingItemsHint, 'Rating', inputs.rating.checked);
    }

    function clearJobResult(message = 'Pendiente de validar en Oracle.', { clearModels = false } = {}) {
        validatedJobNumber = '';
        availableQuantity = null;
        inputs.assembly.value = '';
        inputs.quantity.removeAttribute('max');
        inputs.quantity.setCustomValidity('');
        capacity.container.classList.add('hidden');
        capacity.container.classList.remove('grid');
        setHint(jobHint, message);
        setHint(quantityHint, 'Primero valida el Job para conocer la disponibilidad.');

        if (clearModels) {
            applyMappedModel(null, { clearManual: true, status: 'pending' });
        }
    }

    const lookupJob = debounce(async () => {
        const jobNumber = normalize(inputs.job.value);

        if (!jobNumber) {
            inputs.job.setCustomValidity('');
            clearJobResult();
            updateFormGuidance();
            return;
        }

        inputs.job.setCustomValidity('Espera a que termine la validación de Oracle.');
        setHint(jobHint, 'Buscando en Oracle…');

        try {
            const url = new URL(form.dataset.lookupUrl, window.location.origin);
            url.searchParams.set('job_number', jobNumber);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });

            if (!response.ok) throw new Error(`Lookup failed with HTTP ${response.status}`);

            const data = await response.json();

            if (!data.found) {
                clearJobResult('No encontrado en Oracle Jobs.');
                inputs.job.setCustomValidity('El Job no existe en Oracle Jobs.');
                setHint(jobHint, 'No encontrado en Oracle Jobs.', 'text-red-700');
                updateFormGuidance();
                return;
            }

            if (!data.valid_for_packaging) {
                clearJobResult('El Job no pertenece a Empaque.');
                inputs.job.setCustomValidity(data.classification_messages?.packaging || 'El Job no coincide con una regla activa de Empaque.');
                setHint(jobHint, 'El Job no pertenece a Empaque.', 'text-red-700');
                updateFormGuidance();
                return;
            }

            validatedJobNumber = normalize(data.job_number);
            availableQuantity = Number(data.available_quantity || 0);
            inputs.job.setCustomValidity('');
            inputs.assembly.value = data.assembly || '';
            inputs.po.value = data.ttl_cust_po || inputs.po.value;
            inputs.destination.value = data.ship_code || inputs.destination.value;
            inputs.quantity.max = String(Math.max(availableQuantity, 0));
            applyMappedModel(data.mapped_model, { status: 'resolved' });

            setText(capacity.jobQty, Number(data.job_qty || 0).toLocaleString('es-MX'));
            setText(capacity.reserved, Number(data.reserved_quantity || 0).toLocaleString('es-MX'));
            setText(capacity.available, availableQuantity.toLocaleString('es-MX'));
            capacity.container.classList.remove('hidden');
            capacity.container.classList.add('grid');

            setHint(jobHint, `Job válido · Assembly ${data.assembly || 'sin dato'}`, 'text-emerald-700');
            setHint(quantityHint, `Máximo disponible para solicitar: ${availableQuantity.toLocaleString('es-MX')}.`, availableQuantity > 0 ? 'text-emerald-700' : 'text-red-700');
            validateQuantityAvailability();
            updateFormGuidance();
        } catch (error) {
            clearJobResult('No fue posible consultar Oracle. Intenta nuevamente.');
            inputs.job.setCustomValidity('No fue posible validar el Job en este momento.');
            setHint(jobHint, 'No fue posible consultar Oracle. Intenta nuevamente.', 'text-red-700');
            updateFormGuidance();
        }
    }, 350);

    [
        inputs.date,
        inputs.week,
        inputs.line,
        inputs.shift,
        inputs.leader,
        inputs.model,
        inputs.po,
        inputs.destination,
        inputs.quantity,
        inputs.shippingQuantity,
        inputs.innerPartNumber,
        inputs.innerModel,
        inputs.shippingPartNumber,
        inputs.shippingModel,
    ].forEach((input) => {
        input.addEventListener('input', () => {
            validateQuantityAvailability();
            updateFormGuidance();
        });
        input.addEventListener('change', () => {
            validateQuantityAvailability();
            updateFormGuidance();
        });
    });

    function updateRemoveSerialButtons() {
        const rows = Array.from(serialPartNumbersContainer.querySelectorAll('.serial-part-number-row'));

        rows.forEach((row, index) => {
            const partNumberInput = row.querySelector('.serial-part-number-input');
            const modelInput = row.querySelector('.part-model-input');
            if (partNumberInput) partNumberInput.name = `serial_items[${index}][part_number]`;
            if (modelInput) modelInput.name = `serial_items[${index}][model]`;
            row.querySelector('.remove-serial-part-number')?.classList.toggle('hidden', rows.length === 1);
        });
    }

    addSerialPartNumberButton.addEventListener('click', () => {
        const row = serialPartNumberTemplate.content.firstElementChild.cloneNode(true);
        serialPartNumbersContainer.append(row);
        syncConditionalFields();
        updateRemoveSerialButtons();
        setModelInputState(row.querySelector('.mapped-model-input'), mappedModel);
        row.querySelector('.serial-part-number-input')?.focus();
        updateFormGuidance();
    });

    serialPartNumbersContainer.addEventListener('input', updateFormGuidance);
    serialPartNumbersContainer.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-serial-part-number');

        if (!removeButton) return;

        removeButton.closest('.serial-part-number-row')?.remove();
        updateRemoveSerialButtons();
        updateFormGuidance();
    });

    function updateRemoveRatingButtons() {
        const rows = Array.from(ratingPartNumbersContainer.querySelectorAll('.rating-part-number-row'));

        rows.forEach((row, index) => {
            const partNumberInput = row.querySelector('.rating-part-number-input');
            const modelInput = row.querySelector('.part-model-input');
            if (partNumberInput) partNumberInput.name = `rating_items[${index}][part_number]`;
            if (modelInput) modelInput.name = `rating_items[${index}][model]`;
            row.querySelector('.remove-rating-part-number')?.classList.toggle('hidden', rows.length === 1);
        });
    }

    addRatingPartNumberButton.addEventListener('click', () => {
        const row = ratingPartNumberTemplate.content.firstElementChild.cloneNode(true);
        ratingPartNumbersContainer.append(row);
        syncConditionalFields();
        updateRemoveRatingButtons();
        setModelInputState(row.querySelector('.mapped-model-input'), mappedModel);
        row.querySelector('.rating-part-number-input')?.focus();
        updateFormGuidance();
    });

    ratingPartNumbersContainer.addEventListener('input', updateFormGuidance);
    ratingPartNumbersContainer.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-rating-part-number');

        if (!removeButton) return;

        removeButton.closest('.rating-part-number-row')?.remove();
        updateRemoveRatingButtons();
        updateFormGuidance();
    });

    [inputs.serial, inputs.rating, inputs.inner, inputs.shipping].forEach((input) => {
        input.addEventListener('change', () => {
            syncConditionalFields();
            updateFormGuidance();
        });
    });

    inputs.lineType.addEventListener('change', () => {
        filterLinesByType();
        updateFormGuidance();
    });

    inputs.job.addEventListener('input', () => {
        inputs.po.value = '';
        inputs.destination.value = '';
        clearJobResult('Esperando validación de Oracle…', { clearModels: true });
        inputs.job.setCustomValidity('Espera a que termine la validación de Oracle.');
        updateFormGuidance();
        lookupJob();
    });
    inputs.job.addEventListener('change', lookupJob);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        updateRemoveSerialButtons();
        updateRemoveRatingButtons();
        syncConditionalFields();
        validateQuantityAvailability();
        updateFormGuidance();

        if (normalize(inputs.job.value) !== validatedJobNumber) {
            inputs.job.setCustomValidity('Espera a que el Job quede validado en Oracle.');
        }

        if (!form.reportValidity()) return;

        const escapeHtml = (value) => String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
        const types = selectedTypes().join(' + ');
        const html = `
            <div class="text-left text-sm">
                <ul style="margin:0;padding-left:1rem;display:grid;gap:0.25rem;">
                    <li><strong>Fecha / Semana:</strong> ${escapeHtml(inputs.date.value)} · ${escapeHtml(inputs.week.value)}</li>
                    <li><strong>Línea / Turno:</strong> ${escapeHtml(inputs.line.selectedOptions[0]?.textContent?.trim() || '')} / ${escapeHtml(inputs.shift.selectedOptions[0]?.textContent?.trim() || '')}</li>
                    <li><strong>Líder:</strong> ${escapeHtml(inputs.leader.value)}</li>
                    <li><strong>Job / Assembly:</strong> ${escapeHtml(inputs.job.value)} / ${escapeHtml(inputs.assembly.value)}</li>
                    <li><strong>Modelo general:</strong> ${escapeHtml(inputs.model.value || 'Sin dato')}</li>
                    <li><strong>PO / Destino:</strong> ${escapeHtml(inputs.po.value || 'Sin dato')} / ${escapeHtml(inputs.destination.value || 'Sin dato')}</li>
                    <li><strong>Tipos:</strong> ${escapeHtml(types)}</li>
                    <li><strong>Cantidad general:</strong> ${escapeHtml(inputs.quantity.value)}</li>
                    <li><strong>Cantidad Shipping:</strong> ${escapeHtml(inputs.shipping.checked ? inputs.shippingQuantity.value : 'No requerida')}</li>
                    <li><strong>Serial (NP / modelo):</strong> ${escapeHtml(inputs.serial.checked ? formatItems(serialItems()) : 'No requerido')}</li>
                    <li><strong>Rating (NP / modelo):</strong> ${escapeHtml(inputs.rating.checked ? formatItems(ratingItems()) : 'No requerido')}</li>
                    <li><strong>Inner (NP / modelo):</strong> ${escapeHtml(inputs.inner.checked ? `${inputs.innerPartNumber.value}${inputs.innerModel.value ? ` / ${inputs.innerModel.value}` : ''}` : 'No requerido')}</li>
                    <li><strong>Shipping (NP / modelo):</strong> ${escapeHtml(inputs.shipping.checked ? `${inputs.shippingPartNumber.value}${inputs.shippingModel.value ? ` / ${inputs.shippingModel.value}` : ''}` : 'No requerido')}</li>
                </ul>
            </div>`;

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

        if (result.isConfirmed) form.submit();
    });

    updateRemoveSerialButtons();
    updateRemoveRatingButtons();
    syncConditionalFields();
    initializeLineTypeFilter();
    applyMappedModel(null, { status: 'pending' });
    updateFormGuidance();

    if (inputs.job.value.trim()) {
        clearJobResult('Esperando validación de Oracle…');
        inputs.job.setCustomValidity('Espera a que termine la validación de Oracle.');
        lookupJob();
    }
})();
