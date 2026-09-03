import Swal from '../lib/sweetalert';

const dashboard = document.querySelector('[data-pending-request-counts-url]');
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

if (dashboard) {
    const refreshUrl = dashboard.dataset.pendingRequestCountsUrl;
    const refreshInterval = Number.parseInt(
        dashboard.dataset.pendingRequestCountsInterval ?? '',
        10,
    ) || 15000;
    let requestInProgress = false;

    const updateCount = (module, value) => {
        const badge = dashboard.querySelector(`[data-pending-request-count="${module}"]`);
        const count = Number(value);

        if (!badge || !Number.isInteger(count) || count < 0) {
            return;
        }

        badge.textContent = String(count);
        badge.classList.toggle('hidden', count === 0);
        badge.setAttribute(
            'aria-label',
            `${count} ${badge.dataset.pendingRequestLabel}`,
        );
    };

    const refreshCounts = async () => {
        if (requestInProgress || document.hidden) {
            return;
        }

        requestInProgress = true;

        try {
            const response = await fetch(refreshUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();

            Object.entries(payload.counts ?? {}).forEach(([module, count]) => {
                updateCount(module, count);
            });
        } catch {

        } finally {
            requestInProgress = false;
        }
    };

    window.setInterval(refreshCounts, refreshInterval);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshCounts();
        }
    });
}
