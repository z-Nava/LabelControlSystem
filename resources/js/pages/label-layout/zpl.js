export const ZPL_ORIENTATIONS = Object.freeze(['N', 'R', 'I', 'B']);

const ORIENTATION_ANGLES = Object.freeze({
    N: 0,
    R: 90,
    I: 180,
    B: 270,
});

const FIELD_ORIGIN_BLOCK_PATTERN = /\^FO-?\d+,-?\d+[\s\S]*?\^FS/g;
const QR_COMMAND_PATTERN = /\^BQ([NRIB])?,\d+,(\d+)/i;

export const normalizeZplOrientation = (value, fallback = 'N') => {
    const normalizedFallback = String(fallback || 'N').trim().toUpperCase();
    const normalized = String(value || normalizedFallback).trim().toUpperCase();

    return ZPL_ORIENTATIONS.includes(normalized)
        ? normalized
        : (ZPL_ORIENTATIONS.includes(normalizedFallback) ? normalizedFallback : 'N');
};

export const getZplOrientationAngle = (value) => (
    ORIENTATION_ANGLES[normalizeZplOrientation(value)] ?? 0
);

export const millimetersToDots = (millimeters, dpi) => {
    const normalizedMillimeters = Number(millimeters);
    const normalizedDpi = Number(dpi);

    if (!Number.isFinite(normalizedMillimeters)
        || normalizedMillimeters <= 0
        || !Number.isFinite(normalizedDpi)
        || normalizedDpi <= 0) {
        return null;
    }

    return Math.max(1, Math.round((normalizedMillimeters / 25.4) * normalizedDpi));
};

export const resolvePhysicalLabelSize = ({ widthMm, heightMm, dpi = 203 } = {}) => {
    const normalizedDpi = Math.max(1, Math.round(Number(dpi) || 203));
    const widthDots = millimetersToDots(widthMm, normalizedDpi);
    const heightDots = millimetersToDots(heightMm, normalizedDpi);

    if (widthDots === null || heightDots === null) {
        return null;
    }

    return {
        dpi: normalizedDpi,
        widthMm: Number(widthMm),
        heightMm: Number(heightMm),
        widthDots,
        heightDots,
    };
};

export const buildPhysicalLabelCommands = (physicalSize) => {
    const size = resolvePhysicalLabelSize(physicalSize);

    return size
        ? [`^PW${size.widthDots}`, `^LL${size.heightDots}`, '^LH0,0', '^LS0']
        : [];
};

export const parseZplLabelSize = (zpl) => {
    const source = String(zpl || '');
    const widthDots = Number(source.match(/\^PW(\d+)/i)?.[1] || 0);
    const heightDots = Number(source.match(/\^LL(\d+)/i)?.[1] || 0);

    return {
        widthDots: widthDots > 0 ? widthDots : null,
        heightDots: heightDots > 0 ? heightDots : null,
    };
};

const finitePositions = (positions, fallback) => {
    const normalized = positions
        .map((position) => Number(position))
        .filter(Number.isFinite);

    return normalized.length ? normalized : fallback;
};

export const resolveCenteredLabelViewport = ({
    labelWidth,
    labelHeight,
    horizontalPositions = [],
    verticalPositions = [],
    movementMargin = 0,
} = {}) => {
    const width = Math.max(1, Number(labelWidth) || 1);
    const height = Math.max(1, Number(labelHeight) || 1);
    const margin = Math.max(0, Number(movementMargin) || 0);
    const horizontal = finitePositions(horizontalPositions, [0, width]);
    const vertical = finitePositions(verticalPositions, [0, height]);
    const horizontalOverflow = Math.max(
        0,
        -Math.min(...horizontal),
        Math.max(...horizontal) - width,
    );
    const verticalOverflow = Math.max(
        0,
        -Math.min(...vertical),
        Math.max(...vertical) - height,
    );
    const horizontalSpace = margin + horizontalOverflow;
    const verticalSpace = margin + verticalOverflow;

    return {
        minX: -horizontalSpace,
        maxX: width + horizontalSpace,
        minY: -verticalSpace,
        maxY: height + verticalSpace,
        width: width + (horizontalSpace * 2),
        height: height + (verticalSpace * 2),
    };
};

export const isZplQrBlock = (block) => QR_COMMAND_PATTERN.test(String(block || ''));

const estimateTextWidth = (text, fontWidth) => (
    Math.max(80, String(text || '').length * (fontWidth * 0.55))
);

export const parseZplElements = (zpl) => {
    const blocks = String(zpl || '').match(FIELD_ORIGIN_BLOCK_PATTERN) || [];

    return blocks.flatMap((block, index) => {
        const position = block.match(/\^FO(-?\d+),(-?\d+)/i);
        if (!position) return [];

        const qr = block.match(QR_COMMAND_PATTERN);
        const font = block.match(/\^A0([NRIB])?,(\d+),(\d+)/i);
        const kind = qr ? 'qr' : 'text';
        const fieldData = block.match(/\^FD([\s\S]*?)\^FS/i)?.[1] || (qr ? 'QR' : 'Texto');
        const orientation = normalizeZplOrientation(qr?.[1] || font?.[1] || 'N');
        const fontHeight = Math.max(12, Number(font?.[2] || 24));
        const fontWidth = Math.max(12, Number(font?.[3] || fontHeight));
        const qrMagnification = Math.max(1, Number(qr?.[2] || 4));
        const qrSize = Math.max(88, qrMagnification * 34);
        const width = qr ? qrSize : estimateTextWidth(fieldData, fontWidth);
        const height = qr ? qrSize : fontHeight + 12;

        return [{
            index,
            block,
            x: Number(position[1]),
            y: Number(position[2]),
            kind,
            label: qr ? 'QR' : fieldData.replace(/^LA,/, ''),
            width,
            height,
            fontSize: fontHeight,
            fontWidth,
            orientation,
            angle: getZplOrientationAngle(orientation),
            qrMagnification: qr ? qrMagnification : null,
        }];
    });
};

const normalizeOffset = (value) => {
    const normalized = Number(value);

    return Number.isFinite(normalized) ? Math.round(normalized) : 0;
};

export const applyZplKindOffsets = (zpl, offsetsByKind = {}) => (
    String(zpl || '').replace(FIELD_ORIGIN_BLOCK_PATTERN, (block) => {
        const kind = isZplQrBlock(block) ? 'qr' : 'text';
        const offset = offsetsByKind[kind] || {};
        const xOffset = normalizeOffset(offset.x);
        const yOffset = normalizeOffset(offset.y);

        return block.replace(
            /\^FO(-?\d+),(-?\d+)/i,
            (_match, x, y) => `^FO${Number(x) + xOffset},${Number(y) + yOffset}`,
        );
    })
);
