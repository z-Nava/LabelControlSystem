(function () {
    const form = document.getElementById('master-print-create-form');
    const selectAllButton = document.getElementById('selectAll');
    const clearAllButton = document.getElementById('clearAll');
    const folioCheckboxes = document.querySelectorAll('input[name="folio_ids[]"]');

    if (!form || !selectAllButton || !clearAllButton || folioCheckboxes.length === 0) {
        return;
    }

     function toggleAllFolios(checked) {
        folioCheckboxes.forEach((checkbox) => {
            checkbox.checked = checked;
        });
    }

    selectAllButton.addEventListener('click', () => toggleAllFolios(true));
    clearAllButton.addEventListener('click', () => toggleAllFolios(false));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const result = await window.Swal.fire({
            title: '¿Crear batch e ir a imprimir?',
            text: 'Se generará el lote de impresión con los folios seleccionados.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
        });

        if (result.isConfirmed) {
            form.submit();
        }
    });
})();
