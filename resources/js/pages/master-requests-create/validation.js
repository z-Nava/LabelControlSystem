import { clearCustomValidity, getFieldValue } from './dom';

const VALIDATED_FIELDS = ['job_assembly', 'job_packaging', 'folios_to', 'partial_folio', 'partial_qty'];

export function clearValidationErrors(form) {
    VALIDATED_FIELDS.forEach((fieldName) => clearCustomValidity(form, fieldName));
}

export function attachValidationClearListeners(form) {
    VALIDATED_FIELDS.forEach((fieldName) => {
        form.elements.namedItem(fieldName)?.addEventListener('input', () => clearCustomValidity(form, fieldName));
    });
}

export function validateBeforeSubmit(form) {
    const foliosFrom = Number.parseInt(getFieldValue(form, 'folios_from') || '0', 10);
    const foliosTo = Number.parseInt(getFieldValue(form, 'folios_to') || '0', 10);
    const partialFolio = getFieldValue(form, 'partial_folio');
    const partialQty = getFieldValue(form, 'partial_qty');
    const requestType = getFieldValue(form, 'request_type');
    const assemblyValue = getFieldValue(form, 'job_assembly');
    const packagingValue = getFieldValue(form, 'job_packaging');

    const assemblyInput = form.elements.namedItem('job_assembly');
    const packagingInput = form.elements.namedItem('job_packaging');
    const foliosToInput = form.elements.namedItem('folios_to');
    const partialFolioInput = form.elements.namedItem('partial_folio');
    const partialQtyInput = form.elements.namedItem('partial_qty');

    clearValidationErrors(form);

    if (!assemblyValue) {
        assemblyInput?.setCustomValidity('El Job Ensamble es obligatorio.');
        return false;
    }

    if (requestType === 'assembly_packaging' && !packagingValue) {
        packagingInput?.setCustomValidity('El Job Empaque es obligatorio para este tipo de requisición.');
        return false;
    }

    if (packagingValue && packagingValue === assemblyValue) {
        packagingInput?.setCustomValidity('El Job Empaque debe ser distinto al Job Ensamble.');
        return false;
    }

    if (foliosTo && foliosFrom && foliosTo < foliosFrom) {
        foliosToInput?.setCustomValidity('El folio final debe ser mayor o igual al inicial.');
        return false;
    }

    if ((partialFolio && !partialQty) || (!partialFolio && partialQty)) {
        partialFolioInput?.setCustomValidity('Debes capturar ambos campos parciales.');
        partialQtyInput?.setCustomValidity('Debes capturar ambos campos parciales.');
        return false;
    }

    return form.checkValidity();
}
