export const DUMMY_ALIGNMENT_ELEMENTS = ['title', 'qr', 'fg', 'job', 'consecutive'];

const TEXT_ELEMENT_ORDER = ['title', 'fg', 'job', 'consecutive'];

export const emptyDummyAlignment = () => DUMMY_ALIGNMENT_ELEMENTS.reduce((alignment, element) => ({
    ...alignment,
    [`${element}_x`]: 0,
    [`${element}_y`]: 0,
}), {});

export const normalizeDummyAlignment = (alignment = {}) => {
    const normalized = emptyDummyAlignment();

    Object.keys(normalized).forEach((field) => {
        const value = Number(alignment?.[field]);
        normalized[field] = Number.isFinite(value) ? Math.round(value) : 0;
    });

    return normalized;
};

const fieldOriginBlocks = (zpl) => String(zpl || '').match(/\^FO-?\d+,-?\d+[\s\S]*?\^FS/g) || [];

const isQrBlock = (block) => /\^BQ[A-Z]?,\d+,\d+/i.test(block);

const elementTypeForBlock = (block, textIndex) => (
    isQrBlock(block) ? 'qr' : (TEXT_ELEMENT_ORDER[textIndex] || null)
);

export const parseDummyZplElements = (zpl) => {
    let textIndex = 0;

    return fieldOriginBlocks(zpl).flatMap((block) => {
        const position = block.match(/\^FO(-?\d+),(-?\d+)/);
        if (!position) return [];

        const type = elementTypeForBlock(block, textIndex);
        if (!isQrBlock(block)) textIndex += 1;
        if (!type) return [];

        const fieldData = block.match(/\^FD([\s\S]*?)\^FS/)?.[1] || '';
        const font = block.match(/\^A0[A-Z]?,(\d+),(\d+)/i);
        const qrMagnification = Number(block.match(/\^BQ[A-Z]?,\d+,(\d+)/i)?.[1] || 4);
        const fontSize = Math.max(12, Number(font?.[1] || 24));

        return [{
            type,
            x: Number(position[1]),
            y: Number(position[2]),
            label: type === 'qr' ? 'Código QR' : fieldData.replace(/^LA,/, ''),
            width: type === 'qr'
                ? Math.max(88, qrMagnification * 34)
                : Math.max(80, fieldData.length * (fontSize * 0.55)),
            height: type === 'qr' ? Math.max(88, qrMagnification * 34) : fontSize + 12,
            fontSize,
        }];
    });
};

const moveFieldOrigin = (block, horizontalOffset, verticalOffset) => (
    block.replace(
        /\^FO(-?\d+),(-?\d+)/,
        (_match, x, y) => `^FO${Number(x) + horizontalOffset},${Number(y) + verticalOffset}`,
    )
);

export const applyDummyAlignmentToZpl = (zpl, alignment = {}) => {
    const normalized = normalizeDummyAlignment(alignment);
    let textIndex = 0;

    return String(zpl || '').replace(/\^FO-?\d+,-?\d+[\s\S]*?\^FS/g, (block) => {
        const type = elementTypeForBlock(block, textIndex);
        if (!isQrBlock(block)) textIndex += 1;
        if (!type) return block;

        return moveFieldOrigin(
            block,
            normalized[`${type}_x`],
            normalized[`${type}_y`],
        );
    });
};

export const buildDummyItemZpl = ({
    item,
    templatesByType,
    jobNumber,
    fgCode,
    alignment = {},
}) => {
    const template = templatesByType[item.dummyType];
    if (!template) {
        throw new Error(`No existe template activo para tipo ${String(item.dummyType).toUpperCase()}.`);
    }

    const qrPayloadHex = String(item.qrPayload || '')
        .replaceAll('\\', '\\5C')
        .replaceAll('^', '\\5E')
        .replaceAll('~', '\\7E');
    const normalizedQrField = `^FH\\^FDLA,${qrPayloadHex}^FS`;

    let zpl = template;
    ['N', 'R', 'I', 'B'].forEach((orientation) => {
        zpl = zpl.replaceAll(`^FD${orientation},A^DM^^FG^^JOB^^CONSECUTIVO^^^FS`, normalizedQrField);
        zpl = zpl.replaceAll(`^FD${orientation},A^RW^^FG^^JOB^^CONSECUTIVO^^^FS`, normalizedQrField);
    });

    const renderedZpl = zpl
        .replaceAll('^FH\\^FDLA,^DM^^FG^^JOB^^CONSECUTIVO^^^FS', normalizedQrField)
        .replaceAll('^FH\\^FDLA,^RW^^FG^^JOB^^CONSECUTIVO^^^FS', normalizedQrField)
        .replaceAll('^FDLA,^DM^^FG^^JOB^^CONSECUTIVO^^^FS', normalizedQrField)
        .replaceAll('^FDLA,^RW^^FG^^JOB^^CONSECUTIVO^^^FS', normalizedQrField)
        .replaceAll('^DM^^FG^^JOB^^CONSECUTIVO^^', qrPayloadHex)
        .replaceAll('^RW^^FG^^JOB^^CONSECUTIVO^^', qrPayloadHex)
        .replaceAll('^FDLA,', '^FH\\^FDLA,')
        .replaceAll('^FH\\^FH\\^FDLA,', '^FH\\^FDLA,')
        .replaceAll('^FG^', fgCode)
        .replaceAll('^JOB^', jobNumber)
        .replaceAll('^CONSECUTIVO^', item.consecutive);

    return applyDummyAlignmentToZpl(renderedZpl, alignment);
};
