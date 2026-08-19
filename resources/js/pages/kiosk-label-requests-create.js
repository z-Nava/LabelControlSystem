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
    };
    const serialFields = byId('serialFields');
    const serialPartNumbersContainer = byId('serialPartNumbers');
    const serialPartNumberTemplate = byId('serialPartNumberTemplate');
    const addSerialPartNumberButton = byId('addSerialPartNumber');
    const ratingFields = byId('ratingFields');
    const ratingPartNumbersContainer = byId('ratingPartNumbers');
    const ratingPartNumberTemplate = byId('ratingPartNumberTemplate');
    const addRatingPartNumberButton = byId('addRatingPartNumber');
    const preview = {
        lineShift: byId('previewLineShift'),
        leader: byId('previewLeader'),
        dateWeek: byId('previewDateWeek'),
        job: byId('previewJob'),
        assembly: byId('previewAssembly'),
        model: byId('previewModel'),
        types: byId('previewTypes'),
        quantity: byId('previewQuantity'),
        serialPartNumbers: byId('previewSerialPartNumbers'),
        ratingPartNumber: byId('previewRatingPartNumber'),
        shippingQuantity: byId('previewShippingQuantity'),
        extras: byId('previewExtras'),
    };
    const jobHint = byId('jobHint');
    const lineTypeHint = byId('lineTypeHint');
    const quantityHint = byId('quantityHint');
    const typeHint = byId('typeHint');
    const capacity = {
        container: byId('jobCapacitySummary'),
        jobQty: byId('jobQtyValue'),
        reserved: byId('reservedQuantityValue'),
        available: byId('availableQuantityValue'),
    };

    let validatedJobNumber = '';
    let availableQuantity = null;

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
    const serialPartNumbers = () => serialPartNumberInputs()
        .map((input) => input.value.trim())
        .filter(Boolean);
    const ratingPartNumberInputs = () => Array.from(
        ratingPartNumbersContainer.querySelectorAll('.rating-part-number-input'),
    );
    const ratingPartNumbers = () => ratingPartNumberInputs()
        .map((input) => input.value.trim())
        .filter(Boolean);
    const lineOptions = Array.from(inputs.line.options).filter((option) => option.value !== '');

    function formatDate(value) {
        if (!value) return 'Fecha pendiente';
        const date = new Date(`${value}T00:00:00`);
        return Number.isNaN(date.getTime())
            ? value
            : new Intl.DateTimeFormat('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }).format(date);
    }

    function syncConditionalFields() {
        const hasSerial = inputs.serial.checked;
        const hasRating = inputs.rating.checked;
        const hasShipping = inputs.shipping.checked;

        serialFields.classList.toggle('hidden', !hasSerial);
        serialPartNumberInputs().forEach((input) => {
            input.required = hasSerial;
            input.disabled = !hasSerial;
        });

        ratingFields.classList.toggle('hidden', !hasRating);
        ratingPartNumberInputs().forEach((input) => {
            input.required = hasRating;
            input.disabled = !hasRating;
        });

        inputs.shippingQuantity.required = hasShipping;
        inputs.shippingQuantity.disabled = !hasShipping;

        if (!hasShipping) inputs.shippingQuantity.value = '';

        inputs.serial.setCustomValidity(selectedTypes().length ? '' : 'Selecciona al menos un tipo de etiqueta.');
        setHint(
            typeHint,
            selectedTypes().length ? `Seleccionado: ${selectedTypes().join(' + ')}` : 'Selecciona al menos un tipo.',
            selectedTypes().length ? 'text-emerald-700' : 'text-slate-500',
        );
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

        if (
            availableQuantity !== null
            && inputs.quantity.value
            && Number(inputs.quantity.value) > availableQuantity
        ) {
            inputs.quantity.setCustomValidity(`La cantidad no puede superar ${availableQuantity}.`);
            return false;
        }

        return true;
    }

    function updatePreview() {
        const line = inputs.line.selectedOptions?.[0]?.textContent?.trim() || 'Línea pendiente';
        const shift = inputs.shift.selectedOptions?.[0]?.textContent?.trim() || 'Turno pendiente';
        const types = selectedTypes();
        const extras = [
            inputs.po.value.trim() ? `PO ${inputs.po.value.trim()}` : null,
            inputs.destination.value.trim() ? `Destino ${inputs.destination.value.trim()}` : null,
        ].filter(Boolean);

        setText(preview.lineShift, `${line} / ${shift}`);
        setText(preview.leader, inputs.leader.value.trim() ? `Líder: ${inputs.leader.value.trim()}` : 'Sin líder capturado');
        setText(preview.dateWeek, `${formatDate(inputs.date.value)} · Semana ${inputs.week.value || '—'}`);
        setText(preview.job, inputs.job.value.trim() ? `Job: ${inputs.job.value.trim()}` : 'Job no capturado');
        setText(preview.assembly, inputs.assembly.value ? `Assembly: ${inputs.assembly.value}` : 'Assembly pendiente');
        setText(preview.model, inputs.model.value.trim() ? `Modelo: ${inputs.model.value.trim()}` : 'Modelo pendiente');
        setText(preview.types, types.length ? types.join(' + ') : 'Tipo pendiente');
        setText(preview.quantity, inputs.quantity.value ? `Cantidad general: ${inputs.quantity.value}` : 'Cantidad general no definida');
        setText(
            preview.serialPartNumbers,
            inputs.serial.checked
                ? `NP Serial: ${serialPartNumbers().join(', ') || 'pendiente'}`
                : 'NP de Serial no requerido',
        );
        setText(
            preview.ratingPartNumber,
            inputs.rating.checked
                ? `NP Rating: ${ratingPartNumbers().join(', ') || 'pendiente'}`
                : 'NP de Rating no requerido',
        );
        setText(
            preview.shippingQuantity,
            inputs.shipping.checked
                ? `Cantidad Shipping: ${inputs.shippingQuantity.value || 'pendiente'}`
                : 'Shipping no requerido',
        );
        setText(preview.extras, extras.length ? extras.join(' · ') : 'PO y destino pendientes.');
    }

    function clearJobResult(message = 'Pendiente de validar en Oracle.') {
        validatedJobNumber = '';
        availableQuantity = null;
        inputs.assembly.value = '';
        inputs.quantity.removeAttribute('max');
        inputs.quantity.setCustomValidity('');
        capacity.container.classList.add('hidden');
        capacity.container.classList.remove('grid');
        setHint(jobHint, message);
        setHint(quantityHint, 'Primero valida el Job para conocer la disponibilidad.');
    }

    const lookupJob = debounce(async () => {
        const jobNumber = normalize(inputs.job.value);

        if (!jobNumber) {
            inputs.job.setCustomValidity('');
            clearJobResult();
            updatePreview();
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
                updatePreview();
                return;
            }

            if (!data.valid_for_packaging) {
                clearJobResult('El Job no pertenece a Empaque.');
                inputs.job.setCustomValidity(data.classification_messages?.packaging || 'El Job no coincide con una regla activa de Empaque.');
                setHint(jobHint, 'El Job no pertenece a Empaque.', 'text-red-700');
                updatePreview();
                return;
            }

            validatedJobNumber = normalize(data.job_number);
            availableQuantity = Number(data.available_quantity || 0);
            inputs.job.setCustomValidity('');
            inputs.assembly.value = data.assembly || '';
            inputs.po.value = data.ttl_cust_po || inputs.po.value;
            inputs.destination.value = data.ship_code || inputs.destination.value;
            inputs.quantity.max = String(Math.max(availableQuantity, 0));

            setText(capacity.jobQty, Number(data.job_qty || 0).toLocaleString('es-MX'));
            setText(capacity.reserved, Number(data.reserved_quantity || 0).toLocaleString('es-MX'));
            setText(capacity.available, availableQuantity.toLocaleString('es-MX'));
            capacity.container.classList.remove('hidden');
            capacity.container.classList.add('grid');

            setHint(jobHint, `Job válido · Assembly ${data.assembly || 'sin dato'}`, 'text-emerald-700');
            setHint(quantityHint, `Máximo disponible para solicitar: ${availableQuantity.toLocaleString('es-MX')}.`, availableQuantity > 0 ? 'text-emerald-700' : 'text-red-700');
            validateQuantityAvailability();
            updatePreview();
        } catch (error) {
            clearJobResult('No fue posible consultar Oracle. Intenta nuevamente.');
            inputs.job.setCustomValidity('No fue posible validar el Job en este momento.');
            setHint(jobHint, 'No fue posible consultar Oracle. Intenta nuevamente.', 'text-red-700');
            updatePreview();
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
    ].forEach((input) => {
        input.addEventListener('input', () => {
            validateQuantityAvailability();
            updatePreview();
        });
        input.addEventListener('change', () => {
            validateQuantityAvailability();
            updatePreview();
        });
    });

    function updateRemoveSerialButtons() {
        const rows = Array.from(serialPartNumbersContainer.querySelectorAll('.serial-part-number-row'));

        rows.forEach((row) => {
            row.querySelector('.remove-serial-part-number')?.classList.toggle('hidden', rows.length === 1);
        });
    }

    addSerialPartNumberButton.addEventListener('click', () => {
        const row = serialPartNumberTemplate.content.firstElementChild.cloneNode(true);
        serialPartNumbersContainer.append(row);
        syncConditionalFields();
        updateRemoveSerialButtons();
        row.querySelector('.serial-part-number-input')?.focus();
        updatePreview();
    });

    serialPartNumbersContainer.addEventListener('input', updatePreview);
    serialPartNumbersContainer.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-serial-part-number');

        if (!removeButton) return;

        removeButton.closest('.serial-part-number-row')?.remove();
        updateRemoveSerialButtons();
        updatePreview();
    });

    function updateRemoveRatingButtons() {
        const rows = Array.from(ratingPartNumbersContainer.querySelectorAll('.rating-part-number-row'));

        rows.forEach((row) => {
            row.querySelector('.remove-rating-part-number')?.classList.toggle('hidden', rows.length === 1);
        });
    }

    addRatingPartNumberButton.addEventListener('click', () => {
        const row = ratingPartNumberTemplate.content.firstElementChild.cloneNode(true);
        ratingPartNumbersContainer.append(row);
        syncConditionalFields();
        updateRemoveRatingButtons();
        row.querySelector('.rating-part-number-input')?.focus();
        updatePreview();
    });

    ratingPartNumbersContainer.addEventListener('input', updatePreview);
    ratingPartNumbersContainer.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-rating-part-number');

        if (!removeButton) return;

        removeButton.closest('.rating-part-number-row')?.remove();
        updateRemoveRatingButtons();
        updatePreview();
    });

    [inputs.serial, inputs.rating, inputs.inner, inputs.shipping].forEach((input) => {
        input.addEventListener('change', () => {
            syncConditionalFields();
            updatePreview();
        });
    });

    inputs.lineType.addEventListener('change', () => {
        filterLinesByType();
        updatePreview();
    });

    inputs.job.addEventListener('input', () => {
        inputs.po.value = '';
        inputs.destination.value = '';
        clearJobResult('Esperando validación de Oracle…');
        inputs.job.setCustomValidity('Espera a que termine la validación de Oracle.');
        updatePreview();
        lookupJob();
    });
    inputs.job.addEventListener('change', lookupJob);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        syncConditionalFields();
        validateQuantityAvailability();

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
                    <li><strong>Modelo:</strong> ${escapeHtml(inputs.model.value)}</li>
                    <li><strong>PO / Destino:</strong> ${escapeHtml(inputs.po.value || 'Sin dato')} / ${escapeHtml(inputs.destination.value || 'Sin dato')}</li>
                    <li><strong>Tipos:</strong> ${escapeHtml(types)}</li>
                    <li><strong>Cantidad general:</strong> ${escapeHtml(inputs.quantity.value)}</li>
                    <li><strong>Cantidad Shipping:</strong> ${escapeHtml(inputs.shipping.checked ? inputs.shippingQuantity.value : 'No requerida')}</li>
                    <li><strong>NP Serial:</strong> ${escapeHtml(inputs.serial.checked ? serialPartNumbers().join(', ') : 'No requerido')}</li>
                    <li><strong>NP Rating:</strong> ${escapeHtml(inputs.rating.checked ? ratingPartNumbers().join(', ') : 'No requerido')}</li>
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
    updatePreview();

    if (inputs.job.value.trim()) {
        clearJobResult('Esperando validación de Oracle…');
        inputs.job.setCustomValidity('Espera a que termine la validación de Oracle.');
        lookupJob();
    }
})();
