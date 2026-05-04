const initDummyRequestShow = () => {
    const printButton = document.getElementById('go-to-print');
    if (!printButton) {
        return;
    }

    printButton.addEventListener('click', async (event) => {
        event.preventDefault();

        if (window.Swal?.fire) {
            const result = await window.Swal.fire({
                title: '¿Ir a imprimir ahora?',
                html: 'Vas a abrir el centro de impresión para generar un batch de esta requisición.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
            });

            if (!result?.isConfirmed) {
                return;
            }
        } else if (!window.confirm('¿Ir a imprimir ahora?')) {
            return;
        }

        window.location.href = printButton.href;
    });
};

initDummyRequestShow();
