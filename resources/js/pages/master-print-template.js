(function () {
    const { dataset } = document.body;
    const shouldRenderBarcodes = dataset.renderBarcodes === '1';
    const shouldAutoPrint = dataset.autoPrint === '1';

    function getBarcodeConfig(element) {
        return {
            format: element.dataset.format || 'CODE39',
            height: Number(element.dataset.height || 44),
            width: Number(element.dataset.width || 1.4),
            margin: Number(element.dataset.margin || 0),
            displayValue: false,
            background: '#ffffff',
        };
    }

    function renderEmptyState(element) {
        element.outerHTML = '<div class="text-[10px] text-slate-500 flex items-center justify-center min-h-[6mm]">Sin código</div>';
    }

    function renderBarcodes() {
        if (!shouldRenderBarcodes || typeof window.JsBarcode !== 'function') {
            return;
        }

        document.querySelectorAll('svg.js-barcode[data-value]').forEach((element) => {
            const barcodeValue = (element.dataset.value || '').trim().toUpperCase();

            if (!barcodeValue) {
                renderEmptyState(element);
                return;
            }

            window.JsBarcode(element, barcodeValue, getBarcodeConfig(element)); 
        });
    }

    window.addEventListener('load', () => {
        renderBarcodes();

        if (shouldAutoPrint) {
            window.print();
        }
    });
})();
