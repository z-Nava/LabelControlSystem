<?php

namespace App\Services\DummyQr;

class DummyQrTemplateZplBuilder
{
    public function build(array $layout): string
    {
        $title = ($layout['dummy_type'] ?? 'rmt') === 'rw' ? 'RW Dummy QR' : 'RMT Dummy QR';

        $job = '^JOB^';
        $fg = '^FG^';
        $consecutive = '^CONSECUTIVO^';
        $qrPrefix = ($layout['dummy_type'] ?? 'rmt') === 'rw' ? '^RW^' : '^DM^';
        $qrPayload = "{$qrPrefix}^FG^^JOB^^CONSECUTIVO^^";

        $titleX = (int) ($layout['title_x'] ?? 20);
        $titleY = (int) ($layout['title_y'] ?? 20);
        $titleFont = (int) ($layout['title_font_size'] ?? 44);

        $qrX = (int) ($layout['qr_x'] ?? 30);
        $qrY = (int) ($layout['qr_y'] ?? 65);
        $qrMag = max(1, min(10, (int) ($layout['qr_magnification'] ?? 4)));
        $qrOrientation = strtoupper((string) ($layout['qr_orientation'] ?? 'N'));

        $fgX = (int) ($layout['fg_x'] ?? 360);
        $fgY = (int) ($layout['fg_y'] ?? 70);
        $fgFont = (int) ($layout['fg_font_size'] ?? 40);

        $jobX = (int) ($layout['job_x'] ?? 360);
        $jobY = (int) ($layout['job_y'] ?? 130);
        $jobFont = (int) ($layout['job_font_size'] ?? 34);

        $consecutiveX = (int) ($layout['consecutive_x'] ?? 380);
        $consecutiveY = (int) ($layout['consecutive_y'] ?? 250);
        $consecutiveFont = (int) ($layout['consecutive_font_size'] ?? 58);

        return implode("\n", [
            '^XA',
            '^CI28',
            '^PW820',
            '^LL400',
            '^LH0,0',
            "^FO{$titleX},{$titleY}^A0N,{$titleFont},{$titleFont}^FD{$title}^FS",
            "^FO{$qrX},{$qrY}^BQ{$qrOrientation},2,{$qrMag}",
            "^FH\\^FDLA,{$qrPayload}^FS",
            "^FO{$fgX},{$fgY}^A0N,{$fgFont},{$fgFont}^FD{$fg}^FS",
            "^FO{$jobX},{$jobY}^A0N,{$jobFont},{$jobFont}^FD{$job}^FS",
            "^FO{$consecutiveX},{$consecutiveY}^A0N,{$consecutiveFont},{$consecutiveFont}^FD{$consecutive}^FS",
            '^XZ',
        ]);
    }
}
