import Swal from '../lib/sweetalert';
import { debounce } from './utils/debounce';
import { createJobLookupHandler } from './master-requests-create/lookup';
import {
    folioValidationAlertMessage,
    refreshFolioValidation,
} from './master-requests-create/folio-validation';

const ASSEMBLY_PACKAGING = 'assembly_packaging';

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function value(form, name) {
    return (form.elements.namedItem(name)?.value || '').trim();
}

function validLookupForRole(lookup, role) {
    const validFlag = role === 'packaging' ? 'valid_for_packaging' : 'valid_for_assembly';

    return Boolean(lookup?.found && lookup[validFlag]);
}

(function initializeManualMasterRequest() {
    const form = document.getElementById('manualMasterRequestCreate');

    if (!form) {
        return;
    }

    const fields = {
        requestType: document.getElementById('requestType'),
        jobAssembly: document.getElementById('jobAssembly'),
        jobPackaging: document.getElementById('jobPackaging'),
        qtyAssembly: document.getElementById('jobAssemblyQty'),
        qtyPackaging: document.getElementById('jobPackagingQty'),
        hintAssembly: document.getElementById('jobAssemblyHint'),
        hintPackaging: document.getElementById('jobPackagingHint'),
        officialLine: document.getElementById('officialLineDisplay'),
        oracleLine: document.getElementById('oracleLineInput'),
        poNumber: document.getElementById('poNumber'),
        destination: document.getElementById('destination'),
        modelDisplay: document.getElementById('modelDisplay'),
        modelMappingWarning: document.getElementById('modelMappingWarning'),
        localInput: document.getElementById('localInput'),
        subinventoryInput: document.getElementById('subinventoryInput'),
        validationStatus: document.getElementById('jobValidationStatus'),
        folioValidation: document.getElementById('folioLiveValidation'),
        summary: {
            jobs: document.getElementById('summaryJobs'),
            type: document.getElementById('summaryType'),
            line: document.getElementById('summaryLine'),
            inventory: document.getElementById('summaryInventory'),
            folios: document.getElementById('summaryFolios'),
        },
    };
    const lookupFields = {
        ...fields,
        poNumber: null,
        destination: null,
    };
    const jobLookups = {
        assembly: null,
        packaging: null,
    };
    let folioResult = { status: 'idle', jobs: [] };
    let isSubmitting = false;
    let modelWasManuallyEdited = Boolean(fields.modelDisplay?.value.trim());

    const requestType = () => fields.requestType?.value || '';
    const productionRole = () => requestType() === ASSEMBLY_PACKAGING ? 'packaging' : 'assembly';

    const setIfEmpty = (element, nextValue) => {
        if (element && !element.value.trim()) {
            element.value = nextValue || '';
        }
    };

    const resolveModel = () => {
        const preferredRole = validLookupForRole(jobLookups.packaging, 'packaging')
            ? 'packaging'
            : 'assembly';
        const lookup = jobLookups[preferredRole];
        const selectedRequestType = requestType();

        if (!validLookupForRole(lookup, preferredRole)) {
            return {
                ambiguous: false,
                lookup,
                model: '',
            };
        }

        let relevantRequestTypes = selectedRequestType
            ? [selectedRequestType]
            : (lookup.production_context?.allowed_request_types || [])
                .filter((type) => preferredRole === 'packaging'
                    ? type === ASSEMBLY_PACKAGING
                    : type !== ASSEMBLY_PACKAGING);

        if (!selectedRequestType && relevantRequestTypes.length === 0) {
            relevantRequestTypes = Object.keys(lookup.models_by_request_type || {})
                .filter((type) => preferredRole === 'packaging'
                    ? type === ASSEMBLY_PACKAGING
                    : type !== ASSEMBLY_PACKAGING);
        }

        const candidateModels = relevantRequestTypes
            .map((type) => lookup.models_by_request_type?.[type] || '')
            .filter(Boolean);
        const uniqueModels = [...new Set(candidateModels)];

        return {
            ambiguous: uniqueModels.length > 1,
            lookup,
            model: uniqueModels.length === 1 ? uniqueModels[0] : '',
        };
    };

    const applySuggestions = () => {
        const role = productionRole();
        const lookup = jobLookups[role];
        const context = lookup?.production_context || {};
        const isOrt = requestType() === fields.requestType?.dataset.ortAssemblyType;
        const officialLine = lookup?.found ? (context.line_code || lookup.line || '') : '';

        if (fields.officialLine) {
            fields.officialLine.textContent = officialLine || '—';
        }

        if (validLookupForRole(lookup, role)) {
            setIfEmpty(fields.oracleLine, officialLine);
            setIfEmpty(
                fields.localInput,
                isOrt ? fields.localInput?.dataset.ortDefaultValue : context.stock_locator,
            );
            setIfEmpty(
                fields.subinventoryInput,
                isOrt ? fields.subinventoryInput?.dataset.ortDefaultValue : context.subinventory,
            );
        }

        const modelResolution = resolveModel();

        if (!modelWasManuallyEdited && fields.modelDisplay) {
            fields.modelDisplay.value = modelResolution.model;
        }

        const modelMissing = Boolean(
            requestType()
            && modelResolution.lookup?.found
            && !modelResolution.model
            && !fields.modelDisplay?.value.trim(),
        );
        const modelAmbiguous = Boolean(
            !requestType()
            && modelResolution.ambiguous
            && !fields.modelDisplay?.value.trim(),
        );
        if (fields.modelMappingWarning) {
            fields.modelMappingWarning.textContent = modelAmbiguous
                ? 'Hay más de un modelo posible. Selecciona el Tipo de Master para determinarlo o captúralo manualmente.'
                : (modelMissing
                    ? 'No hay un modelo sugerido en Master Model Mapping. Captúralo manualmente.'
                    : '');
            fields.modelMappingWarning.classList.toggle('hidden', !modelMissing && !modelAmbiguous);
        }

        if (validLookupForRole(jobLookups.packaging, 'packaging')) {
            setIfEmpty(fields.poNumber, jobLookups.packaging.ttl_cust_po);
            setIfEmpty(fields.destination, jobLookups.packaging.ship_code);
        }
    };

    const renderJobStatus = () => {
        const type = requestType();
        const assemblyNumber = fields.jobAssembly?.value.trim() || '';
        const packagingNumber = fields.jobPackaging?.value.trim() || '';
        const requiredRoles = type === ASSEMBLY_PACKAGING ? ['packaging'] : ['assembly'];
        const enteredRoles = [
            assemblyNumber ? 'assembly' : null,
            packagingNumber ? 'packaging' : null,
        ].filter(Boolean);
        const rolesToValidate = [...new Set([...requiredRoles, ...enteredRoles])];
        const pending = rolesToValidate.some((role) => {
            const number = role === 'packaging' ? packagingNumber : assemblyNumber;

            return number && !jobLookups[role];
        });
        const invalid = rolesToValidate.some((role) => !validLookupForRole(jobLookups[role], role));

        if (!type || rolesToValidate.length === 0) {
            fields.validationStatus.className = 'hidden';
            return;
        }

        if (pending) {
            fields.validationStatus.className = 'rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 md:col-span-2';
            fields.validationStatus.textContent = 'Espera a que termine la validación de los Jobs en Oracle.';
            return;
        }

        if (invalid) {
            fields.validationStatus.className = 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 md:col-span-2';
            fields.validationStatus.textContent = 'Los Jobs obligatorios deben existir en Oracle y respetar su clasificación de Ensamble o Empaque.';
            return;
        }

        fields.validationStatus.className = 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 md:col-span-2';
        fields.validationStatus.textContent = 'Jobs validados. Puedes ajustar libremente los datos operativos sugeridos.';
    };

    const refreshSummary = () => {
        const assembly = fields.jobAssembly.value.trim() || '—';
        const packaging = fields.jobPackaging.value.trim() || '—';
        const selectedType = fields.requestType.selectedOptions[0]?.textContent.trim() || '—';
        const officialLine = fields.officialLine.textContent.trim() || '—';
        const selectedLine = fields.oracleLine.value.trim() || '—';
        const local = fields.localInput.value.trim() || '—';
        const subinventory = fields.subinventoryInput.value.trim() || '—';
        const foliosFrom = value(form, 'folios_from') || '—';
        const foliosTo = value(form, 'folios_to') || '—';
        const partialFolio = value(form, 'partial_folio');
        const partialQuantity = value(form, 'partial_qty');

        fields.summary.jobs.textContent = `Ensamble: ${assembly} · Empaque: ${packaging}`;
        fields.summary.type.textContent = selectedType;
        fields.summary.line.textContent = officialLine !== '—' && officialLine !== selectedLine
            ? `Oficial: ${officialLine} · Utilizada: ${selectedLine}`
            : selectedLine;
        fields.summary.inventory.textContent = `Local: ${local} · Subinventory: ${subinventory}`;
        fields.summary.folios.textContent = partialFolio || partialQuantity
            ? `${foliosFrom} al ${foliosTo} · Parcial ${partialFolio || '—'} (${partialQuantity || '—'} pzas)`
            : `${foliosFrom} al ${foliosTo}`;
    };

    const updatePage = () => {
        fields.jobAssembly.required = requestType() !== ASSEMBLY_PACKAGING;
        fields.jobPackaging.required = requestType() === ASSEMBLY_PACKAGING;
        applySuggestions();
        renderJobStatus();
        refreshSummary();
        folioResult = refreshFolioValidation(form, fields.folioValidation, jobLookups, {
            nonBlocking: true,
        });
    };

    const clearSuggestionsForRole = (role) => {
        if (role === 'packaging') {
            fields.poNumber.value = '';
            fields.destination.value = '';
        }

        const affectsProductionContext = productionRole() === role;
        const affectsModel = role === 'packaging'
            || (role === 'assembly' && !fields.jobPackaging.value.trim());

        if (affectsProductionContext) {
            fields.oracleLine.value = '';
            fields.localInput.value = '';
            fields.subinventoryInput.value = '';
        }

        if (affectsModel) {
            fields.modelDisplay.value = '';
            modelWasManuallyEdited = false;
        }
    };

    const lookupOptions = (role) => ({
        inputElement: role === 'packaging' ? fields.jobPackaging : fields.jobAssembly,
        hintElement: role === 'packaging' ? fields.hintPackaging : fields.hintAssembly,
        qtyElement: role === 'packaging' ? fields.qtyPackaging : fields.qtyAssembly,
        lookupUrl: form.dataset.lookupUrl,
        fields: lookupFields,
        refreshPreview: updatePage,
        role,
        onResolved: (data) => {
            jobLookups[role] = data;
            applySuggestions();
        },
    });
    const lookupAssembly = createJobLookupHandler(lookupOptions('assembly'));
    const lookupPackaging = createJobLookupHandler(lookupOptions('packaging'));
    const debouncedAssemblyLookup = debounce(lookupAssembly, 350);
    const debouncedPackagingLookup = debounce(lookupPackaging, 350);

    fields.jobAssembly.addEventListener('input', () => {
        jobLookups.assembly = null;
        clearSuggestionsForRole('assembly');
        updatePage();
        debouncedAssemblyLookup();
    });
    fields.jobPackaging.addEventListener('input', () => {
        jobLookups.packaging = null;
        clearSuggestionsForRole('packaging');
        updatePage();
        debouncedPackagingLookup();
    });
    fields.requestType.addEventListener('change', () => {
        fields.oracleLine.value = '';
        fields.modelDisplay.value = '';
        modelWasManuallyEdited = false;
        fields.localInput.value = '';
        fields.subinventoryInput.value = '';
        updatePage();
    });

    fields.modelDisplay.addEventListener('input', () => {
        modelWasManuallyEdited = true;
    });

    form.addEventListener('input', (event) => {
        if (![fields.jobAssembly, fields.jobPackaging].includes(event.target)) {
            refreshSummary();
            folioResult = refreshFolioValidation(form, fields.folioValidation, jobLookups, {
                nonBlocking: true,
            });
        }
    });
    form.addEventListener('change', refreshSummary);

    const jobsAreReady = () => {
        const requiredRoles = requestType() === ASSEMBLY_PACKAGING ? ['packaging'] : ['assembly'];
        const optionalEnteredRoles = [
            fields.jobAssembly.value.trim() ? 'assembly' : null,
            fields.jobPackaging.value.trim() ? 'packaging' : null,
        ].filter(Boolean);

        return [...new Set([...requiredRoles, ...optionalEnteredRoles])]
            .every((role) => validLookupForRole(jobLookups[role], role));
    };

    const confirmationHtml = () => `
        <div class="space-y-1 text-left text-sm">
            <p><strong>Tipo:</strong> ${escapeHtml(fields.requestType.selectedOptions[0]?.textContent.trim() || '—')}</p>
            <p><strong>Jobs:</strong> ${escapeHtml(value(form, 'job_assembly') || '—')} / ${escapeHtml(value(form, 'job_packaging') || '—')}</p>
            <p><strong>Línea:</strong> ${escapeHtml(value(form, 'oracle_line'))}</p>
            <p><strong>Modelo:</strong> ${escapeHtml(value(form, 'model'))}</p>
            <p><strong>Folios:</strong> ${escapeHtml(value(form, 'folios_from'))} al ${escapeHtml(value(form, 'folios_to'))}</p>
            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-900">
                <strong>Motivo:</strong> ${escapeHtml(value(form, 'manual_reason'))}
            </div>
        </div>
    `;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        const submissionAction = event.submitter?.value === 'save_and_print' ? 'save_and_print' : 'save';
        updatePage();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (!jobsAreReady()) {
            await Swal.fire({
                title: 'No se puede enviar la requisición',
                text: 'Espera la consulta y verifica que todos los Jobs capturados existan en Oracle y respeten su clasificación.',
                icon: 'error',
                confirmButtonText: 'Corregir',
            });
            return;
        }

        if (folioResult.status === 'invalid') {
            const warning = await Swal.fire({
                title: 'Advertencias de folios o cantidades',
                text: folioValidationAlertMessage(folioResult),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Entiendo, continuar',
                cancelButtonText: 'Revisar datos',
                focusCancel: true,
                reverseButtons: true,
            });

            if (!warning.isConfirmed) {
                return;
            }
        }

        const confirmation = await Swal.fire({
            title: submissionAction === 'save_and_print'
                ? '¿Guardar e imprimir el Master Manual?'
                : '¿Guardar el Master Manual?',
            html: confirmationHtml(),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: submissionAction === 'save_and_print' ? 'Sí, guardar e imprimir' : 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            focusCancel: true,
            reverseButtons: true,
        });

        if (!confirmation.isConfirmed) {
            return;
        }

        isSubmitting = true;
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'submission_action';
        actionInput.value = submissionAction;
        form.appendChild(actionInput);

        if (submissionAction === 'save_and_print') {
            form.target = '_blank';
        }

        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
        });
        form.submit();

        if (submissionAction === 'save_and_print' && form.dataset.afterPrintUrl) {
            window.setTimeout(() => window.location.assign(form.dataset.afterPrintUrl), 0);
        }
    });

    updatePage();

    if (fields.jobAssembly.value.trim()) {
        lookupAssembly();
    }

    if (fields.jobPackaging.value.trim()) {
        lookupPackaging();
    }
})();
