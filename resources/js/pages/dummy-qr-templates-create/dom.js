export const getElement = (id) => document.getElementById(id);

export const getValue = (id, fallback = '') => getElement(id)?.value ?? fallback;

export const getNumericValue = (id, fallback = 0) => {
    const parsedValue = Number(getValue(id, fallback));
    return Number.isFinite(parsedValue) ? parsedValue : fallback;
};

export const createStatusSetter = (statusEl) => (message, isError = false) => {
    if (!statusEl) return;
    statusEl.textContent = message;
    statusEl.classList.toggle('text-red-700', isError);
    statusEl.classList.toggle('text-slate-700', !isError);
};
