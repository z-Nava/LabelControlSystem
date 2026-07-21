<?php

namespace App\Support;

use App\Models\LabelPrintProfile;
use App\Models\LabelTemplate;

final class LabelDimensions
{
    /**
     * @return array{dpi: int, width_mm: ?float, height_mm: ?float, width_dots: ?int, height_dots: ?int}
     */
    public static function fromTemplate(LabelTemplate $template, ?LabelPrintProfile $profile = null): array
    {
        $dpi = max(1, (int) ($template->dpi ?: $profile?->dpi ?: 203));
        $widthMm = $template->width_mm !== null ? (float) $template->width_mm : null;
        $heightMm = $template->height_mm !== null ? (float) $template->height_mm : null;

        return [
            'dpi' => $dpi,
            'width_mm' => $widthMm,
            'height_mm' => $heightMm,
            'width_dots' => self::millimetersToDots($widthMm, $dpi),
            'height_dots' => self::millimetersToDots($heightMm, $dpi),
        ];
    }

    private static function millimetersToDots(?float $millimeters, int $dpi): ?int
    {
        if ($millimeters === null || $millimeters <= 0) {
            return null;
        }

        return max(1, (int) round(($millimeters / 25.4) * $dpi));
    }
}
