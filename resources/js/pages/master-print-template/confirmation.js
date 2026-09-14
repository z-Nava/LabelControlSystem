export function initializeMasterPrintConfirmation({
    Swal,
    windowObject = window,
    documentObject = document,
    fetchRequest = fetch,
} = {}) {
    const controls = documentObject.getElementById('master-print-confirmation');

    if (!controls || !Swal) return;

    const status = documentObject.getElementById('master-print-status');
    const confirmButton = documentObject.getElementById('master-print-confirm');
    const retryButton = documentObject.getElementById('master-print-retry');
    let confirmed = controls.dataset.confirmed === '1';
    let promptOpen = false;
    let printing = false;

    function returnToMasterCreate() {
        const returnUrl = controls.dataset.returnUrl;

        if (!returnUrl) return;

        // Print views normally open in a new tab. Closing it brings the operator
        // back to the creation form that is already open in the previous tab.
        windowObject.close();

        // A directly opened print view may not be script-closable.
        windowObject.setTimeout(() => {
            if (!windowObject.closed) windowObject.location.assign(returnUrl);
        }, 100);
    }

    async function askForConfirmation() {
        if (confirmed || promptOpen || printing) return;

        promptOpen = true;

        try {
            const result = await Swal.fire({
                title: '¿Se imprimieron correctamente las hojas?',
                text: 'Confirma solo si salieron todas las hojas de este lote.',
                width: 460,
                showCancelButton: true,
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'No, dejar pendiente',
                confirmButtonColor: '#dc2626',
                focusCancel: true,
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                allowEscapeKey: () => !Swal.isLoading(),
                preConfirm: async () => {
                    try {
                        const response = await fetchRequest(controls.dataset.confirmUrl, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': controls.dataset.csrfToken,
                            },
                        });
                        const data = await response.json().catch(() => ({}));

                        if (!response.ok || data.status !== 'printed') {
                            const validationMessage = Object.values(data.errors || {}).flat()[0];
                            throw new Error(validationMessage || (response.status === 419 || response.status === 401
                                ? 'La sesión venció. Recarga la página para confirmar; no necesitas volver a imprimir.'
                                : data.message || 'No se pudo guardar. Intenta confirmar de nuevo.'));
                        }

                        return data;
                    } catch (error) {
                        const message = error instanceof TypeError
                            ? 'No se pudo conectar. Intenta confirmar de nuevo; no necesitas volver a imprimir.'
                            : error.message;
                        const messageNode = documentObject.createElement('span');
                        messageNode.textContent = message;
                        Swal.showValidationMessage(messageNode.innerHTML);

                        return false;
                    }
                },
            });

            if (result.isConfirmed) {
                confirmed = true;
                controls.dataset.confirmed = '1';
                status.textContent = 'Impresión confirmada';
                status.classList.remove('text-amber-700');
                status.classList.add('text-green-700');
                confirmButton.hidden = true;
                retryButton.hidden = true;
                returnToMasterCreate();
            } else {
                status.textContent = 'Pendiente de confirmar. Puedes reintentar cuando estés listo.';
            }
        } finally {
            promptOpen = false;
        }
    }

    windowObject.addEventListener('beforeprint', () => {
        printing = true;
        if (promptOpen && !Swal.isLoading()) Swal.close();
    });

    windowObject.addEventListener('afterprint', () => {
        printing = false;
        // Closing the browser dialog is not proof of printing: ask the operator.
        windowObject.setTimeout(askForConfirmation, 0);
    });

    confirmButton?.addEventListener('click', askForConfirmation);
    retryButton?.addEventListener('click', () => windowObject.print());
}
