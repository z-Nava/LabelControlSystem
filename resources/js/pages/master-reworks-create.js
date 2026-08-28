import Swal from '../lib/sweetalert';

const normalize = (value) => String(value || '').trim().toUpperCase();

const formatQuantity = (value) => {
    if (value === null || value === undefined || value === '') return '—';

    const quantity = Number(value);
    return Number.isFinite(quantity) ? new Intl.NumberFormat('es-MX').format(quantity) : String(value);
};

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const initializeMasterRework = () => {
    const form = document.getElementById('masterReworkForm');
    if (!form) return;

    const elements = {
        jobs: {
            assembly: {
                input: document.getElementById('jobAssembly'),
                quantity: document.getElementById('jobAssemblyQty'),
                hint: document.getElementById('jobAssemblyHint'),
                context: document.getElementById('jobAssemblyContext'),
            },
            packaging: {
                input: document.getElementById('jobPackaging'),
                quantity: document.getElementById('jobPackagingQty'),
                hint: document.getElementById('jobPackagingHint'),
                context: document.getElementById('jobPackagingContext'),
            },
        },
        requestType: document.getElementById('requestType'),
        requestTypeHint: document.getElementById('requestTypeHint'),
        finalLine: document.getElementById('finalLine'),
        local: document.getElementById('localInput'),
        subinventory: document.getElementById('subinventoryInput'),
        model: document.getElementById('modelInput'),
        poNumber: document.getElementById('poNumber'),
        destination: document.getElementById('destination'),
        resolvedLine: document.getElementById('resolvedLine'),
        resolvedLocal: document.getElementById('resolvedLocal'),
        resolvedSubinventory: document.getElementById('resolvedSubinventory'),
        resolvedModel: document.getElementById('resolvedModel'),
        additionalFolios: document.getElementById('additionalFolios'),
        folioSummary: document.getElementById('folioSummary'),
        newFoliosPreview: document.getElementById('newFoliosPreview'),
        partialFolio: document.getElementById('partialFolio'),
        partialQty: document.getElementById('partialQty'),
        reason: document.getElementById('reworkReason'),
    };
    const state = { assembly: null, packaging: null };
    const originalMaximum = Number.parseInt(form.dataset.originalMaxFolio || '0', 10);
    const ortType = form.dataset.ortType || 'ort_assembly';
    const preserveFinalValues = form.dataset.preserveFinalValues === '1';

    const lookupJob = async (jobNumber, role, counterpartJobNumber = '') => {
        const url = new URL(form.dataset.lookupUrl, window.location.origin);
        url.searchParams.set('job_number', jobNumber);
        url.searchParams.set('role', role);
        if (counterpartJobNumber) url.searchParams.set('counterpart_job_number', counterpartJobNumber);
        const response = await fetch(url, { headers: { Accept: 'application/json' } });

        if (!response.ok) throw new Error(`Oracle lookup failed with status ${response.status}`);
        return response.json();
    };

    const renderJob = (role, payload) => {
        const jobElements = elements.jobs[role];
        const roleLabel = role === 'assembly' ? 'Ensamble' : 'Empaque';
        const validFlag = role === 'assembly' ? 'valid_for_assembly' : 'valid_for_packaging';

        jobElements.input.setCustomValidity('');
        jobElements.quantity.textContent = `Cantidad del Job: ${formatQuantity(payload?.job_qty)}`;

        if (!payload?.found) {
            jobElements.hint.className = 'mt-1 text-xs text-amber-700';
            jobElements.hint.textContent = jobElements.input.value.trim() ? 'No encontrado en Oracle Jobs.' : '';
            jobElements.context.className = 'hidden';
            return;
        }

        if (!payload[validFlag]) {
            jobElements.input.setCustomValidity(`El Job ${roleLabel} no corresponde a una operación válida.`);
            jobElements.hint.className = 'mt-1 text-xs text-amber-700';
            jobElements.hint.textContent = `El Job no es válido para ${roleLabel}.`;
        } else {
            jobElements.hint.className = 'mt-1 text-xs text-emerald-700';
            jobElements.hint.textContent = `NP: ${payload.assembly || '—'} · ${payload.part_description || ''}`;
        }

        const context = payload.production_context || {};
        const configured = Boolean(context.production_line_configured);
        jobElements.context.className = configured
            ? 'mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm'
            : 'mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm';
        jobElements.context.textContent = context.line_code
            ? `${context.line_code}${context.line_type ? ` · ${context.line_type}` : ''} · Local ${context.stock_locator || '—'} · Subinventory ${context.subinventory || '—'}`
            : 'Oracle no reportó una línea configurada para este Job.';
    };

    const performLookup = async (role) => {
        const jobElements = elements.jobs[role];
        const jobNumber = jobElements.input.value.trim();

        if (!jobNumber) {
            state[role] = null;
            renderJob(role, null);
            return;
        }

        jobElements.hint.className = 'mt-1 text-xs text-slate-500';
        jobElements.hint.textContent = 'Buscando en Oracle…';

        try {
            const counterpartRole = role === 'assembly' ? 'packaging' : 'assembly';
            const counterpartJobNumber = elements.jobs[counterpartRole].input.value.trim();
            const payload = await lookupJob(jobNumber, role, counterpartJobNumber);
            if (jobElements.input.value.trim() !== jobNumber) return;
            state[role] = payload;
            renderJob(role, payload);
        } catch {
            state[role] = null;
            jobElements.input.setCustomValidity('No se pudo consultar Oracle. Intenta nuevamente.');
            jobElements.hint.className = 'mt-1 text-xs text-red-700';
            jobElements.hint.textContent = 'No se pudo consultar Oracle. Intenta nuevamente.';
        }
    };

    const roleForType = (requestType) => requestType === 'assembly_packaging' ? 'packaging' : 'assembly';

    const isTypeAvailable = (requestType) => {
        const role = roleForType(requestType);
        const payload = state[role];
        const validFlag = role === 'assembly' ? 'valid_for_assembly' : 'valid_for_packaging';

        return Boolean(
            payload?.found
            && payload[validFlag]
            && payload.production_context?.production_line_configured
            && (payload.production_context.allowed_request_types || []).includes(requestType)
        );
    };

    const filterRequestTypes = () => {
        const currentValue = elements.requestType.value;
        const options = Array.from(elements.requestType.options).filter((option) => option.value);
        const available = options.filter((option) => {
            const enabled = isTypeAvailable(option.value);
            option.hidden = !enabled;
            option.disabled = !enabled;
            return enabled;
        });

        if (!available.some((option) => option.value === currentValue)) {
            elements.requestType.value = '';
        }

        elements.requestTypeHint.textContent = available.length
            ? 'Selecciona uno de los tipos compatibles con los Jobs consultados.'
            : 'Los Jobs actuales no tienen un tipo de Master compatible.';
        elements.requestTypeHint.className = available.length
            ? 'mt-1 text-xs text-slate-500'
            : 'mt-1 text-xs text-amber-700';
    };

    const selectedLineOption = () => elements.finalLine.selectedOptions?.[0] || null;

    const inventorySuggestion = () => {
        if (elements.requestType.value === ortType) {
            return {
                local: form.dataset.ortLocal || '',
                subinventory: form.dataset.ortSubinventory || '',
            };
        }

        const option = selectedLineOption();
        return {
            local: option?.dataset.local || '',
            subinventory: option?.dataset.subinventory || '',
        };
    };

    const modelSuggestion = () => {
        const requestType = elements.requestType.value;
        if (!requestType) return '';

        let role = roleForType(requestType);
        if (['assembly', 'ort_assembly'].includes(requestType) && state.packaging?.found) {
            role = 'packaging';
        }

        return state[role]?.models_by_request_type?.[requestType] || '';
    };

    const applyResolution = (overwriteFinal, overwritePackagingValues = false) => {
        const requestType = elements.requestType.value;

        if (overwritePackagingValues) {
            elements.poNumber.value = normalize(state.packaging?.found ? state.packaging.ttl_cust_po : '');
            elements.destination.value = normalize(state.packaging?.found ? state.packaging.ship_code : '');
        }

        if (!requestType) {
            elements.resolvedLine.textContent = '—';
            elements.resolvedLocal.textContent = '—';
            elements.resolvedSubinventory.textContent = '—';
            elements.resolvedModel.textContent = '—';
            return;
        }

        const role = roleForType(requestType);
        const payload = state[role];
        const context = payload?.production_context || {};

        elements.resolvedLine.textContent = context.line_code || '—';

        if (overwriteFinal && context.production_line_id) {
            elements.finalLine.value = String(context.production_line_id);
        }

        const inventory = inventorySuggestion();
        const model = modelSuggestion();
        elements.resolvedLocal.textContent = inventory.local || '—';
        elements.resolvedSubinventory.textContent = inventory.subinventory || '—';
        elements.resolvedModel.textContent = model || '—';

        if (overwriteFinal) {
            elements.local.value = inventory.local;
            elements.subinventory.value = inventory.subinventory;
            elements.model.value = model;
        }

    };

    const finalFolioNumbers = () => {
        const selected = Array.from(document.querySelectorAll('.original-folio:checked'))
            .map((checkbox) => Number.parseInt(checkbox.value, 10));
        const additionalCount = Math.max(0, Number.parseInt(elements.additionalFolios.value || '0', 10));
        const added = Array.from({ length: additionalCount }, (_, index) => originalMaximum + index + 1);

        return { selected, added, all: [...selected, ...added].sort((a, b) => a - b) };
    };

    const refreshFolioPreview = () => {
        const folios = finalFolioNumbers();
        elements.folioSummary.textContent = `${folios.all.length} folio(s) en la revisión`;
        elements.newFoliosPreview.textContent = folios.added.length
            ? `Nuevos folios consecutivos: ${folios.added.map((folio) => `F${String(folio).padStart(2, '0')}`).join(', ')}`
            : 'No se agregarán folios nuevos.';

        const partialFolio = Number.parseInt(elements.partialFolio.value || '0', 10);
        elements.partialFolio.setCustomValidity(
            partialFolio && !folios.all.includes(partialFolio)
                ? 'El folio parcial debe estar seleccionado o formar parte de los folios nuevos.'
                : ''
        );
    };

    Object.entries(elements.jobs).forEach(([role, jobElements]) => {
        jobElements.input.addEventListener('change', async () => {
            await performLookup(role);
            filterRequestTypes();
            applyResolution(true, role === 'packaging');
        });
    });

    elements.requestType.addEventListener('change', () => applyResolution(true));
    [elements.poNumber, elements.destination].forEach((input) => input.addEventListener('change', () => {
        input.value = normalize(input.value);
    }));
    elements.finalLine.addEventListener('change', () => {
        const inventory = inventorySuggestion();
        elements.resolvedLocal.textContent = inventory.local || '—';
        elements.resolvedSubinventory.textContent = inventory.subinventory || '—';
        elements.local.value = inventory.local;
        elements.subinventory.value = inventory.subinventory;
    });
    document.querySelectorAll('.original-folio').forEach((checkbox) => checkbox.addEventListener('change', () => {
        if (
            !checkbox.checked
            && checkbox.dataset.isPartial === '1'
            && Number.parseInt(elements.partialFolio.value || '0', 10) === Number.parseInt(checkbox.value, 10)
        ) {
            elements.partialFolio.value = '';
            elements.partialQty.value = '';
        }
        refreshFolioPreview();
    }));
    elements.additionalFolios.addEventListener('input', refreshFolioPreview);
    elements.partialFolio.addEventListener('input', refreshFolioPreview);
    document.getElementById('selectAllFolios')?.addEventListener('click', () => {
        document.querySelectorAll('.original-folio').forEach((checkbox) => { checkbox.checked = true; });
        refreshFolioPreview();
    });
    document.getElementById('clearAllFolios')?.addEventListener('click', () => {
        document.querySelectorAll('.original-folio').forEach((checkbox) => { checkbox.checked = false; });
        refreshFolioPreview();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        refreshFolioPreview();

        if (!form.reportValidity()) return;

        const folios = finalFolioNumbers();
        if (!folios.all.length) {
            await Swal.fire('Folios requeridos', 'Selecciona al menos un folio original o agrega uno nuevo.', 'warning');
            return;
        }

        const result = await Swal.fire({
            title: '¿Guardar revisión Master?',
            html: `Se guardarán <b>${folios.all.length}</b> folio(s).<br><br>Motivo:<br><span class="text-sm">${escapeHtml(elements.reason.value)}</span>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar revisión',
            cancelButtonText: 'Revisar datos',
            reverseButtons: true,
        });

        if (result.isConfirmed) form.submit();
    });

    Promise.all([performLookup('assembly'), performLookup('packaging')]).then(() => {
        const initialType = elements.requestType.dataset.initialValue || elements.requestType.value;
        filterRequestTypes();
        if (initialType && isTypeAvailable(initialType)) elements.requestType.value = initialType;
        applyResolution(false, !preserveFinalValues);
    });
    refreshFolioPreview();
};

initializeMasterRework();
