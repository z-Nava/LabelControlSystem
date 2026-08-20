<?php

namespace App\Services\Kiosk;

use App\Support\LabelDimensions;
use DateTimeInterface;
use Illuminate\Support\Carbon;

abstract class AbstractKioskRequisitionLabelZplBuilder
{
    protected const BASE_DPI = 203;

    protected const BASE_WIDTH_DOTS = 799;

    protected const BASE_HEIGHT_DOTS = 799;

    /**
     * @return array{dpi: int, width: int, height: int, scale: float}
     */
    protected function dimensions(int $dpi): array
    {
        $dpi = in_array($dpi, [203, 300], true) ? $dpi : self::BASE_DPI;
        $width = LabelDimensions::millimetersToDots(100, $dpi) ?? self::BASE_WIDTH_DOTS;
        $height = LabelDimensions::millimetersToDots(100, $dpi) ?? self::BASE_HEIGHT_DOTS;

        return [
            'dpi' => $dpi,
            'width' => $width,
            'height' => $height,
            'scale' => min(
                $width / self::BASE_WIDTH_DOTS,
                $height / self::BASE_HEIGHT_DOTS,
            ),
        ];
    }

    protected function field(
        int $x,
        int $y,
        int $width,
        int $fontSize,
        string $value,
        float $scale,
        int $maxLines = 1,
        string $alignment = 'L',
    ): string {
        $x = $this->scaled($x, $scale);
        $y = $this->scaled($y, $scale);
        $width = $this->scaled($width, $scale);
        $fontSize = $this->scaled($fontSize, $scale);

        return "^FO{$x},{$y}^A0N,{$fontSize},{$fontSize}^FB{$width},{$maxLines},2,{$alignment},0^FH^FD{$this->escape($value)}^FS";
    }

    protected function box(int $x, int $y, int $width, int $height, int $thickness, float $scale): string
    {
        return sprintf(
            '^FO%d,%d^GB%d,%d,%d^FS',
            $this->scaled($x, $scale),
            $this->scaled($y, $scale),
            $this->scaled($width, $scale),
            $this->scaled($height, $scale),
            $this->scaled($thickness, $scale),
        );
    }

    protected function line(int $x, int $y, int $width, int $thickness, float $scale): string
    {
        return sprintf(
            '^FO%d,%d^GB%d,%d,%d^FS',
            $this->scaled($x, $scale),
            $this->scaled($y, $scale),
            $this->scaled($width, $scale),
            $this->scaled($thickness, $scale),
            $this->scaled($thickness, $scale),
        );
    }

    protected function qr(int $x, int $y, string $payload, float $scale): string
    {
        $magnification = max(2, min(10, (int) round(4 * $scale)));

        return sprintf(
            '^FO%d,%d^BQN,2,%d^FH^FDLA,%s^FS',
            $this->scaled($x, $scale),
            $this->scaled($y, $scale),
            $magnification,
            $this->escape($payload),
        );
    }

    protected function formatDate(mixed $value, string $format, string $fallback = ''): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format($format);
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->format($format);
            } catch (\Throwable) {
                return trim($value);
            }
        }

        return $fallback;
    }

    private function scaled(int $value, float $scale): int
    {
        return max(1, (int) round($value * $scale));
    }

    private function escape(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return str_replace(
            ['_', '\\', '^', '~'],
            ['_5F', '_5C', '_5E', '_7E'],
            $normalized,
        );
    }
}
