import Swal from '../lib/sweetalert';

document.addEventListener('DOMContentLoaded', () => {
    const cancellationForm = document.getElementById('cancel-master-request-form');
    const reasonInput = document.getElementById('cancel-master-request-reason');
    const cancelButtons = document.querySelectorAll('.js-cancel-master-request');

    if (!cancellationForm || !reasonInput || cancelButtons.length === 0) {
        return;
    }

    cancelButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const requestId = button.dataset.requestId;
            const cancelUrl = button.dataset.cancelUrl;

            if (!requestId || !cancelUrl) {
                return;
            }

            const result = await Swal.fire({
                title: `Cancelar requisición Master #${requestId}`,
                text: 'La requisición se conservará para auditoría y ya no podrá imprimirse.',
                icon: 'warning',
                input: 'textarea',
                inputLabel: 'Motivo de cancelación',
                inputPlaceholder: 'Describe por qué se cancela la requisición...',
                inputAttributes: {
                    maxlength: '500',
                    rows: '4',
                    'aria-label': 'Motivo de cancelación',
                },
                showCancelButton: true,
                confirmButtonText: 'Cancelar requisición',
                cancelButtonText: 'Volver',
                confirmButtonColor: '#dc2626',
                reverseButtons: true,
                focusCancel: true,
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'El motivo de cancelación es obligatorio.';
                    }

                    if (value.trim().length > 500) {
                        return 'El motivo no puede exceder 500 caracteres.';
                    }

                    return undefined;
                },
            });

            if (!result.isConfirmed) {
                return;
            }

            cancellationForm.action = cancelUrl;
            reasonInput.value = result.value.trim();
            cancellationForm.submit();
        });
    });
});
