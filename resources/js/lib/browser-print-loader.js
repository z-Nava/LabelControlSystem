let browserPrintLoadPromise = null;

export const loadBrowserPrint = (scriptUrl) => {
    if (window.BrowserPrint) {
        return Promise.resolve(window.BrowserPrint);
    }

    if (!scriptUrl) {
        return Promise.reject(new Error('No se encontró la ruta de Zebra Browser Print.'));
    }

    if (!browserPrintLoadPromise) {
        browserPrintLoadPromise = new Promise((resolve, reject) => {
            const resolveWhenReady = () => {
                if (window.BrowserPrint) {
                    resolve(window.BrowserPrint);
                    return;
                }

                reject(new Error('Zebra Browser Print no quedó disponible en este navegador.'));
            };

            const existingScript = document.querySelector('script[data-browser-print-loader="1"]');
            if (existingScript) {
                existingScript.addEventListener('load', resolveWhenReady, { once: true });
                existingScript.addEventListener('error', () => reject(new Error('No se pudo cargar Zebra Browser Print.')), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = scriptUrl;
            script.async = true;
            script.dataset.browserPrintLoader = '1';
            script.addEventListener('load', resolveWhenReady, { once: true });
            script.addEventListener('error', () => reject(new Error('No se pudo cargar Zebra Browser Print.')), { once: true });
            document.head.appendChild(script);
        });
    }

    return browserPrintLoadPromise.catch((error) => {
        browserPrintLoadPromise = null;
        throw error;
    });
};
