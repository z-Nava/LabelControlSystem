import Swal from '../lib/sweetalert';

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const initializeDummyReprint = () => {
    const form = document.getElementById('dummy-reprint-form');
    if (!form) return;

    const printerNameInput = document.getElementById('printer_name');
    const selectedPrinterBox = document.getElementById('selected-printer');
    const connectPrinterButton = document.getElementById('connect-printer');
    const submitButton = document.getElementById('submit-reprint');
    const reasonInput = document.getElementById('reason');
    const selectAllButton = document.getElementById('select-all');
    const clearAllButton = document.getElementById('clear-all');

    const showMessage = (title, text, icon = 'info') => {
        void Swal.fire(title, text, icon);
    };

    const updatePrinter = (device) => {
        if (!device) return;

        const value = `${device.name || 'Unknown'} (${device.connection || 'connection'})`;
        printerNameInput.value = value;
        selectedPrinterBox.textContent = value;
    };

    connectPrinterButton?.addEventListener('click', () => {
        const browserPrint = window.BrowserPrint;
        if (!browserPrint) {
            showMessage('Browser Print no disponible', 'Instala o abre Zebra Browser Print para continuar.', 'error');
            return;
        }

        browserPrint.getDefaultDevice('printer', (device) => {
            if (device) {
                updatePrinter(device);
                return;
            }

            browserPrint.getLocalDevices((devices) => {
                const printers = (devices || []).filter((candidate) => candidate.deviceType === 'printer');
                if (!printers.length) {
                    showMessage('Sin impresoras', 'No se detectaron impresoras locales.', 'error');
                    return;
                }

                updatePrinter(printers[0]);
            }, (error) => {
                showMessage('Error', `No se pudo detectar impresora: ${error}`, 'error');
            }, 'printer');
        }, (error) => {
            showMessage('Error', `No se pudo obtener impresora predeterminada: ${error}`, 'error');
        });
    });

    const allCheckboxes = () => Array.from(document.querySelectorAll('.dummy-item'));

    selectAllButton?.addEventListener('click', () => {
        allCheckboxes().forEach((checkbox) => {
            checkbox.checked = true;
        });
    });

    clearAllButton?.addEventListener('click', () => {
        allCheckboxes().forEach((checkbox) => {
            checkbox.checked = false;
        });
    });

    submitButton?.addEventListener('click', async () => {
        const selectedCount = document.querySelectorAll('.dummy-item:checked').length;
        const printerName = printerNameInput.value.trim();
        const reason = reasonInput.value.trim();

        if (!selectedCount) {
            showMessage('Selecciona dummys', 'Debes elegir al menos 1 dummy para continuar.', 'warning');
            return;
        }

        if (!printerName) {
            showMessage('Impresora requerida', 'Primero selecciona una impresora con Browser Print.', 'warning');
            return;
        }

        if (!reason) {
            showMessage('Motivo requerido', 'Captura el motivo de reimpresión.', 'warning');
            return;
        }

        const result = await Swal.fire({
            title: '¿Confirmar reimpresión?',
            html: `Dummys seleccionados: <b>${selectedCount}</b><br>Impresora: <b>${escapeHtml(printerName)}</b>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, crear batch',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        });

        if (result.isConfirmed) {
            form.submit();
        }
    });
};

initializeDummyReprint();
