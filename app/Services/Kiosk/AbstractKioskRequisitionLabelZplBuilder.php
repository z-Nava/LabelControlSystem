<?php

namespace App\Services\Kiosk;

use App\Support\LabelDimensions;
use DateTimeInterface;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;

abstract class AbstractKioskRequisitionLabelZplBuilder
{
    protected const BASE_DPI = 203;

    // Portrait reading area on stock that feeds 102 mm wide by 165 mm long.
    protected const LAYOUT_WIDTH_DOTS = 815;

    protected const LAYOUT_HEIGHT_DOTS = 1319;

    /**
     * @return array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float}
     */
    protected function dimensions(int $dpi): array
    {
        $dpi = in_array($dpi, [203, 300], true) ? $dpi : self::BASE_DPI;
        $width = LabelDimensions::millimetersToDots((float) config('kiosk.requisition_label.width_mm', 102), $dpi) ?? self::LAYOUT_WIDTH_DOTS;
        $height = LabelDimensions::millimetersToDots((float) config('kiosk.requisition_label.height_mm', 165), $dpi) ?? self::LAYOUT_HEIGHT_DOTS;

        return [
            'width' => $width,
            'height' => $height,
            'x_scale' => $width / self::LAYOUT_WIDTH_DOTS,
            'y_scale' => $height / self::LAYOUT_HEIGHT_DOTS,
            'font_scale' => min($width / self::LAYOUT_WIDTH_DOTS, $height / self::LAYOUT_HEIGHT_DOTS),
        ];
    }

    /**
     * @param  array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float}  $dimensions
     */
    protected function field(
        int $x,
        int $y,
        int $width,
        int $fontSize,
        string $value,
        array $dimensions,
        string $alignment = 'L',
    ): string {
        $fontSize = $this->scaled($fontSize, $dimensions['font_scale']);
        $physicalX = $this->scaled($x, $dimensions['x_scale']);
        $physicalY = $this->scaled($y, $dimensions['y_scale']);
        $fieldWidth = $this->scaled($width, $dimensions['x_scale']);

        return "^FO{$physicalX},{$physicalY}^A0N,{$fontSize},{$fontSize}^FB{$fieldWidth},1,0,{$alignment},0^FH^FD{$this->escape($value)}^FS";
    }

    /**
     * @param  array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float}  $dimensions
     */
    protected function box(int $x, int $y, int $width, int $height, int $thickness, array $dimensions): string
    {
        return sprintf(
            '^FO%d,%d^GB%d,%d,%d^FS',
            $this->scaled($x, $dimensions['x_scale']),
            $this->scaled($y, $dimensions['y_scale']),
            $this->scaled($width, $dimensions['x_scale']),
            $this->scaled($height, $dimensions['y_scale']),
            $this->scaled($thickness, $dimensions['font_scale']),
        );
    }

    /**
     * @param  array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float}  $dimensions
     */
    protected function line(int $x, int $y, int $width, int $thickness, array $dimensions): string
    {
        return $this->box($x, $y, $width, $thickness, $thickness, $dimensions);
    }

    /**
     * @param  array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float}  $dimensions
     */
    protected function qr(
        int $x,
        int $y,
        string $payload,
        array $dimensions,
        int $baseMagnification = 4,
    ): string {
        $magnification = max(2, min(10, (int) round($baseMagnification * $dimensions['font_scale'])));

        return sprintf(
            '^FO%d,%d^BQN,2,%d^FH^FDLA,%s^FS',
            $this->scaled($x, $dimensions['x_scale']),
            $this->scaled($y, $dimensions['y_scale']),
            $magnification,
            $this->escape($payload),
        );
    }

    /**
     * @param  array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float}  $dimensions
     * @return array<int, string>
     */
    protected function startLabel(array $dimensions): array
    {
        return [
            '^XA',
            '^CI28',
            "^PW{$dimensions['width']}",
            "^LL{$dimensions['height']}",
            '^LH0,0',
            '^LS0',
            '^MMT',
            $this->box(12, 12, 791, 1295, 3, $dimensions),
        ];
    }

    /**
     * @param  array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float}  $dimensions
     * @return array<int, string>
     */
    protected function footer(array $dimensions): array
    {
        return [
            $this->line(25, 1150, 765, 2, $dimensions),
            $this->field(30, 1164, 105, 17, 'IMPRIMIO:', $dimensions),
            $this->line(135, 1189, 235, 2, $dimensions),
            $this->field(410, 1164, 95, 17, 'RECIBIO:', $dimensions),
            $this->line(505, 1189, 270, 2, $dimensions),
            $this->field(30, 1210, 145, 17, 'FOLIO INICIAL:', $dimensions),
            $this->line(175, 1235, 190, 2, $dimensions),
            $this->field(410, 1210, 140, 17, 'FOLIO FINAL:', $dimensions),
            $this->line(550, 1235, 225, 2, $dimensions),
            $this->field(30, 1260, 80, 17, 'TURNO:', $dimensions),
            $this->line(110, 1285, 260, 2, $dimensions),
        ];
    }

    protected function formatDate(
        mixed $value,
        string $format,
        string $fallback = '',
        ?string $timezone = null,
    ): string {
        if ($value instanceof DateTimeInterface) {
            $date = Carbon::instance($value)->copy();

            return ($timezone ? $date->setTimezone($timezone) : $date)->format($format);
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                $date = Carbon::parse($value);

                return ($timezone ? $date->setTimezone($timezone) : $date)->format($format);
            } catch (\Throwable) {
                return trim($value);
            }
        }

        return $fallback;
    }

    protected function displayTimezone(): string
    {
        $container = Container::getInstance();

        if ($container->bound('config')) {
            return (string) $container->make('config')->get(
                'app.display_timezone',
                'America/Mexico_City',
            );
        }

        return 'America/Mexico_City';
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
