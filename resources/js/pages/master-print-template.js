(function () {
    const { dataset } = document.body;
    const shouldRenderQrs = dataset.renderQrs === '1';
    const shouldAutoPrint = dataset.autoPrint === '1';

    function renderEmptyState(element) {
        element.innerHTML = '<div class="text-[9px] text-slate-500 flex items-center justify-center h-full w-full">Sin código</div>';
    }

    function renderQrs() {
        if (!shouldRenderQrs || typeof window.QRCode !== 'function') {
            return;
        }

        document.querySelectorAll('.js-qr[data-value]').forEach((element) => {
            const qrValue = (element.dataset.value || '').trim();

            if (!qrValue) {
                renderEmptyState(element);
                return;
            }

            const size = Number(element.dataset.size || 68);

            element.innerHTML = '';

            new window.QRCode(element, {
                text: qrValue,
                width: size,
                height: size,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: window.QRCode.CorrectLevel.M,
            });

            const img = element.querySelector('img');
            const canvas = element.querySelector('canvas');

            if (img) {
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'contain';
                img.style.display = 'block';
            }

            if (canvas) {
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                canvas.style.display = 'block';
            }
        });
    }

    window.addEventListener('load', () => {
        renderQrs();

        if (shouldAutoPrint) {
            window.print();
        }
    });
})();