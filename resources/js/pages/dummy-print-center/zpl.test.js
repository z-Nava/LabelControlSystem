import test from 'node:test';
import assert from 'node:assert/strict';
import {
    applyDummyAlignmentToZpl,
    buildDummyItemZpl,
    normalizeDummyAlignment,
    parseDummyZplElements,
} from './zpl.js';

const template = [
    '^XA',
    '^PW820',
    '^LL400',
    '^FO20,20^A0N,44,44^FDRMT Dummy QR^FS',
    '^FO30,65^BQR,2,4',
    '^FH\\^FDLA,^DM^^FG^^JOB^^CONSECUTIVO^^^FS',
    '^FO360,70^A0N,40,40^FD^FG^^FS',
    '^FO360,130^A0N,34,34^FD^JOB^^FS',
    '^FO380,250^A0N,58,58^FD^CONSECUTIVO^^FS',
    '^XZ',
].join('\n');

const item = {
    dummyType: 'rmt',
    qrPayload: 'DM|FG^01|JOB\\02|0000000001',
    consecutive: '0000000001',
};

test('parses every editable dummy element in template order', () => {
    const elements = parseDummyZplElements(template);

    assert.deepEqual(elements.map((element) => element.type), [
        'title',
        'qr',
        'fg',
        'job',
        'consecutive',
    ]);
    assert.deepEqual(elements.map(({ x, y }) => [x, y]), [
        [20, 20],
        [30, 65],
        [360, 70],
        [360, 130],
        [380, 250],
    ]);
});

test('applies independent offsets without changing unrelated ZPL', () => {
    const adjusted = applyDummyAlignmentToZpl(template, {
        title_x: 5,
        title_y: -2,
        qr_x: -10,
        qr_y: 8,
        fg_x: 3,
        job_y: 4,
        consecutive_x: -7,
        consecutive_y: -9,
    });

    assert.match(adjusted, /\^FO25,18\^A0N,44,44\^FDRMT Dummy QR\^FS/);
    assert.match(adjusted, /\^FO20,73\^BQR,2,4/);
    assert.match(adjusted, /\^FO363,70\^A0N,40,40\^FD\^FG\^\^FS/);
    assert.match(adjusted, /\^FO360,134\^A0N,34,34\^FD\^JOB\^\^FS/);
    assert.match(adjusted, /\^FO373,241\^A0N,58,58\^FD\^CONSECUTIVO\^\^FS/);
    assert.match(adjusted, /\^PW820/);
    assert.match(adjusted, /\^LL400/);
});

test('renders payload values and uses the same offsets for final item ZPL', () => {
    const zpl = buildDummyItemZpl({
        item,
        templatesByType: { rmt: template },
        jobNumber: 'JOB-02',
        fgCode: 'FG-01',
        alignment: {
            qr_x: 6,
            qr_y: -5,
            job_x: 12,
        },
    });

    assert.match(zpl, /\^FO36,60\^BQR,2,4/);
    assert.match(zpl, /\^FO372,130\^A0N,34,34\^FDJOB-02\^FS/);
    assert.match(zpl, /\^FDFG-01\^FS/);
    assert.match(zpl, /\^FD0000000001\^FS/);
    assert.match(zpl, /\^FH\\\^FDLA,DM\|FG\\5E01\|JOB\\5C02\|0000000001\^FS/);
});

test('normalizes invalid and fractional alignment values', () => {
    const normalized = normalizeDummyAlignment({
        title_x: '4.6',
        title_y: 'invalid',
        qr_x: null,
        qr_y: -3.2,
    });

    assert.equal(normalized.title_x, 5);
    assert.equal(normalized.title_y, 0);
    assert.equal(normalized.qr_x, 0);
    assert.equal(normalized.qr_y, -3);
});
