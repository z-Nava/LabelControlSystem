import { getValue } from './dom';

export const buildZpl = () => {
    const dummyType = getValue('dummy_type', 'rmt');
    const title = dummyType === 'rw' ? 'RW Dummy QR' : 'RMT Dummy QR';

    const qrPayload = '^DM^479124001^999999^0000000014^';
    const qrPayloadHex = String(qrPayload)
        .replaceAll('\\', '\\5C')
        .replaceAll('^', '\\5E')
        .replaceAll('~', '\\7E');

    return [
        '^XA',
        '^CI28',
        '^PW820',
        '^LL400',
        '^LH0,0',
        `^FO${getValue('title_x', 20)},${getValue('title_y', 20)}^A0N,${getValue('title_font_size', 44)},${getValue('title_font_size', 44)}^FD${title}^FS`,
        `^FO${getValue('qr_x', 30)},${getValue('qr_y', 65)}^BQ${getValue('qr_orientation', 'N')},2,${getValue('qr_magnification', 4)}`,
        `^FH\\^FDLA,${qrPayloadHex}^FS`,
        `^FO${getValue('fg_x', 360)},${getValue('fg_y', 70)}^A0N,${getValue('fg_font_size', 40)},${getValue('fg_font_size', 40)}^FD479124001^FS`,
        `^FO${getValue('job_x', 360)},${getValue('job_y', 130)}^A0N,${getValue('job_font_size', 34)},${getValue('job_font_size', 34)}^FD999999^FS`,
        `^FO${getValue('consecutive_x', 380)},${getValue('consecutive_y', 250)}^A0N,${getValue('consecutive_font_size', 58)},${getValue('consecutive_font_size', 58)}^FD0000000014^FS`,
        '^XZ',
    ].join('\n');
};
