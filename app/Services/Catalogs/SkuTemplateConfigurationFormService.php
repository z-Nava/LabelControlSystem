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

    public function build(LabelPrintProfile $configuration): array
    {
        $labelSkus = $this->availableSkus();
        $formState = $this->buildFormState($configuration);
        $skuGroups = $this->groupSkusByStandard($labelSkus);
        $skuPreviewSerials = $this->buildSkuPreviewSerials($labelSkus);

        $selectedStandard = $formState['selected_serial_standard'] ?? 'UL';

        if (($skuGroups[$selectedStandard] ?? collect())->isEmpty()) {
            $selectedStandard = collect(self::SUPPORTED_STANDARDS)
                ->first(fn (string $standard) => ($skuGroups[$standard] ?? collect())->isNotEmpty()) ?? 'UL';
        }

        $formState['selected_serial_standard'] = $selectedStandard;

        return [
            'labelSkus' => $labelSkus,
            'skuGroups' => $skuGroups,
            'skuPreviewSerials' => $skuPreviewSerials,
            'availableStandards' => self::SUPPORTED_STANDARDS,
            'formState' => $formState,
        ];
    }

    private function buildSkuPreviewSerials(Collection $labelSkus): array
    {
        if ($labelSkus->isEmpty()) {
            return [];
        }

        $formats = SkuSerialFormat::query()
            ->with(['ulConfig', 'emeaConfig', 'anzConfig'])
            ->active()
            ->whereIn('sku', $labelSkus->pluck('sku')->all())
            ->whereIn('serial_standard', self::SUPPORTED_STANDARDS)
            ->get()
            ->keyBy(fn (SkuSerialFormat $format) => strtoupper(trim((string) $format->serial_standard)).'|'.strtoupper(trim((string) $format->sku)));

        return $labelSkus
            ->mapWithKeys(function (LabelSku $sku) use ($formats): array {
                $standard = strtoupper(trim((string) ($sku->serial_standard ?? SerialStandards::UL)));
                $key = $standard.'|'.strtoupper(trim((string) $sku->sku));
                $format = $formats->get($key);

                return [$sku->id => $this->resolvePreviewSerial($standard, $format)];
            })
            ->all();
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
            $plant = strtoupper(trim((string) ($format?->emea_plant_code ?: '01')));

            return "{$prefix}{$conformity}{$plant}{$unit}{$monthLetter}{$yearFour}";
        }

        $prefix = strtoupper(trim((string) ($format?->ul_prefix ?: 'L36')));
        $break = strtoupper(trim((string) ($format?->ul_serial_break ?: 'B')));
        $plant = (bool) ($format?->ul_use_plant_code ?? true)
            ? strtoupper(trim((string) ($format?->ul_plant_code ?: 'H')))
            : '';

        return "{$prefix}{$break}{$plant}{$yearTwo}01{$unit}";
    }

    private function availableSkus(): Collection
    {
        return LabelSku::query()
            ->active()
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from((new SkuSerialFormat())->getTable())
                    ->whereColumn('sku_serial_formats.sku', 'label_skus.sku')
                    ->whereColumn('sku_serial_formats.serial_standard', 'label_skus.serial_standard')
                    ->where('sku_serial_formats.is_active', true);
            })
            ->orderBy('serial_standard')
            ->orderBy('sku')
            ->get();
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
}
