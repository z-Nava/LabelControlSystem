import { debounce } from './utils/debounce';
import { confirmSubmit } from './master-requests-create/confirmation';
import { getMasterRequestElements } from './master-requests-create/dom';
import {
    getMasterRequestLineResult,
    renderMasterRequestLineResult,
    showMasterRequestLineAlert,
} from './master-requests-create/line-match';
import {
    refreshJobDrivenContext,
    showJobDrivenContextAlert,
} from './master-requests-create/job-driven-context';
import { createJobLookupHandler, updateModelField } from './master-requests-create/lookup';
import { refreshLabelRoomSummary, refreshPreview } from './master-requests-create/preview';
import { attachValidationClearListeners, validateBeforeSubmit } from './master-requests-create/validation';

function initializeLineTypeFilter(fields) {
    const lineTypeSelect = fields.lineTypeSelect;
    const lineSelect = fields.lineSelect;

    if (!lineTypeSelect || !lineSelect) {
        return;
    }

    const lineOptions = Array.from(lineSelect.options).filter((option) => option.value !== '');

    if (!lineOptions.length) {
        return;
    }

    const selectedLineOption = lineOptions.find((option) => option.value === lineSelect.value);

    if (!lineTypeSelect.value && selectedLineOption?.dataset.lineType) {
        lineTypeSelect.value = selectedLineOption.dataset.lineType;
    }

    const applyFilter = () => {
        const selectedLineType = (lineTypeSelect.value || '').trim().toLowerCase();

        lineOptions.forEach((option) => {
            const optionLineType = (option.dataset.lineType || '').trim().toLowerCase();
            const shouldShow = !selectedLineType || optionLineType === selectedLineType;

            option.hidden = !shouldShow;
            option.disabled = !shouldShow;
        });

        const selectedOption = lineOptions.find((option) => option.value === lineSelect.value);

        if (selectedOption && (selectedOption.hidden || selectedOption.disabled)) {
            lineSelect.value = '';
        }
    };

    lineTypeSelect.addEventListener('change', applyFilter);
    applyFilter();
}

function initializeRequestTypeFilter(fields) {
    const lineTypeSelect = fields.lineTypeSelect;
    const requestType = fields.requestType;

    if (!lineTypeSelect || !requestType) {
        return;
    }

    const placeholderOption = Array.from(requestType.options).find((option) => option.value === '');
    const requestTypeOptions = Array.from(requestType.options).filter((option) => option.value !== '');

    const applyFilter = () => {
        const selectedLineType = (lineTypeSelect.value || '').trim().toUpperCase();
        const previousValue = requestType.value;
        const allowedOptions = requestTypeOptions.filter((option) => {
            const lineTypes = (option.dataset.lineTypes || '')
                .split('|')
                .map((lineType) => lineType.trim().toUpperCase())
                .filter(Boolean);
            const shouldShow = selectedLineType !== '' && lineTypes.includes(selectedLineType);

            option.hidden = !shouldShow;
            option.disabled = !shouldShow;

            return shouldShow;
        });

        requestType.disabled = selectedLineType === '';

        if (placeholderOption) {
            placeholderOption.textContent = selectedLineType
                ? 'Selecciona tipo de hoja master...'
                : 'Selecciona primero el tipo de línea...';
        }

        const selectedOption = requestTypeOptions.find((option) => option.value === requestType.value);

        if (!selectedOption || selectedOption.disabled) {
            requestType.value = '';
        }

        if (allowedOptions.length === 1 && requestType.value === '') {
            requestType.value = allowedOptions[0].value;
        }

        if (requestType.value !== previousValue) {
            requestType.dispatchEvent(new Event('change'));
        }
    };

    lineTypeSelect.addEventListener('change', applyFilter);
    applyFilter();
}

function initializeInventoryDestination(fields) {
    const lineSelect = fields.lineSelect;
    const localInput = fields.localInput;
    const subinventoryInput = fields.subinventoryInput;
    const requestType = fields.requestType;

    if (!lineSelect || !localInput || !subinventoryInput || !requestType) {
        return;
    }

    const ortAssemblyType = requestType.dataset.ortAssemblyType;
    const inventoryWarning = document.getElementById('inventoryDestinationWarning');
    const inventoryWarningMessage = document.getElementById('inventoryDestinationWarningMessage');
    let previousRequestType = requestType.value;

    const getSelectedLineMapping = () => {
        const selectedLineOption = lineSelect.selectedOptions?.[0];

        return {
            oracleLine: (selectedLineOption?.dataset.lineCode || selectedLineOption?.textContent || '').trim(),
            stockLocator: (selectedLineOption?.dataset.stockLocator || '').trim(),
            subinventory: (selectedLineOption?.dataset.subinventory || '').trim(),
        };
    };

    const renderInventoryWarning = () => {
        const missingFields = [
            !localInput.value.trim() ? 'Local' : null,
            !subinventoryInput.value.trim() ? 'Subinventory' : null,
        ].filter(Boolean);
        const shouldShow = Boolean(lineSelect.value) && missingFields.length > 0;

        inventoryWarning?.classList.toggle('hidden', !shouldShow);

        if (inventoryWarningMessage) {
            inventoryWarningMessage.textContent = shouldShow
                ? `Falta ${missingFields.join(' y ')}. Puedes continuar con la requisición.`
                : '';
        }
    };

    const applyMappedInventoryDestination = () => {
        const mapping = getSelectedLineMapping();

        localInput.value = mapping.stockLocator;
        subinventoryInput.value = mapping.subinventory;
        localInput.placeholder = mapping.oracleLine && !mapping.stockLocator
            ? 'Sin mapeo en Locals by Oracle Line'
            : 'Se resolverá desde Locals by Oracle Line';
        subinventoryInput.placeholder = mapping.oracleLine && !mapping.subinventory
            ? 'Sin mapeo en Locals by Oracle Line'
            : 'Se resolverá desde Locals by Oracle Line';
        renderInventoryWarning();
    };

    const setOrtFieldState = (isOrtAssembly) => {
        [localInput, subinventoryInput].forEach((input) => {
            input.readOnly = !isOrtAssembly;
            input.required = false;
            input.classList.toggle('bg-slate-100', !isOrtAssembly);
            input.classList.toggle('bg-white', isOrtAssembly);
        });
    };

    const applyRequestTypeInventoryDestination = () => {
        const currentRequestType = requestType.value;
        const isOrtAssembly = currentRequestType === ortAssemblyType;
        const switchedToOrtAssembly = isOrtAssembly && previousRequestType !== ortAssemblyType;

        setOrtFieldState(isOrtAssembly);

        if (isOrtAssembly) {
            if (switchedToOrtAssembly || !localInput.value.trim()) {
                localInput.value = localInput.dataset.ortDefaultValue || '';
            }

            if (switchedToOrtAssembly || !subinventoryInput.value.trim()) {
                subinventoryInput.value = subinventoryInput.dataset.ortDefaultValue || '';
            }

            localInput.placeholder = 'Editable para Ensamble ORT';
            subinventoryInput.placeholder = 'Editable para Ensamble ORT';
        } else {
            applyMappedInventoryDestination();
        }

        previousRequestType = currentRequestType;
        setOrtFieldState(isOrtAssembly);
        renderInventoryWarning();
    };

    lineSelect.addEventListener('change', () => {
        if (requestType.value !== ortAssemblyType) {
            applyMappedInventoryDestination();
        }
    });
    requestType.addEventListener('change', applyRequestTypeInventoryDestination);
    localInput.addEventListener('input', renderInventoryWarning);
    subinventoryInput.addEventListener('input', renderInventoryWarning);
    applyRequestTypeInventoryDestination();
}

(function () {
    const page = getMasterRequestElements();

    if (!page) {
        return;
    }

    const {
        form,
        fields,
        preview,
        lineMatchStatus,
        jobDrivenContext,
        summary,
        lookupUrl,
        requestSource,
    } = page;
    const isLabelRoomRequest = requestSource === 'label_room';
    const jobLookupState = {
        assembly: null,
        packaging: null,
    };
    let currentValidationResult = { status: 'idle', message: '' };
    const updatePageState = () => {
        if (isLabelRoomRequest) {
            currentValidationResult = refreshJobDrivenContext(fields, jobDrivenContext, jobLookupState);
        } else {
            currentValidationResult = getMasterRequestLineResult(fields, jobLookupState);
            renderMasterRequestLineResult(lineMatchStatus, currentValidationResult);
        }

        updateModelField(fields, jobLookupState);
        refreshPreview(fields, preview, jobLookupState);
        refreshLabelRoomSummary(form, fields, summary, jobLookupState);
    };

    let isSubmitting = false;

    if (!isLabelRoomRequest) {
        initializeLineTypeFilter(fields);
        initializeRequestTypeFilter(fields);
        initializeInventoryDestination(fields);
    }

    const assemblyLookup = createJobLookupHandler({
        inputElement: fields.jobAssembly,
        hintElement: fields.hintAssembly,
        qtyElement: fields.qtyAssembly,
        lookupUrl,
        fields,
        refreshPreview: updatePageState,
        role: 'assembly',
        onResolved: (jobData) => {
            jobLookupState.assembly = jobData;
        },
    });
    const packagingLookup = createJobLookupHandler({
        inputElement: fields.jobPackaging,
        hintElement: fields.hintPackaging,
        qtyElement: fields.qtyPackaging,
        lookupUrl,
        fields,
        refreshPreview: updatePageState,
        role: 'packaging',
        onResolved: (jobData) => {
            jobLookupState.packaging = jobData;
        },
    });
    const debouncedAssemblyLookup = debounce(assemblyLookup, 350);
    const debouncedPackagingLookup = debounce(packagingLookup, 350);

    fields.jobAssembly?.addEventListener('input', () => {
        jobLookupState.assembly = null;
        updatePageState();
        debouncedAssemblyLookup();
    });
    fields.jobPackaging?.addEventListener('input', () => {
        jobLookupState.packaging = null;
        updatePageState();
        debouncedPackagingLookup();
    });

    [
        fields.requestDate,
        fields.lineTypeSelect,
        fields.lineSelect,
        fields.localInput,
        fields.subinventoryInput,
        fields.shiftSelect,
        fields.requestType,
    ].forEach((element) => {
        element?.addEventListener('change', updatePageState);
        element?.addEventListener('input', updatePageState);
    });

    if (isLabelRoomRequest) {
        fields.requestType?.addEventListener('change', () => {
            fields.requestType.dataset.initialValue = '';
        });
        form.addEventListener('input', () => refreshLabelRoomSummary(form, fields, summary, jobLookupState));
        form.addEventListener('change', () => refreshLabelRoomSummary(form, fields, summary, jobLookupState));
    }

    attachValidationClearListeners(form);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        const submissionAction = event.submitter?.value === 'save_and_print'
            ? 'save_and_print'
            : 'save';
        const shouldOpenPrintInNewTab = isLabelRoomRequest && submissionAction === 'save_and_print';

        updatePageState();

        if (!validateBeforeSubmit(form)) {
            form.reportValidity();
            return;
        }

        if (isLabelRoomRequest) {
            if (currentValidationResult.status !== 'ready') {
                await showJobDrivenContextAlert(currentValidationResult);
                return;
            }
        } else if (['pending', 'mismatch', 'unverifiable'].includes(currentValidationResult.status)) {
            await showMasterRequestLineAlert(currentValidationResult);

            return;
        }

        const confirmed = await confirmSubmit(
            form,
            fields,
            isLabelRoomRequest ? jobLookupState : {},
            submissionAction,
        );

        if (!confirmed) {
            return;
        }

        isSubmitting = true;

        if (isLabelRoomRequest) {
            const submissionActionInput = document.createElement('input');
            submissionActionInput.type = 'hidden';
            submissionActionInput.name = 'submission_action';
            submissionActionInput.value = submissionAction;
            form.appendChild(submissionActionInput);

            if (shouldOpenPrintInNewTab) {
                form.target = '_blank';
            }
        }

        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
        });

        form.submit();

        if (shouldOpenPrintInNewTab && form.dataset.afterPrintUrl) {
            window.setTimeout(() => window.location.assign(form.dataset.afterPrintUrl), 0);
        }
    });

    updatePageState();

    if ((fields.jobAssembly?.value || '').trim()) {
        assemblyLookup();
    }

    if ((fields.jobPackaging?.value || '').trim()) {
        packagingLookup();
    }
})();
