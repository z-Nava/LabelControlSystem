<?php

namespace App\Services\Labels;

class SerialTemplateZplBuilder
{
    public function build(string $labelType, array $layout): string
    {
        $elements = $this->resolveElements($layout);
        $zpl = ['^XA', '^CI28'];

        foreach ($elements as $element) {
            $fieldData = $this->resolveFieldData((string) ($element['content'] ?? ''));

            if ($fieldData === null) {
                continue;
            }

            if (($element['type'] ?? 'text') === 'qr') {
                $zpl = [...$zpl, ...$this->buildQrElement($element, $fieldData)];
                continue;
            }

            $zpl = [...$zpl, ...$this->buildTextElement($element, $fieldData)];
        }

        $zpl[] = '^XZ';

        return implode("\n", $zpl);
    }

    private function resolveElements(array $layout): array
    {
        $elements = $layout['elements'] ?? null;

        if (is_array($elements) && $elements !== []) {
            return array_values(array_filter($elements, fn ($element) => is_array($element)));
        }

        return [[
            'type' => 'text',
            'content' => 'serial_full',
            'x' => $layout['x'] ?? 40,
            'y' => $layout['y'] ?? 40,
            'font_size' => $layout['font_size'] ?? 40,
            'orientation' => $layout['orientation'] ?? 'N',
        ]];
    }

    private function buildTextElement(array $element, string $fieldData): array
    {
        $x = (int) ($element['x'] ?? 40);
        $y = (int) ($element['y'] ?? 40);
        $fontSize = (int) ($element['font_size'] ?? 40);
        $width = (int) ($element['width'] ?? $fontSize);
        $orientation = $this->normalizeOrientation((string) ($element['orientation'] ?? 'N'));

        return [
            sprintf('^FO%d,%d', $x, $y),
            sprintf('^A%sN,%d,%d', $orientation, $fontSize, max(1, $width)),
            sprintf('^FD%s^FS', $fieldData),
        ];
    }

    private function buildQrElement(array $element, string $fieldData): array
    {
        $x = (int) ($element['x'] ?? 20);
        $y = (int) ($element['y'] ?? 20);
        $orientation = $this->normalizeOrientation((string) ($element['orientation'] ?? 'N'));
        $model = $this->normalizeQrModel((string) ($element['model'] ?? '2'));
        $magnification = max(1, min(10, (int) ($element['magnification'] ?? 4)));

        return [
            sprintf('^FO%d,%d', $x, $y),
            sprintf('^BQN,%s,%s,%d', $orientation, $model, $magnification),
            sprintf('^FDLA,%s^FS', $fieldData),
        ];
    }

    private function resolveFieldData(string $content): ?string
    {
        return match (trim($content)) {
            'serial_full' => '{{serial_full}}',
            'sku' => '{{sku}}',
            'label_part_number' => '{{label_part_number}}',
            default => null,
        };
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

    private function normalizeQrModel(string $model): string
    {
        $normalized = strtoupper(trim($model));

        return in_array($normalized, ['1', '2'], true)
            ? $normalized
            : '2';
    }
}
