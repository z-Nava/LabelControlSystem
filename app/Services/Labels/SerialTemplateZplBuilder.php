<?php

namespace App\Services\Labels;

class SerialTemplateZplBuilder
{
    public function build(array $layout): string
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

    private function normalizeOrientation(string $orientation): string
    {
        $normalized = strtoupper(trim($orientation));

        return in_array($normalized, ['N', 'R', 'I', 'B'], true)
            ? $normalized
            : 'N';
    }
}

