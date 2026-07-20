import { getNumericValue, getValue } from './dom.js';

const DEFAULT_DPI = 203;
const DEFAULT_WIDTH_DOTS = 820;
const DEFAULT_HEIGHT_DOTS = 400;
export const LEGACY_LAYOUT_DEFAULTS = Object.freeze({
    title_x: 20,
    title_y: 20,
    title_font_size: 44,
    qr_x: 30,
    qr_y: 65,
    qr_magnification: 4,
    fg_x: 360,
    fg_y: 70,
    fg_font_size: 40,
    job_x: 360,
    job_y: 130,
    job_font_size: 34,
    consecutive_x: 380,
    consecutive_y: 250,
    consecutive_font_size: 58,
});

export const millimetersToDots = (millimeters, dpi, fallback) => {
    const parsedMillimeters = Number(millimeters);
    const parsedDpi = Number(dpi);

    if (!Number.isFinite(parsedMillimeters) || parsedMillimeters <= 0
        || !Number.isFinite(parsedDpi) || parsedDpi <= 0) {
        return fallback;
    }

    return Math.max(1, Math.round((parsedMillimeters / 25.4) * parsedDpi));
};

export const scaleLegacyLayout = (layout, widthDots, heightDots) => {
    if (widthDots === DEFAULT_WIDTH_DOTS && heightDots === DEFAULT_HEIGHT_DOTS) return layout;

    const isLegacyLayout = Object.entries(LEGACY_LAYOUT_DEFAULTS)
        .every(([field, defaultValue]) => Number(layout[field] ?? defaultValue) === defaultValue);
    if (!isLegacyLayout) return layout;

    const scaleX = widthDots / DEFAULT_WIDTH_DOTS;
    const scaleY = heightDots / DEFAULT_HEIGHT_DOTS;
    const uniformScale = Math.min(scaleX, scaleY);
    const scaled = { ...layout };

    ['title_x', 'qr_x', 'fg_x', 'job_x', 'consecutive_x'].forEach((field) => {
        scaled[field] = Math.max(0, Math.round(LEGACY_LAYOUT_DEFAULTS[field] * scaleX));
    });
    ['title_y', 'qr_y', 'fg_y', 'job_y', 'consecutive_y'].forEach((field) => {
        scaled[field] = Math.max(0, Math.round(LEGACY_LAYOUT_DEFAULTS[field] * scaleY));
    });
    ['title_font_size', 'fg_font_size', 'job_font_size', 'consecutive_font_size'].forEach((field) => {
        scaled[field] = Math.max(10, Math.round(LEGACY_LAYOUT_DEFAULTS[field] * uniformScale));
    });
    scaled.qr_magnification = Math.max(1, Math.min(10, Math.round(LEGACY_LAYOUT_DEFAULTS.qr_magnification * uniformScale)));

    return scaled;
};

export const buildZpl = () => {
    const dummyType = getValue('dummy_type', 'rmt');
    const title = dummyType === 'rw' ? 'RW Dummy QR' : 'RMT Dummy QR';
    const dpi = Math.max(1, getNumericValue('dpi', DEFAULT_DPI));
    const widthDots = millimetersToDots(getValue('width_mm'), dpi, DEFAULT_WIDTH_DOTS);
    const heightDots = millimetersToDots(getValue('height_mm'), dpi, DEFAULT_HEIGHT_DOTS);
    const layout = scaleLegacyLayout(
        Object.fromEntries(Object.entries(LEGACY_LAYOUT_DEFAULTS).map(([field, fallback]) => [field, getNumericValue(field, fallback)])),
        widthDots,
        heightDots,
    );

    const qrPayload = '^DM^479124001^999999^0000000014^';
    const qrPayloadHex = String(qrPayload)
        .replaceAll('\\', '\\5C')
        .replaceAll('^', '\\5E')
        .replaceAll('~', '\\7E');

    return [
        '^XA',
        '^CI28',
        `^PW${widthDots}`,
        `^LL${heightDots}`,
        '^LH0,0',
        '^LS0',
        `^FO${layout.title_x},${layout.title_y}^A0N,${layout.title_font_size},${layout.title_font_size}^FD${title}^FS`,
        `^FO${layout.qr_x},${layout.qr_y}^BQ${getValue('qr_orientation', 'N')},2,${layout.qr_magnification}`,
        `^FH\\^FDLA,${qrPayloadHex}^FS`,
        `^FO${layout.fg_x},${layout.fg_y}^A0N,${layout.fg_font_size},${layout.fg_font_size}^FD479124001^FS`,
        `^FO${layout.job_x},${layout.job_y}^A0N,${layout.job_font_size},${layout.job_font_size}^FD999999^FS`,
        `^FO${layout.consecutive_x},${layout.consecutive_y}^A0N,${layout.consecutive_font_size},${layout.consecutive_font_size}^FD0000000014^FS`,
        '^XZ',
    ].join('\n');
};
