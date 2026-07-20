export const buildDummyItemZpl = ({ item, templatesByType, jobNumber, fgCode }) => {
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

    return zpl
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
};
