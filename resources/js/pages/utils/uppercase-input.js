export function initializeUppercaseInput(input) {
    if (!input) {
        return;
    }

    const normalize = () => {
        const value = input.value;
        const uppercaseValue = value.toUpperCase();

        if (value === uppercaseValue) {
            return;
        }

        const { selectionStart, selectionEnd, selectionDirection } = input;
        input.value = uppercaseValue;

        if (selectionStart !== null && selectionEnd !== null) {
            input.setSelectionRange(
                value.slice(0, selectionStart).toUpperCase().length,
                value.slice(0, selectionEnd).toUpperCase().length,
                selectionDirection,
            );
        }
    };

    input.addEventListener('input', normalize);
    input.addEventListener('change', normalize);
    normalize();
}
