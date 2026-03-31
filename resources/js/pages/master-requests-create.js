import { debounce } from './utils/debounce';
import { confirmSubmit } from './master-requests-create/confirmation';
import { getMasterRequestElements } from './master-requests-create/dom';
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

(function () {
    const page = getMasterRequestElements();

    if (!page) {
        return;
    }

    const { form, fields, preview, lookupUrl } = page;
    const jobLookupState = {
        assembly: null,
        packaging: null,
    };
    const updatePreview = () => refreshPreview(fields, preview, jobLookupState);

    let isSubmitting = false;

    initializeLineTypeFilter(fields);

    const debouncedAssemblyLookup = debounce(
        createJobLookupHandler({
            inputElement: fields.jobAssembly,
            hintElement: fields.hintAssembly,
            qtyElement: fields.qtyAssembly,
            lookupUrl,
            fields,
            refreshPreview: updatePreview,
            role: 'assembly',
            onResolved: (jobData) => {
                jobLookupState.assembly = jobData;
            },
        }),
        350,
    );

    const debouncedPackagingLookup = debounce(
        createJobLookupHandler({
            inputElement: fields.jobPackaging,
            hintElement: fields.hintPackaging,
            qtyElement: fields.qtyPackaging,
            lookupUrl,
            fields,
            refreshPreview: updatePreview,
            role: 'packaging',
            onResolved: (jobData) => {
                jobLookupState.packaging = jobData;
            },
        }),
        350,
    );

    fields.jobAssembly?.addEventListener('input', debouncedAssemblyLookup);
    fields.jobPackaging?.addEventListener('input', debouncedPackagingLookup);

    [
        fields.requestDate,
        fields.lineTypeSelect,
        fields.lineSelect,
        fields.shiftSelect,
        fields.requestType,
        fields.jobAssembly,
        fields.jobPackaging,
    ].forEach((element) => {
        element?.addEventListener('change', updatePreview);
        element?.addEventListener('input', updatePreview);
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

        const confirmed = await confirmSubmit(form, fields);

        if (!confirmed) {
            return;
        }

        isSubmitting = true;
        form.submit();
    });

    updatePreview();
})();
