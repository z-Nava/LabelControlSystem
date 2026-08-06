import { debounce } from './utils/debounce';
import { confirmSubmit } from './master-requests-create/confirmation';
import { getMasterRequestElements } from './master-requests-create/dom';
import {
    getMasterRequestLineResult,
    renderMasterRequestLineResult,
    showMasterRequestLineAlert,
} from './master-requests-create/line-match';
import { createJobLookupHandler } from './master-requests-create/lookup';
import { refreshPreview } from './master-requests-create/preview';
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

function initializeLocalFromLine(fields) {
    const lineSelect = fields.lineSelect;
    const localInput = fields.localInput;
    const requestType = fields.requestType;

    if (!lineSelect || !localInput || !requestType) {
        return;
    }

    const getSelectedLineCode = () => {
        const selectedLineOption = lineSelect.selectedOptions?.[0];
        return (selectedLineOption?.dataset.lineCode || selectedLineOption?.textContent || '').trim();
    };

    const applyDefaultLocal = () => {
        const requestTypeValue = (requestType.value || '').trim();
        let defaultLocal = '';

        if (requestTypeValue === 'assembly') {
            defaultLocal = 'SMARKET-1';
        } else if (requestTypeValue === 'motors_molding') {
            defaultLocal = 'WIP-MOTORS';
        } else if (requestTypeValue === 'batteries_assembly' || requestTypeValue === 'assembly_packaging') {
            defaultLocal = getSelectedLineCode();
        }

        const localValue = (localInput.value || '').trim();
        const lastAutoValue = localInput.dataset.lastAutoValue || '';
        const canOverride = localValue === '' || localValue === lastAutoValue;

        if (!canOverride && requestTypeValue !== 'assembly' && requestTypeValue !== 'motors_molding') {
            return;
        }

        localInput.value = defaultLocal;
        localInput.dataset.lastAutoValue = defaultLocal;
    };

    lineSelect.addEventListener('change', applyDefaultLocal);
    requestType.addEventListener('change', applyDefaultLocal);
    applyDefaultLocal();
}

(function () {
    const page = getMasterRequestElements();

    if (!page) {
        return;
    }

    const { form, fields, preview, lineMatchStatus, lookupUrl } = page;
    const jobLookupState = {
        assembly: null,
        packaging: null,
    };
    let currentLineMatchResult = { status: 'idle', message: '' };
    const updatePageState = () => {
        refreshPreview(fields, preview, jobLookupState);
        currentLineMatchResult = getMasterRequestLineResult(fields, jobLookupState);
        renderMasterRequestLineResult(lineMatchStatus, currentLineMatchResult);
    };

    let isSubmitting = false;

    initializeLineTypeFilter(fields);
    initializeRequestTypeFilter(fields);
    initializeLocalFromLine(fields);

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
        fields.shiftSelect,
        fields.requestType,
    ].forEach((element) => {
        element?.addEventListener('change', updatePageState);
        element?.addEventListener('input', updatePageState);
    });

    attachValidationClearListeners(form);

    form.addEventListener('submit', async (event) => {
        if (isSubmitting) {
            return;
        }

        event.preventDefault();

        if (!validateBeforeSubmit(form)) {
            form.reportValidity();
            return;
        }

        updatePageState();

        if (['pending', 'mismatch', 'unverifiable'].includes(currentLineMatchResult.status)) {
            await showMasterRequestLineAlert(currentLineMatchResult);
            return;
        }

        const confirmed = await confirmSubmit(form, fields);

        if (!confirmed) {
            return;
        }

        isSubmitting = true;
        form.submit();
    });

    updatePageState();

    if ((fields.jobAssembly?.value || '').trim()) {
        assemblyLookup();
    }

    if ((fields.jobPackaging?.value || '').trim()) {
        packagingLookup();
    }
})();
