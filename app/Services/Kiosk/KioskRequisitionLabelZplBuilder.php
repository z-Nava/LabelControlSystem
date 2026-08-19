<?php

namespace App\Services\Kiosk;

use App\Models\LabelRequest;
use App\Support\LabelDimensions;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class KioskRequisitionLabelZplBuilder
{
    private const BASE_DPI = 203;

    private const BASE_WIDTH_DOTS = 799;

    private const BASE_HEIGHT_DOTS = 799;

    public function build(LabelRequest $labelRequest, int $dpi = self::BASE_DPI): string
    {
        $dpi = in_array($dpi, [203, 300], true) ? $dpi : self::BASE_DPI;
        $widthDots = LabelDimensions::millimetersToDots(100, $dpi) ?? self::BASE_WIDTH_DOTS;
        $heightDots = LabelDimensions::millimetersToDots(100, $dpi) ?? self::BASE_HEIGHT_DOTS;
        $scale = min(
            $widthDots / self::BASE_WIDTH_DOTS,
            $heightDots / self::BASE_HEIGHT_DOTS,
        );

        $lineName = trim(implode(' ', array_filter([
            $labelRequest->line?->code,
            $labelRequest->line?->name,
        ]))) ?: 'SIN LINEA';
        $types = implode(' + ', $labelRequest->requestedLabelTypes()) ?: 'SIN TIPO';
        $serialPartNumbers = implode(', ', $labelRequest->requestedSerialPartNumbers()) ?: 'NO REQUERIDO';
        $ratingPartNumbers = implode(', ', $labelRequest->requestedRatingPartNumbers()) ?: 'NO REQUERIDO';
        $shippingQuantity = $labelRequest->include_shipping
            ? (string) ($labelRequest->shipping_quantity ?? $labelRequest->quantity_requested)
            : 'NO REQUERIDO';
        $requestDate = $this->formatDate($labelRequest->getRawOriginal('request_date'), 'd/m/Y');
        $createdAt = $this->formatDate(
            $labelRequest->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now()->format('d/m/Y H:i'),
        );
        $folio = sprintf('#%06d', (int) $labelRequest->id);
        $qrPayload = (string) $labelRequest->job_number;

        return implode("\n", [
            '^XA',
            '^CI28',
            "^PW{$widthDots}",
            "^LL{$heightDots}",
            '^LH0,0',
            '^LS0',
            '^MMT',
            $this->box(12, 12, 775, 775, 3, $scale),
            $this->field(28, 25, 742, 30, 'REQUISICION DE ETIQUETAS', $scale, alignment: 'C'),
            $this->field(28, 65, 742, 52, $folio, $scale, alignment: 'C'),
            $this->line(28, 124, 742, 3, $scale),
            $this->field(38, 140, 720, 25, "FECHA: {$requestDate}  |  SEMANA: {$labelRequest->week}", $scale),
            $this->field(38, 176, 720, 36, 'JOB: '.(string) $labelRequest->job_number, $scale),
            $this->field(38, 220, 720, 27, 'MODELO: '.(string) $labelRequest->model, $scale, maxLines: 2),
            $this->field(38, 276, 720, 24, "LINEA: {$lineName}", $scale, maxLines: 2),
            $this->field(38, 326, 720, 23, 'LIDER: '.(string) $labelRequest->leader_name, $scale, maxLines: 2),
            $this->field(38, 374, 720, 23, 'SOLICITA: '.(string) $labelRequest->requested_by_name, $scale, maxLines: 2),
            $this->field(38, 422, 720, 25, 'CANTIDAD: '.number_format((int) $labelRequest->quantity_requested).'  |  TIPOS: '.$types, $scale, maxLines: 2),
            $this->field(38, 475, 720, 22, 'NP SERIAL: '.$serialPartNumbers, $scale, maxLines: 2),
            $this->field(38, 525, 720, 22, 'NP RATING: '.$ratingPartNumbers, $scale, maxLines: 2),
            $this->field(38, 575, 530, 21, 'SHIPPING: '.$shippingQuantity.'  |  PO: '.($labelRequest->po_number ?: 'N/A'), $scale, maxLines: 2),
            $this->field(38, 625, 530, 21, 'DESTINO: '.($labelRequest->destination ?: 'N/A'), $scale, maxLines: 2),
            $this->field(38, 681, 515, 18, "REGISTRADA: {$createdAt}", $scale),
            $this->field(38, 710, 95, 17, 'IMPRIMIO:', $scale),
            $this->line(130, 730, 145, 2, $scale),
            $this->field(300, 710, 90, 17, 'RECIBIO:', $scale),
            $this->line(390, 730, 160, 2, $scale),
            $this->field(38, 738, 130, 17, 'FOLIO INICIAL:', $scale),
            $this->line(165, 758, 110, 2, $scale),
            $this->field(300, 738, 125, 17, 'FOLIO FINAL:', $scale),
            $this->line(425, 758, 125, 2, $scale),
            $this->field(38, 763, 70, 17, 'TURNO:', $scale),
            $this->line(105, 783, 170, 2, $scale),
            $this->qr(596, 606, $qrPayload, $scale),
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }

    private function field(
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

    private function box(int $x, int $y, int $width, int $height, int $thickness, float $scale): string
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

    private function line(int $x, int $y, int $width, int $thickness, float $scale): string
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

    private function qr(int $x, int $y, string $payload, float $scale): string
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

    private function formatDate(mixed $value, string $format, string $fallback = ''): string
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
}
