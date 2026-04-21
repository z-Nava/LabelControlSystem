<?php

namespace App\Services\Catalogs;

use App\Models\LabelPrintProfile;
use App\Models\LabelSku;
use App\Models\SkuSerialFormat;
use Illuminate\Support\Collection;

class SkuTemplateConfigurationFormService
{
    private const SUPPORTED_STANDARDS = ['UL', 'EMEA', 'ANZ'];

    public function build(LabelPrintProfile $configuration): array
    {
        $labelSkus = $this->availableSkus();
        $formState = $this->buildFormState($configuration);
        $skuGroups = $this->groupSkusByStandard($labelSkus);

        $selectedStandard = $formState['selected_serial_standard'] ?? 'UL';

        if (($skuGroups[$selectedStandard] ?? collect())->isEmpty()) {
            $selectedStandard = collect(self::SUPPORTED_STANDARDS)
                ->first(fn (string $standard) => ($skuGroups[$standard] ?? collect())->isNotEmpty()) ?? 'UL';
        }

        $formState['selected_serial_standard'] = $selectedStandard;

        return [
            'labelSkus' => $labelSkus,
            'skuGroups' => $skuGroups,
            'availableStandards' => self::SUPPORTED_STANDARDS,
            'formState' => $formState,
        ];
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
