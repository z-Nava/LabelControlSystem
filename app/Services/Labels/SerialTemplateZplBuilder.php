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
        $hideSkuOnEmeaRating = $labelType === 'rating'
            && (
                in_array(strtoupper($serialStandard), ['EMEA', 'ANZ'], true)
                || (bool) ($layout['rating_hide_sku'] ?? false)
            );
        $prefix = trim((string) ($layout['sn']['prefix'] ?? 'SN:'));
        $snText = $isRatingLabel || $prefix === ''
            ? '{{serial_full}}'
            : $prefix.' {{serial_full}}';

        $zpl = [
            '^XA',
            '^CI28',
            sprintf('^FO%d,%d', $qr['x'], $qr['y']),
            sprintf('^BQ%s,2,%d', $qr['orientation'], $qr['magnification']),
            sprintf('^FDLA,%s^FS', $this->resolveQrPayload($qr, $isRatingLabel)),
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
            'content_mode' => (string) ($layout['content_mode'] ?? 'auto'),
            'separator' => (string) ($layout['separator'] ?? 'pipe'),
            'serial_style' => (string) ($layout['serial_style'] ?? 'as_is'),
            'custom_fields' => array_values(array_filter((array) ($layout['custom_fields'] ?? []))),
        ];
    }

    private function resolveQrPayload(array $qr, bool $isRatingLabel): string
    {
        $mode = strtolower(trim((string) ($qr['content_mode'] ?? 'auto')));

        if ($mode === 'custom') {
            $tokens = collect($qr['custom_fields'] ?? [])
                ->map(fn ($token) => $this->mapQrToken((string) $token, (string) ($qr['serial_style'] ?? 'as_is')))
                ->filter(fn ($token) => $token !== '')
                ->values();

            if ($tokens->isNotEmpty()) {
                return $tokens->implode($this->resolveQrSeparator((string) ($qr['separator'] ?? 'pipe')));
            }
        }

        if ($mode === 'serial_full') {
            return $this->serialPlaceholder('serial_full', (string) ($qr['serial_style'] ?? 'as_is'));
        }

        if ($mode === 'rating_qr') {
            return $this->serialPlaceholder('rating_qr_code', (string) ($qr['serial_style'] ?? 'as_is'));
        }

        return $isRatingLabel
            ? $this->serialPlaceholder('rating_qr_code', (string) ($qr['serial_style'] ?? 'as_is'))
            : $this->serialPlaceholder('serial_full', (string) ($qr['serial_style'] ?? 'as_is'));
    }

    private function resolveQrSeparator(string $separator): string
    {
        return match (strtolower(trim($separator))) {
            'space' => ' ',
            'none' => '',
            default => ' | ',
        };
    }

    private function mapQrToken(string $token, string $serialStyle = 'as_is'): string
    {
        return match (strtolower(trim($token))) {
            'fixed_103' => '103',
            'serial_full' => $this->serialPlaceholder('serial_full', $serialStyle),
            'rating_qr_code' => $this->serialPlaceholder('rating_qr_code', $serialStyle),
            'sku' => '{{sku}}',
            'label_part_number' => '{{label_part_number}}',
            'console_sku' => '{{console_sku}}',
            'assembly_part_number' => '{{assembly_part_number}}',
            'packaging_part_number' => '{{packaging_part_number}}',
            'emea_sku' => '{{emea_sku}}',
            'anz_sku' => '{{anz_sku}}',
            default => '',
        };
    }

    private function serialPlaceholder(string $baseField, string $style): string
    {
        $normalizedStyle = strtolower(trim($style));

        return match ($normalizedStyle) {
            'segmented' => '{{'.$baseField.'_spaced}}',
            'compact' => '{{'.$baseField.'_compact}}',
            default => '{{'.$baseField.'}}',
        };
    }

    private function normalizeOrientation(string $orientation): string
    {
        $normalized = strtoupper(trim($orientation));

        return in_array($normalized, ['N', 'R', 'I', 'B'], true)
            ? $normalized
            : 'N';
    }
}
