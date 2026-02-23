(function () {
    const selectAllButton = document.getElementById('selectAll');
    const clearAllButton = document.getElementById('clearAll');

    if (!selectAllButton || !clearAllButton) {
        return;
    }

    function setAllFoliosChecked(checked) {
        document.querySelectorAll('input[name="folio_ids[]"]').forEach((checkbox) => {
            checkbox.checked = checked;
        });
    }

    selectAllButton.addEventListener('click', () => setAllFoliosChecked(true));
    clearAllButton.addEventListener('click', () => setAllFoliosChecked(false));
})();
