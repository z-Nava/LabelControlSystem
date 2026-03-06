(function () {
    const selectAllButton = document.getElementById('selectAll');
    const clearAllButton = document.getElementById('clearAll');
    const folioCheckboxes = document.querySelectorAll('input[name="folio_ids[]"]');

    if (!selectAllButton || !clearAllButton || folioCheckboxes.length === 0) {
        return;
    }

     function toggleAllFolios(checked) {
        folioCheckboxes.forEach((checkbox) => {
            checkbox.checked = checked;
        });
    }

    selectAllButton.addEventListener('click', () => toggleAllFolios(true));
    clearAllButton.addEventListener('click', () => toggleAllFolios(false));
})();

