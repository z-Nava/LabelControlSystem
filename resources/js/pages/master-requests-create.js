(function () {
    const root = document.getElementById('masterRequestCreate');

    if (!root) {
        return;
    }

    const lookupUrl = root.dataset.lookupUrl;

    const requestDate = document.getElementById('requestDate');
    const lineSelect = document.getElementById('lineSelect');
    const shiftSelect = document.getElementById('shiftSelect');
    const requestType = document.getElementById('requestType');

    const jobAssembly = document.getElementById('jobAssembly');
    const jobPackaging = document.getElementById('jobPackaging');

    const poNumber = document.getElementById('poNumber');
    const destination = document.getElementById('destination');

    const hintAssembly = document.getElementById('jobAssemblyHint');
    const hintPackaging = document.getElementById('jobPackagingHint');

    const previewDate = document.getElementById('previewDate');
    const previewLineShift = document.getElementById('previewLineShift');
    const previewJobs = document.getElementById('previewJobs');
    const previewType = document.getElementById('previewType');

    let timerAssembly = null;
    let timerPackaging = null;

    function getFormValue(name) {
        return (root.elements.namedItem(name)?.value || '').trim();
    }

    function buildConfirmationMessage() {
        const leader = getFormValue('leader_name') || '—';
        const date = getFormValue('request_date') || '—';
        const line = getSelectedText(lineSelect) || '—';
        const assemblyJob = getFormValue('job_assembly') || '—';
        const packagingJob = getFormValue('job_packaging') || '—';
        const type = getFormValue('request_type')
            ? getFormValue('request_type').replaceAll('_', ' ')
            : '—';

        const foliosFrom = getFormValue('folios_from');
        const foliosTo = getFormValue('folios_to');
        const folios = (foliosFrom || foliosTo)
            ? `${foliosFrom || '—'} al ${foliosTo || '—'}`
            : '—';

        const partialFolio = getFormValue('partial_folio');
        const partialQty = getFormValue('partial_qty');
        const partialInfo = (partialFolio || partialQty)
            ? `${partialFolio || '—'} (${partialQty || '—'} pzas)`
            : 'No';


        return [
            '¿Confirmas el envío de la requisición con estos datos?',
            '',
            `Líder: ${leader}`,
            `Fecha: ${date}`,
            `Línea: ${line}`,
            `Jobs: ${assemblyJob} / ${packagingJob}`,
            `Tipo de Master: ${type}`,
            `Folios: ${folios}`,
            `Folio parcial: ${partialInfo}`,
        ].join('\n');
    }

    function getSelectedText(selectElement) {
        if (!selectElement || !selectElement.selectedOptions || !selectElement.selectedOptions[0]) {
            return '';
        }

        return selectElement.selectedOptions[0].textContent.trim();
    }

    function refreshPreview() {
        previewDate.textContent = requestDate?.value || '—';

        const lineText = getSelectedText(lineSelect);
        const shiftText = getSelectedText(shiftSelect);
        previewLineShift.textContent = (lineText || shiftText)
            ? `${lineText || '—'} · ${shiftText || '—'}`
            : '—';

        const assemblyJob = (jobAssembly?.value || '').trim();
        const packagingJob = (jobPackaging?.value || '').trim();
        previewJobs.textContent = (assemblyJob || packagingJob)
            ? [assemblyJob, packagingJob].filter(Boolean).join(' / ')
            : '—';

        const requestTypeValue = (requestType?.value || '').trim();
        previewType.textContent = requestTypeValue ? requestTypeValue.replaceAll('_', ' ') : '—';
    }

    async function lookup(jobNumber) {
        const url = new URL(lookupUrl, window.location.origin);
        url.searchParams.set('job_number', jobNumber);

        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
            },
        });

        return await response.json();
    }

    function setHint(element, type, message) {
        const baseClass = 'text-xs mt-2 ';
        const colorClass = type === 'ok'
            ? 'text-emerald-700'
            : type === 'warn'
                ? 'text-amber-700'
                : 'text-slate-500';

        element.className = baseClass + colorClass;
        element.textContent = message || '';
    }

    async function handleAssemblyLookup() {
        const value = (jobAssembly.value || '').trim();
        refreshPreview();

        if (!value) {
            setHint(hintAssembly, 'muted', '');
            return;
        }

        setHint(hintAssembly, 'muted', 'Buscando en Oracle…');
        const data = await lookup(value);

        if (!data.found) {
            setHint(hintAssembly, 'warn', 'No encontrado en Oracle Jobs.');
            return;
        }

        setHint(hintAssembly, 'ok', `NP: ${data.assembly || '-'} | ${data.part_description || ''}`);

        if (!destination.value) {
            destination.value = data.ship_code || '';
        }

        if (!poNumber.value) {
            poNumber.value = data.ttl_cust_po || '';
        }

        refreshPreview();
    }

    async function handlePackagingLookup() {
        const value = (jobPackaging.value || '').trim();
        refreshPreview();

        if (!value) {
            setHint(hintPackaging, 'muted', '');
            return;
        }

        setHint(hintPackaging, 'muted', 'Buscando en Oracle…');
        const data = await lookup(value);

        if (!data.found) {
            setHint(hintPackaging, 'warn', 'No encontrado en Oracle Jobs.');
            return;
        }

        setHint(hintPackaging, 'ok', `NP: ${data.assembly || '-'} | ${data.part_description || ''}`);

        if (!destination.value) {
            destination.value = data.ship_code || '';
        }

        if (!poNumber.value) {
            poNumber.value = data.ttl_cust_po || '';
        }

        refreshPreview();
    }

    jobAssembly?.addEventListener('input', () => {
        clearTimeout(timerAssembly);
        timerAssembly = setTimeout(handleAssemblyLookup, 350);
    });

    jobPackaging?.addEventListener('input', () => {
        clearTimeout(timerPackaging);
        timerPackaging = setTimeout(handlePackagingLookup, 350);
    });

    [requestDate, lineSelect, shiftSelect, requestType, jobAssembly, jobPackaging].forEach((element) => {
        element?.addEventListener('change', refreshPreview);
        element?.addEventListener('input', refreshPreview);
    });

       root.addEventListener('submit', (event) => {
        const confirmed = window.confirm(buildConfirmationMessage());

        if (!confirmed) {
            event.preventDefault();
        }
    });

    refreshPreview();
})();
