import { FabricImage, Canvas, Rect, Text } from 'fabric';

export { Canvas, Rect, Text, FabricImage };

export const attachFabricToWindow = () => {
    if (typeof window === 'undefined') return;

    window.fabric = {
        Canvas,
        Rect,
        Text,
        FabricImage,
    };
};
