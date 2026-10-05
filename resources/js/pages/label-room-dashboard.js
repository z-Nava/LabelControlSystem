import Swal from '../lib/sweetalert';

const manualMasterEntry = document.querySelector('[data-manual-master-entry]');

if (manualMasterEntry) {
    manualMasterEntry.addEventListener('click', async (event) => {
        event.preventDefault();

        const result = await Swal.fire({
            title: 'Acceso a Master Manual',
            text: 'Estás por ingresar a un flujo especial. Los datos operativos podrán modificarse y la requisición afectará las cantidades y folios del Master normal. Verifica cuidadosamente la información antes de continuar. USALO EN CASOS EXTRAORDINARIOS',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Continuar a Master Manual',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d97706',
            focusCancel: true,
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            window.location.href = manualMasterEntry.href;
        }
    });
}
