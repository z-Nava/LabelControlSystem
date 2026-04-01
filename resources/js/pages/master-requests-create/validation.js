import { clearCustomValidity, getFieldValue } from './dom';

const VALIDATED_FIELDS = ['job_assembly', 'job_packaging', 'folios_to', 'partial_folio', 'partial_qty', 'po_number', 'destination', 'local', 'notes'];
const NO_HTML_PATTERN = /<[^>]*>/;
const SAFE_TEXT_PATTERN = /^[A-Za-z0-9\-/_\s.]+$/;
const SAFE_JOB_PATTERN = /^[0-9A-Za-z-]+$/;

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
    const poNumber = getFieldValue(form, 'po_number');
    const destination = getFieldValue(form, 'destination');
    const local = getFieldValue(form, 'local');
    const notes = getFieldValue(form, 'notes');

    const assemblyInput = form.elements.namedItem('job_assembly');
    const packagingInput = form.elements.namedItem('job_packaging');
    const foliosToInput = form.elements.namedItem('folios_to');
    const partialFolioInput = form.elements.namedItem('partial_folio');
    const partialQtyInput = form.elements.namedItem('partial_qty');

    clearValidationErrors(form);

    if (requestType !== 'assembly_packaging' && !assemblyValue) {
        assemblyInput?.setCustomValidity('El Job Ensamble es obligatorio.');
        return false;
    }

    if (requestType === 'assembly_packaging' && !packagingValue) {
        packagingInput?.setCustomValidity('El Job Empaque es obligatorio para este tipo de requisición.');
        return false;
    }

    if (assemblyValue && !SAFE_JOB_PATTERN.test(assemblyValue)) {
        assemblyInput?.setCustomValidity('El Job Ensamble contiene caracteres inválidos.');
        return false;
    }

    if (packagingValue && !SAFE_JOB_PATTERN.test(packagingValue)) {
        packagingInput?.setCustomValidity('El Job Empaque contiene caracteres inválidos.');
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

    if (poNumber && (NO_HTML_PATTERN.test(poNumber) || !SAFE_TEXT_PATTERN.test(poNumber))) {
        form.elements.namedItem('po_number')?.setCustomValidity('El campo Custom PO tiene caracteres inválidos.');
        return false;
    }

    if (destination && (NO_HTML_PATTERN.test(destination) || !SAFE_TEXT_PATTERN.test(destination))) {
        form.elements.namedItem('destination')?.setCustomValidity('El destino contiene caracteres inválidos.');
        return false;
    }

    if (local && (NO_HTML_PATTERN.test(local) || !/^[A-Za-z0-9\-._]+$/.test(local))) {
        form.elements.namedItem('local')?.setCustomValidity('El campo Local contiene caracteres inválidos.');
        return false;
    }

    if (notes && NO_HTML_PATTERN.test(notes)) {
        form.elements.namedItem('notes')?.setCustomValidity('Las notas no deben incluir HTML o scripts.');
        return false;
    }

    return form.checkValidity();
}
