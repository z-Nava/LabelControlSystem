const dashboard = document.querySelector('[data-pending-request-counts-url]');

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
