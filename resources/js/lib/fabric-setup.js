import { FabricImage, Canvas, Group, Rect, Text } from 'fabric';

export { Canvas, Group, Rect, Text, FabricImage };

export const attachFabricToWindow = () => {
    if (typeof window === 'undefined') return;

    window.fabric = {
        Canvas,
        Rect,
        Text,
        Group,
        FabricImage,
    };
};
