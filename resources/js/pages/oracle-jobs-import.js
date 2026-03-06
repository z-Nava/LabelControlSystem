(function () {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileName = document.getElementById('fileName');
    const submitButton = document.getElementById('submitBtn');

    if (!dropZone || !fileInput || !fileName || !submitButton) {
        return;
    }

    function setSelectedFile(file) {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        fileName.textContent = file.name;
        submitButton.disabled = false;
    }

    function preventDefaults(event) {
        event.preventDefault();
        event.stopPropagation();
    }

    function setDragState(isDragging) {
        dropZone.classList.toggle('border-red-600', isDragging);
    }


    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (event) => {
        const selectedFile = event.target.files?.[0];

        if (selectedFile) {
            setSelectedFile(selectedFile);
        }
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropZone.addEventListener(eventName, (event) => {
            preventDefaults(event);
            setDragState(true);
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropZone.addEventListener(eventName, (event) => {
            preventDefaults(event);
            setDragState(false);
        });
    });

    dropZone.addEventListener('drop', (event) => {
        const droppedFile = event.dataTransfer?.files?.[0];

        if (droppedFile) {
            setSelectedFile(droppedFile);
        }
    });
})();
