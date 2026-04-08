<?php

namespace App\Services\Labels;

class SerialTemplateZplBuilder
{
    public function build(string $labelType, array $layout, string $serialStandard = 'UL'): string
    {
        $buildWithQr = $labelType === 'serial'
            || ($labelType === 'rating' && (bool) ($layout['rating_qr'] ?? false));

        return $buildWithQr
            ? $this->buildSerialTemplate($layout, $labelType, $serialStandard)
            : $this->buildTextOnlyTemplate($layout);
    }

    private function buildTextOnlyTemplate(array $layout): string
    {
        $text = $this->normalizeTextLayout($layout['text'] ?? $layout);

        return implode("\n", [
            '^XA',
            '^CI28',
            sprintf('^FO%d,%d', $text['x'], $text['y']),
            sprintf('^A0%s,%d,%d', $text['orientation'], $text['font_size'], $text['font_size']),
            '^FD{{serial_full}}^FS',
            '^XZ',
        ]);
    }

    private function buildSerialTemplate(array $layout, string $labelType, string $serialStandard): string
    {
        $qr = $this->normalizeQrLayout($layout['qr'] ?? []);
        $isRatingLabel = $labelType === 'rating';
        $sn = $isRatingLabel
            ? $this->normalizeTextLayout($layout['text'] ?? ($layout['sn'] ?? []))
            : $this->normalizeTextLayout($layout['sn'] ?? ($layout['text'] ?? []), 22);
        $hideSkuOnEmeaRating = $labelType === 'rating' && strtoupper($serialStandard) === 'EMEA';
        $prefix = trim((string) ($layout['sn']['prefix'] ?? 'SN:'));
        $snText = $isRatingLabel || $prefix === ''
            ? '{{serial_full}}'
            : $prefix.' {{serial_full}}';

        $zpl = [
            '^XA',
            '^CI28',
            sprintf('^FO%d,%d', $qr['x'], $qr['y']),
            sprintf('^BQ%s,2,%d', $qr['orientation'], $qr['magnification']),
            '^FDLA,{{serial_full}}^FS',
        ];

        if (!$hideSkuOnEmeaRating) {
            $sku = $this->normalizeTextLayout($layout['sku'] ?? []);
            $zpl[] = sprintf('^FO%d,%d', $sku['x'], $sku['y']);
            $zpl[] = sprintf('^A0%s,%d,%d', $sku['orientation'], $sku['font_size'], $sku['font_size']);
            $zpl[] = '^FD{{sku}}^FS';
        }

        $zpl = array_merge($zpl, [
            sprintf('^FO%d,%d', $sn['x'], $sn['y']),
            sprintf('^A0%s,%d,%d', $sn['orientation'], $sn['font_size'], $sn['font_size']),
            sprintf('^FD%s^FS', $snText),
            '^XZ',
        ]);

        return implode("\n", $zpl);
    }

    private function normalizeTextLayout(array $layout, int $defaultFontSize = 40): array
    {
        return [
            'x' => (int) ($layout['x'] ?? 40),
            'y' => (int) ($layout['y'] ?? 40),
            'font_size' => (int) ($layout['font_size'] ?? $defaultFontSize),
            'orientation' => $this->normalizeOrientation((string) ($layout['orientation'] ?? 'N')),
        ];
    }

    private function normalizeQrLayout(array $layout): array
    {
        return [
            'x' => (int) ($layout['x'] ?? 30),
            'y' => (int) ($layout['y'] ?? 30),
            'orientation' => $this->normalizeOrientation((string) ($layout['orientation'] ?? 'N')),
            'magnification' => max(1, min(10, (int) ($layout['magnification'] ?? 4))),
        ];
    }

    private function normalizeOrientation(string $orientation): string
    {
        $normalized = strtoupper(trim($orientation));

        return in_array($normalized, ['N', 'R', 'I', 'B'], true)
            ? $normalized
            : 'N';
    }
}
