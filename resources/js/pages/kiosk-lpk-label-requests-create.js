import Swal from 'sweetalert2';
import { mountCatalogPicker } from './utils/label-catalog';

(() => {
    const form = document.getElementById('kioskLpkLabelRequestCreate');

    if (!form) return;

    const labelGroupsContainer = document.getElementById('lpkLabelGroups');
    const shippingGroupsContainer = document.getElementById('lpkShippingGroups');
    const labelGroupTemplate = document.getElementById('lpkLabelGroupTemplate');
    const labelItemTemplate = document.getElementById('lpkLabelItemTemplate');
    const shippingGroupTemplate = document.getElementById('lpkShippingGroupTemplate');
    const shippingItemTemplate = document.getElementById('lpkShippingItemTemplate');
    const lookupTimers = new WeakMap();
    const lookupCache = new Map();
    const jobCatalogs = new WeakMap();
    const catalogPickers = new Map();
    let lookupSequence = 0;

    const normalize = (value) => String(value || '').trim().toUpperCase();
    const field = (container, name) => container.querySelector(`[data-field="${name}"]`);
    const setStatus = (input, message, color = 'text-slate-500') => {
        const status = input.closest('.lpk-label-item, .lpk-shipping-item')?.querySelector('[data-job-status]');

        if (!status) return;

        status.textContent = message;
        status.className = `mt-1 text-xs ${color}`;
    };
    const modelInputForJob = (jobInput) => {
        const item = jobInput.closest('.lpk-label-item, .lpk-shipping-item');

        return item ? field(item, 'model') : null;
    };
    const setModelStatus = (jobInput, message, color = 'text-slate-500') => {
        const status = jobInput.closest('.lpk-label-item, .lpk-shipping-item')?.querySelector('[data-model-status]');

        if (!status) return;

        status.textContent = message;
        status.className = `mt-1 text-xs ${color}`;
    };

    function setJobModelState(jobInput, model, { clearManual = false, status = 'resolved' } = {}) {
        const modelInput = modelInputForJob(jobInput);

        if (!modelInput) return;

        if (modelInput.dataset.modelInputConfigured !== 'true') {
            modelInput.dataset.modelInputConfigured = 'true';
            modelInput.dataset.manualPlaceholder = modelInput.placeholder || 'Modelo (opcional)';
        }

        const mappedModel = normalize(model);
        const hasMappedModel = Boolean(mappedModel);

        if (hasMappedModel) {
            modelInput.value = mappedModel;
        } else if (clearManual) {
            modelInput.value = '';
        }

        modelInput.readOnly = hasMappedModel;
        modelInput.dataset.mappedModel = hasMappedModel ? mappedModel : '';
        modelInput.placeholder = hasMappedModel
            ? 'Modelo mapeado'
            : modelInput.dataset.manualPlaceholder;
        modelInput.classList.toggle('cursor-not-allowed', hasMappedModel);
        modelInput.classList.toggle('bg-slate-100', hasMappedModel);

        if (hasMappedModel) {
            setModelStatus(
                jobInput,
                `Modelo ${mappedModel} obtenido de Master Model Mapping.`,
                'text-emerald-700',
            );
        } else if (status === 'resolved') {
            setModelStatus(
                jobInput,
                'Sin modelo mapeado; captura el modelo manualmente.',
                'text-amber-700',
            );
        } else {
            setModelStatus(jobInput, 'Se consultará en Master Model Mapping.');
        }
    }

    function appendTemplate(template, container) {
        const element = template.content.firstElementChild.cloneNode(true);
        container.append(element);

        return element;
    }

    function ensureGroupHasItem(group, shipping) {
        const items = group.querySelector('[data-items]');
        const rowSelector = shipping ? '.lpk-shipping-item' : '.lpk-label-item';

        if (!items.querySelector(rowSelector)) {
            appendTemplate(shipping ? shippingItemTemplate : labelItemTemplate, items);
        }
    }

    function catalogPicker(jobInput) {
        if (!catalogPickers.has(jobInput)) {
            const item = jobInput.closest('.lpk-label-item, .lpk-shipping-item');
            const group = jobInput.closest('.lpk-label-group, .lpk-shipping-group');
            catalogPickers.set(jobInput, mountCatalogPicker({
                container: item,
                partInput: field(group, 'part_number'),
                idInput: field(item, 'catalog_mapping_id'),
                type: group.matches('.lpk-shipping-group') ? 'shipping' : field(group, 'label_type').value,
                limitToPart: true,
                allowManual: () => document.getElementById('folioMode')?.value === 'reprint_originals',
                onSelect: () => { refreshGroupCatalog(group, false); validateGroupUniqueness(); },
            }));
        }
        return catalogPickers.get(jobInput);
    }

    function refreshGroupCatalog(group, autofill = true) {
        const type = group.matches('.lpk-shipping-group') ? 'shipping' : field(group, 'label_type').value;
        group.querySelectorAll('.lpk-job-input').forEach((input) => {
            if (jobCatalogs.has(input)) catalogPicker(input).setOptions(jobCatalogs.get(input), type, { autofill });
            else catalogPicker(input).refresh({ autofill: false });
        });
        const markets = new Set();
        for (const [input, picker] of catalogPickers) {
            if (!input.isConnected) { catalogPickers.delete(input); continue; }
            const selected = picker.selected();
            if (selected) markets.add(selected.market);
        }
        document.getElementById('lpkCatalogMarketHint').textContent = markets.size === 1
            ? 'Mercado identificado: ' + [...markets][0] + '. Cada fila conserva su relación de etiquetas para Label Room.'
            : markets.size > 1 ? 'Hay distintos mercados. Separa los Jobs que requieren folios de mercados diferentes.'
            : 'Selecciona las relaciones disponibles para identificar el Rating y mercado de cada etiqueta.';
    }

    function clearJobCatalog(input) {
        const group = input.closest('.lpk-label-group, .lpk-shipping-group');
        const others = [...group.querySelectorAll('.lpk-job-input')].filter((other) => other !== input && other.dataset.validatedJob);
        catalogPicker(input).clear({ clearPart: others.length === 0 });
        jobCatalogs.delete(input);
        refreshGroupCatalog(group, false);
    }

    function reindexForm() {
        const labelGroups = Array.from(labelGroupsContainer.querySelectorAll('.lpk-label-group'));
        const shippingGroups = Array.from(shippingGroupsContainer.querySelectorAll('.lpk-shipping-group'));

        labelGroups.forEach((group, groupIndex) => {
            ensureGroupHasItem(group, false);
            field(group, 'label_type').name = `lpk_label_groups[${groupIndex}][label_type]`;
            field(group, 'part_number').name = `lpk_label_groups[${groupIndex}][part_number]`;

            const items = Array.from(group.querySelectorAll('.lpk-label-item'));
            items.forEach((item, itemIndex) => {
                field(item, 'job_number').name = `lpk_label_groups[${groupIndex}][items][${itemIndex}][job_number]`;
                field(item, 'model').name = `lpk_label_groups[${groupIndex}][items][${itemIndex}][model]`;
                field(item, 'catalog_mapping_id').name = `lpk_label_groups[${groupIndex}][items][${itemIndex}][catalog_mapping_id]`;
                catalogPicker(field(item, 'job_number'));
                field(item, 'quantity').name = `lpk_label_groups[${groupIndex}][items][${itemIndex}][quantity]`;
                item.querySelector('.remove-lpk-label-item')?.classList.toggle('hidden', items.length === 1);
            });
        });

        shippingGroups.forEach((group, groupIndex) => {
            ensureGroupHasItem(group, true);
            field(group, 'part_number').name = `lpk_shipping_groups[${groupIndex}][part_number]`;
            field(group, 'quantity').name = `lpk_shipping_groups[${groupIndex}][quantity]`;
            field(group, 'po_number').name = `lpk_shipping_groups[${groupIndex}][po_number]`;
            field(group, 'destination').name = `lpk_shipping_groups[${groupIndex}][destination]`;

            const items = Array.from(group.querySelectorAll('.lpk-shipping-item'));
            items.forEach((item, itemIndex) => {
                field(item, 'job_number').name = `lpk_shipping_groups[${groupIndex}][items][${itemIndex}][job_number]`;
                field(item, 'model').name = `lpk_shipping_groups[${groupIndex}][items][${itemIndex}][model]`;
                field(item, 'catalog_mapping_id').name = `lpk_shipping_groups[${groupIndex}][items][${itemIndex}][catalog_mapping_id]`;
                catalogPicker(field(item, 'job_number'));
                item.querySelector('.remove-lpk-shipping-item')?.classList.toggle('hidden', items.length === 1);
            });
        });

        validateGroupUniqueness();
        updatePreview();
    }

    function validateGroupUniqueness() {
        const seen = new Set();

        labelGroupsContainer.querySelectorAll('.lpk-label-group').forEach((group) => {
            const typeInput = field(group, 'label_type');
            const partInput = field(group, 'part_number');
            const key = `${typeInput.value}|${normalize(partInput.value)}`;
            partInput.setCustomValidity('');

            if (!normalize(partInput.value)) return;

            if (seen.has(key)) {
                partInput.setCustomValidity('Este tipo y NP ya existen. Agrega los modelos y Jobs al grupo existente.');
            }

            seen.add(key);
        });
    }

    function productionReservations() {
        const reservations = new Map();

        labelGroupsContainer.querySelectorAll('.lpk-label-item').forEach((item) => {
            const jobNumber = normalize(field(item, 'job_number').value);
            const quantity = Number(field(item, 'quantity').value || 0);

            if (jobNumber && quantity > (reservations.get(jobNumber) || 0)) {
                reservations.set(jobNumber, quantity);
            }
        });

        return reservations;
    }

    function validateQuantityForRow(jobInput) {
        const item = jobInput.closest('.lpk-label-item');

        if (!item) return;

        const quantityInput = field(item, 'quantity');
        const available = Number(jobInput.dataset.availableQuantity);
        quantityInput.setCustomValidity('');
        if (document.getElementById('folioMode')?.value === 'reprint_originals') return;

        if (
            jobInput.dataset.validatedJob
            && Number.isFinite(available)
            && Number(quantityInput.value || 0) > available
        ) {
            quantityInput.setCustomValidity(`La cantidad no puede superar la disponibilidad del Job (${available}).`);
        }
    }

    async function fetchJob(jobNumber) {
        if (lookupCache.has(jobNumber)) return lookupCache.get(jobNumber);

        const lookup = (async () => {
            const url = new URL(form.dataset.lookupUrl, window.location.origin);
            url.searchParams.set('job_number', jobNumber);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });

            if (!response.ok) throw new Error(`Lookup failed with HTTP ${response.status}`);

            return response.json();
        })();

        lookupCache.set(jobNumber, lookup);

        try {
            return await lookup;
        } catch (error) {
            lookupCache.delete(jobNumber);
            throw error;
        }
    }

    async function validateJob(input) {
        const jobNumber = normalize(input.value);
        input.value = jobNumber;
        delete input.dataset.validatedJob;
        delete input.dataset.availableQuantity;

        if (!jobNumber) {
            clearJobCatalog(input);
            input.setCustomValidity('');
            setStatus(input, 'Pendiente de validar.');
            setJobModelState(input, null, { clearManual: true, status: 'pending' });
            return;
        }

        const token = String(++lookupSequence);
        input.dataset.lookupToken = token;
        input.setCustomValidity('Espera a que termine la validación de Oracle.');
        setStatus(input, 'Validando en Oracle…', 'text-blue-700');

        try {
            const data = await fetchJob(jobNumber);

            if (input.dataset.lookupToken !== token || normalize(input.value) !== jobNumber) return;

            if (!data.found) {
                clearJobCatalog(input);
                input.setCustomValidity('El Job no existe en Oracle Jobs.');
                setStatus(input, 'No encontrado en Oracle Jobs.', 'text-red-700');
                setJobModelState(input, null, { status: 'pending' });
                return;
            }

            if (!data.valid_for_packaging) {
                clearJobCatalog(input);
                input.setCustomValidity(data.classification_messages?.packaging || 'El Job no pertenece a Empaque.');
                setStatus(input, 'El Job no pertenece a Empaque.', 'text-red-700');
                setJobModelState(input, null, { status: 'pending' });
                return;
            }

            input.dataset.validatedJob = normalize(data.job_number);
            input.dataset.availableQuantity = String(Number(data.available_quantity || 0));
            input.setCustomValidity('');

            const isShipping = Boolean(input.closest('.lpk-shipping-item'));
            const detail = data.assembly ? ` · ${data.assembly}` : '';
            const availability = document.getElementById('folioMode')?.value === 'reprint_originals'
                ? 'Job válido · reimpresión con originales físicos'
                : isShipping ? 'Job válido (informativo)'
                : `Job válido · disponible ${Number(data.available_quantity || 0).toLocaleString('es-MX')}`;
            setStatus(input, `${availability}${detail}`, 'text-emerald-700');
            setJobModelState(input, data.mapped_model, { status: 'resolved' });
            jobCatalogs.set(input, data.rating_options || []);
            refreshGroupCatalog(input.closest('.lpk-label-group, .lpk-shipping-group'));

            if (isShipping) {
                const group = input.closest('.lpk-shipping-group');
                const poInput = field(group, 'po_number');
                const destinationInput = field(group, 'destination');
                if (!poInput.value && data.ttl_cust_po) poInput.value = data.ttl_cust_po;
                if (!destinationInput.value && data.ship_code) destinationInput.value = data.ship_code;
            }

            validateQuantityForRow(input);
            updatePreview();
        } catch (error) {
            if (input.dataset.lookupToken !== token || normalize(input.value) !== jobNumber) return;
            clearJobCatalog(input);
            input.setCustomValidity('No fue posible validar el Job en este momento.');
            setStatus(input, 'No fue posible consultar Oracle. Intenta nuevamente.', 'text-red-700');
            setJobModelState(input, null, { status: 'pending' });
        }
    }

    function scheduleJobValidation(input, { clearModel = true } = {}) {
        clearTimeout(lookupTimers.get(input));
        input.dataset.lookupToken = String(++lookupSequence);
        if (clearModel) clearJobCatalog(input);
        input.setCustomValidity(input.value.trim() ? 'Espera a que termine la validación de Oracle.' : '');
        delete input.dataset.validatedJob;
        delete input.dataset.availableQuantity;
        setStatus(input, input.value.trim() ? 'Esperando validación…' : 'Pendiente de validar.');
        setJobModelState(input, null, { clearManual: clearModel, status: 'pending' });
        lookupTimers.set(input, setTimeout(() => validateJob(input), 350));
    }

    function updatePreview() {
        const labelGroupCount = labelGroupsContainer.querySelectorAll('.lpk-label-group').length;
        const shippingGroupCount = shippingGroupsContainer.querySelectorAll('.lpk-shipping-group').length;
        const line = document.getElementById('lineSelect').selectedOptions[0]?.textContent?.trim();
        const shift = document.getElementById('shiftSelect').selectedOptions[0]?.textContent?.trim();
        const reservations = Array.from(productionReservations(), ([job, quantity]) => `${job}: ${quantity}`);

        document.getElementById('lpkPreviewOperation').textContent = line && shift ? `${line} / ${shift}` : 'Pendiente';
        document.getElementById('lpkPreviewLabelGroups').textContent = labelGroupCount
            ? `${labelGroupCount} grupo${labelGroupCount === 1 ? '' : 's'}`
            : 'Sin grupos de producción';
        document.getElementById('lpkPreviewShippingGroups').textContent = shippingGroupCount
            ? `${shippingGroupCount} grupo${shippingGroupCount === 1 ? '' : 's'}`
            : 'Sin Shipping';
        document.getElementById('lpkPreviewReservations').textContent = reservations.length
            ? reservations.join('\n')
            : 'Captura Jobs y cantidades';
    }

    function filterLines() {
        const typeInput = document.getElementById('lineTypeFilter');
        const lineInput = document.getElementById('lineSelect');
        const selectedType = typeInput.value;
        let visible = 0;

        Array.from(lineInput.options).slice(1).forEach((option) => {
            const show = !selectedType || option.dataset.lineType === selectedType;
            option.hidden = !show;
            option.disabled = !show;
            if (show) visible += 1;
        });

        if (lineInput.selectedOptions[0]?.disabled) lineInput.value = '';
        document.getElementById('lineTypeHint').textContent = selectedType
            ? `${visible} línea(s) activa(s) para ${selectedType}.`
            : `Mostrando todos los tipos (${visible} líneas activas).`;
        updatePreview();
    }

    document.getElementById('addLpkLabelGroup').addEventListener('click', () => {
        const group = appendTemplate(labelGroupTemplate, labelGroupsContainer);
        ensureGroupHasItem(group, false);
        reindexForm();
        field(group, 'part_number').focus();
    });

    document.getElementById('addLpkShippingGroup').addEventListener('click', () => {
        const group = appendTemplate(shippingGroupTemplate, shippingGroupsContainer);
        ensureGroupHasItem(group, true);
        reindexForm();
        field(group, 'part_number').focus();
    });

    form.addEventListener('click', (event) => {
        const addLabelItem = event.target.closest('.add-lpk-label-item');
        const addShippingItem = event.target.closest('.add-lpk-shipping-item');
        const removeLabelItem = event.target.closest('.remove-lpk-label-item');
        const removeShippingItem = event.target.closest('.remove-lpk-shipping-item');
        const removeLabelGroup = event.target.closest('.remove-lpk-label-group');
        const removeShippingGroup = event.target.closest('.remove-lpk-shipping-group');

        if (addLabelItem) {
            const item = appendTemplate(labelItemTemplate, addLabelItem.closest('.lpk-label-group').querySelector('[data-items]'));
            reindexForm();
            field(item, 'job_number').focus();
        } else if (addShippingItem) {
            const item = appendTemplate(shippingItemTemplate, addShippingItem.closest('.lpk-shipping-group').querySelector('[data-items]'));
            reindexForm();
            field(item, 'job_number').focus();
        } else if (removeLabelItem) {
            const group = removeLabelItem.closest('.lpk-label-group');
            removeLabelItem.closest('.lpk-label-item').remove();
            ensureGroupHasItem(group, false);
            reindexForm();
        } else if (removeShippingItem) {
            const group = removeShippingItem.closest('.lpk-shipping-group');
            removeShippingItem.closest('.lpk-shipping-item').remove();
            ensureGroupHasItem(group, true);
            reindexForm();
        } else if (removeLabelGroup) {
            removeLabelGroup.closest('.lpk-label-group').remove();
            reindexForm();
        } else if (removeShippingGroup) {
            removeShippingGroup.closest('.lpk-shipping-group').remove();
            reindexForm();
        }
    });

    form.addEventListener('input', (event) => {
        if (event.target.matches('.lpk-job-input')) scheduleJobValidation(event.target);
        if (event.target.matches('.lpk-item-quantity')) {
            validateQuantityForRow(event.target.closest('.lpk-label-item').querySelector('.lpk-job-input'));
        }
        if (event.target.matches('[data-field="part_number"]')) {
            const group = event.target.closest('.lpk-label-group, .lpk-shipping-group');
            delete event.target.dataset.catalogAuto;
            refreshGroupCatalog(group, false);
            validateGroupUniqueness();
        }
        updatePreview();
    });

    form.addEventListener('change', (event) => {
        if (event.target.matches('.lpk-job-input')) validateJob(event.target);
        if (event.target.matches('[data-field="label_type"]')) {
            const group = event.target.closest('.lpk-label-group');
            const partInput = field(group, 'part_number');
            if (partInput.dataset.catalogAuto === partInput.value) partInput.value = '';
            group.querySelectorAll('[data-field="catalog_mapping_id"]').forEach((input) => { input.value = ''; });
            refreshGroupCatalog(group);
        }
        validateGroupUniqueness();
        updatePreview();
    });

    document.getElementById('lineTypeFilter').addEventListener('change', filterLines);

    document.getElementById('folioMode')?.addEventListener('change', () => {
        form.querySelectorAll('[data-validated-job]').forEach(validateQuantityForRow);
        form.querySelectorAll('.lpk-label-group, .lpk-shipping-group').forEach((group) => refreshGroupCatalog(group, false));
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        reindexForm();

        const jobInputs = Array.from(form.querySelectorAll('.lpk-job-input'));
        jobInputs.forEach((input) => {
            if (normalize(input.value) !== input.dataset.validatedJob) {
                input.setCustomValidity('Espera a que el Job quede validado en Oracle.');
            }
            validateQuantityForRow(input);
        });

        if (!labelGroupsContainer.children.length && !shippingGroupsContainer.children.length) {
            await Swal.fire({
                icon: 'warning',
                title: 'Agrega al menos un grupo',
                text: 'La requisición necesita una etiqueta Serial, Rating, Inner o Shipping.',
                confirmButtonColor: '#dc2626',
            });
            return;
        }

        form.querySelectorAll('.lpk-label-group, .lpk-shipping-group').forEach((group) => refreshGroupCatalog(group, false));
        if (!form.reportValidity()) return;

        const reservations = Array.from(productionReservations(), ([job, quantity]) => `${job}: ${quantity}`).join(', ');
        const result = await Swal.fire({
            icon: 'question',
            title: '¿Confirmas crear la requisición LPK?',
            html: `<div class="text-left text-sm"><p><strong>Grupos de producción:</strong> ${labelGroupsContainer.children.length}</p><p><strong>Grupos Shipping:</strong> ${shippingGroupsContainer.children.length}</p><p><strong>Reserva única por Job:</strong> ${reservations || 'No aplica (sólo Shipping)'}</p></div>`,
            showCancelButton: true,
            confirmButtonText: 'Sí, crear requisición',
            cancelButtonText: 'Revisar datos',
            confirmButtonColor: '#dc2626',
            focusCancel: true,
        });

        if (result.isConfirmed) form.submit();
    });

    const selectedLineType = document.getElementById('lineSelect').selectedOptions[0]?.dataset?.lineType || '';
    if (selectedLineType) document.getElementById('lineTypeFilter').value = selectedLineType;
    reindexForm();
    filterLines();
    form.querySelectorAll('.lpk-job-input').forEach((input) => {
        setJobModelState(input, null, { status: 'pending' });
        if (input.value.trim()) scheduleJobValidation(input, { clearModel: false });
    });
})();
