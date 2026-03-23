<?php

namespace App\Services\Labels;

class SerialTemplateZplBuilder
{
    public function build(string $labelType, array $layout): string
    {
        $x = (int) ($layout['x'] ?? 40);
        $y = (int) ($layout['y'] ?? 40);
        $fontSize = (int) ($layout['font_size'] ?? 40);
        $orientation = $this->normalizeOrientation((string) ($layout['orientation'] ?? 'N'));

        return implode("\n", [
            '^XA',
            '^CI28',
            sprintf('^FO%d,%d', $x, $y),
            sprintf('^A%sN,%d,%d', $orientation, $fontSize, $fontSize),
            '^FD{{serial_full}}^FS',
            '^XZ',
        ]);
    }

    private function buildSerialTemplate(array $layout): string
    {
        $qr = $this->normalizeQrLayout($layout['qr'] ?? []);
        $sku = $this->normalizeTextLayout($layout['sku'] ?? []);
        $sn = $this->normalizeTextLayout($layout['sn'] ?? ($layout['text'] ?? []), 22);
        $prefix = trim((string) ($layout['sn']['prefix'] ?? 'SN:'));
        $snText = trim($prefix) === '' ? '{{serial_full}}' : trim($prefix).' {{serial_full}}';

        return implode("\n", [
            '^XA',
            '^CI28',
            sprintf('^FO%d,%d', $qr['x'], $qr['y']),
            sprintf('^BQN,2,%d', $qr['magnification']),
            '^FDLA,{{serial_full}}^FS',
            sprintf('^FO%d,%d', $sku['x'], $sku['y']),
            sprintf('^A%sN,%d,%d', $sku['orientation'], $sku['font_size'], $sku['font_size']),
            '^FD{{sku}}^FS',
            sprintf('^FO%d,%d', $sn['x'], $sn['y']),
            sprintf('^A%sN,%d,%d', $sn['orientation'], $sn['font_size'], $sn['font_size']),
            sprintf('^FD%s^FS', $snText),
            '^XZ',
        ]);
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

