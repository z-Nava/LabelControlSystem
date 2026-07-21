import Swal from '../lib/sweetalert';

document.addEventListener('DOMContentLoaded', () => {
    const createBatchPrintButton = document.getElementById('create-batch-print');

    if (!createBatchPrintButton) {
        return;
    }

    createBatchPrintButton.addEventListener('click', async (event) => {
        event.preventDefault();

        const result = await Swal.fire({
            title: '¿Crear batch e ir a imprimir?',
            text: 'Se generará el lote de impresión para esta requisición master.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
        });

        if (result.isConfirmed) {
            window.location.href = createBatchPrintButton.href;
        }
    });
});
