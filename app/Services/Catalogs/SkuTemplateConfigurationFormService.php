<?php

namespace App\Services\Catalogs;

use App\Models\LabelPrintProfile;
use App\Models\LabelSku;
use App\Models\SkuSerialFormat;
use App\Support\SerialStandards;
use Illuminate\Support\Collection;

class SkuTemplateConfigurationFormService
{
    private const SUPPORTED_STANDARDS = ['UL', 'EMEA', 'ANZ'];
    private const STANDARD_BY_SCHEME = [
        'ul_standard' => SerialStandards::UL,
        'emea_rating' => SerialStandards::EMEA,
        'anz_standard' => SerialStandards::ANZ,
    ];

    public function build(LabelPrintProfile $configuration): array
    {
        $labelSkus = $this->availableSkus($configuration);
        $formState = $this->buildFormState($configuration);
        $skuGroups = $this->groupSkusByStandard($labelSkus);
        $skuQrContext = $this->buildSkuQrContext($labelSkus);

        $selectedStandard = $formState['selected_serial_standard'] ?? 'UL';

        if (($skuGroups[$selectedStandard] ?? collect())->isEmpty()) {
            $selectedStandard = collect(self::SUPPORTED_STANDARDS)
                ->first(fn (string $standard) => ($skuGroups[$standard] ?? collect())->isNotEmpty()) ?? 'UL';
        }

        $formState['selected_serial_standard'] = $selectedStandard;

        return [
            'labelSkus' => $labelSkus,
            'skuGroups' => $skuGroups,
            'skuPreviewSerials' => $skuQrContext['preview_serials'],
            'skuAnzCustomerToolCodes' => $skuQrContext['anz_customer_tool_codes'],
            'skuAnzQrSeparators' => $skuQrContext['anz_qr_separators'],
            'availableStandards' => self::SUPPORTED_STANDARDS,
            'formState' => $formState,
        ];
    }

    private function buildSkuQrContext(Collection $labelSkus): array
    {
        if ($labelSkus->isEmpty()) {
            return [
                'preview_serials' => [],
                'anz_customer_tool_codes' => [],
                'anz_qr_separators' => [],
            ];
        }

        $formats = SkuSerialFormat::query()
            ->with(['ulConfig', 'emeaConfig', 'anzConfig'])
            ->active()
            ->where(function ($query) {
                $query->whereIn('serial_standard', self::SUPPORTED_STANDARDS)
                    ->orWhereIn('market', self::SUPPORTED_STANDARDS);
            })
            ->get()
            ->groupBy(fn (SkuSerialFormat $format) => $this->resolveFormatStandard($format));

        $previewSerials = [];
        $anzCustomerCodes = [];
        $anzQrSeparators = [];

        foreach ($labelSkus as $sku) {
            /** @var LabelSku $sku */
            $standard = SerialStandards::normalize((string) ($sku->serial_standard ?? SerialStandards::UL));
            $format = $this->resolveFormatForLabelSku(
                $formats->get($standard, collect()),
                $sku
            );

            $previewSerials[$sku->id] = $this->resolvePreviewSerial($standard, $format);
            $anzCustomerCodes[$sku->id] = strtoupper(trim((string) ($format?->anz_customer_tool_code ?? '')));
            $anzQrSeparators[$sku->id] = (string) ($format?->anz_qr_separator ?? ' | ');
        }

        return [
            'preview_serials' => $previewSerials,
            'anz_customer_tool_codes' => $anzCustomerCodes,
            'anz_qr_separators' => $anzQrSeparators,
        ];
    }

    private function resolvePreviewSerial(string $standard, ?SkuSerialFormat $format): string
    {
        $monthLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M'];
        $monthLetter = $monthLetters[(int) now()->format('n') - 1] ?? 'A';
        $yearTwo = now()->format('y');
        $yearFour = now()->format('Y');
        $unit = str_pad('1', max(1, (int) ($format?->effectiveUnitDigits() ?? ($standard === SerialStandards::ANZ ? 5 : 6))), '0', STR_PAD_LEFT);

        if ($standard === SerialStandards::ANZ) {
            $prefix = strtoupper(trim((string) ($format?->anz_product_prefix ?: 'AF02F2019')));
            $tool = strtoupper(trim((string) ($format?->anz_tool_version ?: 'A')));
            $serial = implode(' ', [$prefix, $tool, $unit, "{$monthLetter}{$yearFour}"]);
            $printFormat = strtolower(trim((string) ($format?->anz_serial_print_format ?: 'spaces')));

            if ($printFormat === 'no_spaces') {
                return str_replace(' ', '', $serial);
            }

            return $serial;
        }

        if ($standard === SerialStandards::EMEA) {
            $prefix = strtoupper(trim((string) ($format?->emea_prefix ?: '5055')));
            $conformity = strtoupper(trim((string) ($format?->emea_conformity_code ?: '54')));
            $plant = strtoupper(trim((string) ($format?->emea_plant_code ?: '')));
            $serial = collect([$prefix, $conformity, $plant, $unit, "{$monthLetter}{$yearFour}"])
                ->filter(fn (string $component) => $component !== '')
                ->implode(' ');
            $printFormat = strtolower(trim((string) ($format?->emea_serial_print_format ?: 'spaces')));

            if ($printFormat === 'no_spaces') {
                return str_replace(' ', '', $serial);
            }

            return $serial;
        }

        $prefix = strtoupper(trim((string) ($format?->ul_prefix ?: 'L36')));
        $break = strtoupper(trim((string) ($format?->ul_serial_break ?: 'B')));
        $plant = (bool) ($format?->ul_use_plant_code ?? true)
            ? strtoupper(trim((string) ($format?->ul_plant_code ?: 'H')))
            : '';

        return "{$prefix}{$break}{$plant}{$yearTwo}01{$unit}";
    }

    private function availableSkus(LabelPrintProfile $configuration): Collection
    {
        $formatsByStandard = SkuSerialFormat::query()
            ->active()
            ->whereNotNull('sku')
            ->get(['sku', 'market', 'serial_standard', 'serial_scheme'])
            ->groupBy(fn (SkuSerialFormat $format) => $this->resolveFormatStandard($format));

        $availableSkus = LabelSku::query()
            ->active()
            ->get()
            ->filter(function (LabelSku $sku) use ($formatsByStandard): bool {
                $skuStandard = SerialStandards::normalize((string) ($sku->serial_standard ?? SerialStandards::UL));
                $formats = $formatsByStandard->get($skuStandard, collect());

                return $this->resolveFormatForLabelSku($formats, $sku) !== null;
            })
            ->sortBy([
                fn (LabelSku $sku) => strtoupper((string) ($sku->serial_standard ?? SerialStandards::UL)),
                fn (LabelSku $sku) => strtoupper((string) $sku->sku),
            ])
            ->values();

        $selectedSku = $configuration->label_sku_id
            ? LabelSku::query()->find($configuration->label_sku_id)
            : null;

        if ($selectedSku && !$availableSkus->contains(fn (LabelSku $sku) => $sku->id === $selectedSku->id)) {
            $availableSkus->push($selectedSku);
        }

        return $availableSkus
            ->sortBy([
                fn (LabelSku $sku) => strtoupper((string) ($sku->serial_standard ?? SerialStandards::UL)),
                fn (LabelSku $sku) => strtoupper((string) $sku->sku),
            ])
            ->values();
    }

    private function groupSkusByStandard(Collection $labelSkus): array
    {
        $grouped = $labelSkus->groupBy(fn (LabelSku $sku) => strtoupper((string) ($sku->serial_standard ?? 'UL')));

        return collect(self::SUPPORTED_STANDARDS)
            ->mapWithKeys(fn (string $standard) => [$standard => $grouped->get($standard, collect())])
            ->all();
    }

    private function buildFormState(LabelPrintProfile $configuration): array
    {
        $resolvedLayout = $configuration->template?->resolved_serial_layout
            ?? data_get($configuration->template?->meta, 'serial_layout', []);
        $layout = old('serial_layout', $resolvedLayout);

        return [
            'text_layout' => $layout['text'] ?? $layout,
            'qr_layout' => $layout['qr'] ?? [],
            'sku_layout' => $layout['sku'] ?? [],
            'sn_layout' => $layout['sn'] ?? [],
            'serial_block_layout' => $layout['serial_block'] ?? [],
            'connection_type' => old(
                'connection_type',
                data_get(
                    old('profile_settings', $configuration->settings ?? []),
                    'connection_type',
                    $configuration->default_printer_ip ? 'network' : 'usb'
                )
            ),
            'selected_label_type' => old(
                'label_type',
                $configuration->label_type ?? $configuration->template?->label_type ?? 'serial'
            ),
            'selected_serial_standard' => old(
                'serial_standard',
                $configuration->serial_standard ?? $configuration->template?->serial_standard ?? 'UL'
            ),
            'rating_qr' => (bool) old('rating_with_qr', data_get($layout, 'rating_qr', false)),
            'rating_hide_sku' => (bool) old('rating_hide_sku', data_get($layout, 'rating_hide_sku', false)),
        ];
    }

    private function resolveFormatStandard(SkuSerialFormat $format): string
    {
        $scheme = strtolower(trim((string) $format->serial_scheme));
        if (isset(self::STANDARD_BY_SCHEME[$scheme])) {
            return self::STANDARD_BY_SCHEME[$scheme];
        }

        $market = strtoupper(trim((string) $format->market));
        if (in_array($market, self::SUPPORTED_STANDARDS, true)) {
            return $market;
        }

        return SerialStandards::normalize((string) ($format->serial_standard ?? SerialStandards::UL));
    }

    private function resolveFormatForLabelSku(Collection $formats, LabelSku $sku): ?SkuSerialFormat
    {
        if ($formats->isEmpty()) {
            return null;
        }

        $skuVariants = $this->buildSkuVariants([
            $sku->sku,
            $sku->anz_sku,
            $sku->emea_sku,
            $sku->console_sku,
            $sku->assembly_part_number,
            $sku->packaging_part_number,
        ]);

        return $formats->first(function (SkuSerialFormat $format) use ($skuVariants): bool {
            $formatVariants = $this->buildSkuVariants([(string) $format->sku]);

            return collect($formatVariants)->intersect($skuVariants)->isNotEmpty();
        });
    }

    private function buildSkuVariants(array $values): array
    {
        $variants = collect($values)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => strtoupper(trim((string) $value)))
            ->flatMap(function (string $value) {
                return [
                    $value,
                    preg_replace('/\s+/', '', $value),
                    preg_replace('/-(ANZ|EMEA|UL)$/', '', $value),
                    preg_replace('/-[A-Z]$/', '', $value),
                ];
            })
            ->filter(fn ($value) => filled($value))
            ->values()
            ->all();

        return array_values(array_unique($variants));
    }
}
