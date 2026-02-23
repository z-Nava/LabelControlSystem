(function () {
    const body = document.body;
    const shouldRenderBarcodes = body.dataset.renderBarcodes === '1';
    const shouldAutoPrint = body.dataset.autoPrint === '1';

    function renderBarcodes() {
        if (!shouldRenderBarcodes || typeof window.JsBarcode !== 'function') {
            return;
        }

        document.querySelectorAll('svg.js-barcode[data-value]').forEach((element) => {
            const rawValue = (element.dataset.value || '').trim();

            if (!rawValue) {
                element.outerHTML = '<div class="text-[10px] text-slate-500 flex items-center justify-center min-h-[6mm]">Sin código</div>';
                return;
            }

            const value = rawValue.toUpperCase();
            const format = element.dataset.format || 'CODE39';
            const height = Number(element.dataset.height || 44);
            const width = Number(element.dataset.width || 1.4);
            const margin = Number(element.dataset.margin || 0);

            window.JsBarcode(element, value, {
                format,
                displayValue: false,
                margin,
                width,
                height,
                background: '#ffffff',
            });
        });
    }

    window.addEventListener('load', () => {
        renderBarcodes();

        if (shouldAutoPrint) {
            window.print();
        }
    });
})();
