import { collectElements } from './label-requests-create/dom';
import { createJobLookupHandler } from './label-requests-create/lookup';
import {
    syncLineTypeFromSelectedLine,
    updateLabelOptionsByStandard,
    updateLineOptionsByType,
    updatePreview,
} from './label-requests-create/preview';

(function initializeLabelRequestCreateForm() {
    const elements = collectElements();

    if (!elements) {
        return;
    }

    const { inputs } = elements;

    const updateUI = () => updatePreview(elements);
    const lookupJob = createJobLookupHandler(elements, updateUI);

    [
        inputs.date,
        inputs.week,
        inputs.lineType,
        inputs.line,
        inputs.shift,
        inputs.leader,
        inputs.serialStandard,
        inputs.labelPartNumber,
        inputs.quantity,
        inputs.includeSerial,
        inputs.includeRating,
        inputs.poNumber,
        inputs.destination,
        inputs.model,
    ].forEach((element) => {
        if (!element) {
            return;
        }

        element.addEventListener('input', updateUI);
        element.addEventListener('change', updateUI);
    });

    if (inputs.lineType) {
        inputs.lineType.addEventListener('change', () => {
            updateLineOptionsByType(elements, { preserveSelection: true });
            updateUI();
        });
    }

    if (inputs.serialStandard) {
        inputs.serialStandard.addEventListener('change', () => {
            updateLabelOptionsByStandard(elements, { preserveSelection: true });
            updateUI();
        });
    }

    if (inputs.jobNumber && lookupJob) {
        inputs.jobNumber.addEventListener('input', () => {
            updateUI();
            lookupJob();
        });
        inputs.jobNumber.addEventListener('change', lookupJob);

        if (inputs.jobNumber.value.trim() !== '') {
            lookupJob();
        }
    }

    syncLineTypeFromSelectedLine(elements);
    updateLabelOptionsByStandard(elements, { preserveSelection: true });
    updateLineOptionsByType(elements, { preserveSelection: true });
    updateUI();
})();
