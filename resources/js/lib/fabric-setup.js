import { FabricImage, Canvas, Rect, Text } from 'fabric';

// Export centralizado para usar Fabric en módulos de página
export { Canvas, Rect, Text, FabricImage };

// Compatibilidad para páginas legacy que esperaban `window.fabric` cargado por CDN.
export const attachFabricToWindow = () => {
    if (typeof window === 'undefined') return;

    window.fabric = {
        Canvas,
        Rect,
        Text,
        FabricImage,
    };
};
