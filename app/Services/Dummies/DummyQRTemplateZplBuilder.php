<?php

namespace App\Services\Dummies;

class DummyQRTemplateZplBuilder
{
    private const DEFAULT_DPI = 203;

    private const DEFAULT_WIDTH_DOTS = 820;

    private const DEFAULT_HEIGHT_DOTS = 400;

    private const LEGACY_LAYOUT = [
        'title_x' => 20,
        'title_y' => 20,
        'title_font_size' => 44,
        'qr_x' => 30,
        'qr_y' => 65,
        'qr_magnification' => 4,
        'fg_x' => 360,
        'fg_y' => 70,
        'fg_font_size' => 40,
        'job_x' => 360,
        'job_y' => 130,
        'job_font_size' => 34,
        'consecutive_x' => 380,
        'consecutive_y' => 250,
        'consecutive_font_size' => 58,
    ];

    public function build(array $layout): string
    {
        [$widthDots, $heightDots] = $this->resolveDimensions($layout);
        $layout = $this->scaleLegacyLayout($layout, $widthDots, $heightDots);
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
            "^PW{$widthDots}",
            "^LL{$heightDots}",
            '^LH0,0',
            '^LS0',
            "^FO{$titleX},{$titleY}^A0N,{$titleFont},{$titleFont}^FD{$title}^FS",
            "^FO{$qrX},{$qrY}^BQ{$qrOrientation},2,{$qrMag}",
            "^FH\\^FDLA,{$qrPayload}^FS",
            "^FO{$fgX},{$fgY}^A0N,{$fgFont},{$fgFont}^FD{$fg}^FS",
            "^FO{$jobX},{$jobY}^A0N,{$jobFont},{$jobFont}^FD{$job}^FS",
            "^FO{$consecutiveX},{$consecutiveY}^A0N,{$consecutiveFont},{$consecutiveFont}^FD{$consecutive}^FS",
            '^XZ',
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function resolveDimensions(array $layout): array
    {
        $dpi = max(1, (int) ($layout['dpi'] ?? self::DEFAULT_DPI));

        return [
            $this->millimetersToDots($layout['width_mm'] ?? null, $dpi, self::DEFAULT_WIDTH_DOTS),
            $this->millimetersToDots($layout['height_mm'] ?? null, $dpi, self::DEFAULT_HEIGHT_DOTS),
        ];
    }

    private function millimetersToDots(mixed $millimeters, int $dpi, int $fallback): int
    {
        if (! is_numeric($millimeters) || (float) $millimeters <= 0) {
            return $fallback;
        }

        return max(1, (int) round(((float) $millimeters / 25.4) * $dpi));
    }

    private function scaleLegacyLayout(array $layout, int $widthDots, int $heightDots): array
    {
        if ($widthDots === self::DEFAULT_WIDTH_DOTS && $heightDots === self::DEFAULT_HEIGHT_DOTS) {
            return $layout;
        }

        foreach (self::LEGACY_LAYOUT as $field => $default) {
            if ((int) ($layout[$field] ?? $default) !== $default) {
                return $layout;
            }
        }

        $scaleX = $widthDots / self::DEFAULT_WIDTH_DOTS;
        $scaleY = $heightDots / self::DEFAULT_HEIGHT_DOTS;
        $uniformScale = min($scaleX, $scaleY);
        $scaled = $layout;

        foreach (['title_x', 'qr_x', 'fg_x', 'job_x', 'consecutive_x'] as $field) {
            $scaled[$field] = max(0, (int) round(self::LEGACY_LAYOUT[$field] * $scaleX));
        }

        foreach (['title_y', 'qr_y', 'fg_y', 'job_y', 'consecutive_y'] as $field) {
            $scaled[$field] = max(0, (int) round(self::LEGACY_LAYOUT[$field] * $scaleY));
        }

        foreach (['title_font_size', 'fg_font_size', 'job_font_size', 'consecutive_font_size'] as $field) {
            $scaled[$field] = max(10, (int) round(self::LEGACY_LAYOUT[$field] * $uniformScale));
        }

        $scaled['qr_magnification'] = max(1, min(10, (int) round(self::LEGACY_LAYOUT['qr_magnification'] * $uniformScale)));

        return $scaled;
    }
}
