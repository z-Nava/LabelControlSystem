import { debounce } from './utils/debounce';
import { confirmSubmit } from './master-requests-create/confirmation';
import { getMasterRequestElements } from './master-requests-create/dom';
import { createJobLookupHandler } from './master-requests-create/lookup';
import { refreshPreview } from './master-requests-create/preview';
import { attachValidationClearListeners, validateBeforeSubmit } from './master-requests-create/validation';

(function () {
    const page = getMasterRequestElements();

    if (!page) {
        return;
    }

    const { form, fields, preview, lookupUrl } = page;
    const updatePreview = () => refreshPreview(fields, preview);

    let isSubmitting = false;

    const debouncedAssemblyLookup = debounce(
        createJobLookupHandler({
            inputElement: fields.jobAssembly,
            hintElement: fields.hintAssembly,
            lookupUrl,
            fields,
            refreshPreview: updatePreview,
        }),
        350,
    );

    const debouncedPackagingLookup = debounce(
        createJobLookupHandler({
            inputElement: fields.jobPackaging,
            hintElement: fields.hintPackaging,
            lookupUrl,
            fields,
            refreshPreview: updatePreview,
        }),
        350,
    );

    fields.jobAssembly?.addEventListener('input', debouncedAssemblyLookup);
    fields.jobPackaging?.addEventListener('input', debouncedPackagingLookup);

    [
        fields.requestDate,
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