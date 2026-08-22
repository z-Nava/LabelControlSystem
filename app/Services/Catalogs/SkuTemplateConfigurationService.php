<?php

namespace App\Services\Catalogs;

use App\Models\LabelPrintProfile;
use App\Models\LabelSku;
use App\Models\LabelTemplate;
use App\Services\Labels\SerialTemplateZplBuilder;
use Illuminate\Support\Facades\DB;

class SkuTemplateConfigurationService
{
    public function __construct(
        private readonly LabelTemplateService $templateService,
        private readonly LabelPrintProfileService $profileService,
        private readonly SerialTemplateZplBuilder $zplBuilder,
    ) {}

    public function create(array $data, ?int $userId = null): LabelPrintProfile
    {
        return DB::transaction(function () use ($data, $userId): LabelPrintProfile {
            $this->lockSkus([(int) $data['label_sku_id']]);
            $template = $this->templateService->create($this->templatePayload($data), $userId);

            return $this->profileService->create(
                $this->profilePayload($data, $template->id),
                $userId,
            );
        }, 3);
    }

    public function update(
        LabelPrintProfile $configuration,
        array $data,
        ?int $userId = null,
    ): LabelPrintProfile {
        return DB::transaction(function () use ($configuration, $data, $userId): LabelPrintProfile {
            $this->lockSkus([
                (int) $configuration->label_sku_id,
                (int) $data['label_sku_id'],
            ]);
            $configuration = $this->lockConfiguration($configuration);
            $template = $this->lockTemplate($configuration);

            if ($template === null || $this->templateRequiresCopy($configuration, $template, $data)) {
                $template = $this->templateService->create($this->templatePayload($data), $userId);
            } else {
                $this->templateService->update($template, $this->templatePayload($data), $userId);
            }

            return $this->profileService->update(
                $configuration,
                $this->profilePayload($data, $template->id),
                $userId,
            );
        }, 3);
    }

    public function toggleActive(
        LabelPrintProfile $configuration,
        ?int $userId = null,
    ): LabelPrintProfile {
        return DB::transaction(function () use ($configuration, $userId): LabelPrintProfile {
            $configuration = LabelPrintProfile::query()->findOrFail($configuration->getKey());
            $this->lockSkus([(int) $configuration->label_sku_id]);
            $configuration = $this->lockConfiguration($configuration);

            return $this->profileService->toggleActive($configuration, $userId);
        }, 3);
    }

    private function lockConfiguration(LabelPrintProfile $configuration): LabelPrintProfile
    {
        return LabelPrintProfile::query()
            ->lockForUpdate()
            ->findOrFail($configuration->getKey());
    }

    private function lockSkus(array $skuIds): void
    {
        LabelSku::query()
            ->whereKey(array_values(array_unique($skuIds)))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    private function lockTemplate(LabelPrintProfile $configuration): ?LabelTemplate
    {
        if ($configuration->label_template_id === null) {
            return null;
        }

        return LabelTemplate::query()
            ->lockForUpdate()
            ->find($configuration->label_template_id);
    }

    private function templateRequiresCopy(
        LabelPrintProfile $configuration,
        LabelTemplate $template,
        array $data,
    ): bool {
        $scopeChanges = $template->label_sku_id !== (int) $data['label_sku_id']
            || $template->label_type !== $data['label_type']
            || $template->serial_standard !== $data['serial_standard'];

        if ($scopeChanges) {
            return true;
        }

        return LabelPrintProfile::query()
            ->where('id', '!=', $configuration->getKey())
            ->where('label_template_id', $template->getKey())
            ->exists();
    }

    private function templatePayload(array $data): array
    {
        $layout = $this->templateLayout($data);

        return [
            'name' => $data['template_name'],
            'label_type' => $data['label_type'],
            'serial_standard' => $data['serial_standard'],
            'label_sku_id' => $data['label_sku_id'],
            'dpi' => $data['template_dpi'],
            'width_mm' => $data['template_width_mm'] ?? null,
            'height_mm' => $data['template_height_mm'] ?? null,
            'zpl' => $this->zplBuilder->build(
                $data['label_type'],
                $layout,
                $data['serial_standard'],
                [
                    'dpi' => $data['template_dpi'],
                    'width_mm' => $data['template_width_mm'] ?? null,
                    'height_mm' => $data['template_height_mm'] ?? null,
                ],
            ),
            'serial_layout' => $layout,
            'meta' => [
                'serial_layout' => $layout,
            ],
            'is_active' => $data['template_is_active'],
        ];
    }

    private function templateLayout(array $data): array
    {
        return [
            'text' => [
                'x' => $data['serial_position_x'],
                'y' => $data['serial_position_y'],
                'font_size' => $data['serial_font_size'],
                'orientation' => $data['serial_orientation'],
            ],
            'qr' => [
                'x' => $data['qr_position_x'] ?? 30,
                'y' => $data['qr_position_y'] ?? 30,
                'orientation' => $data['qr_orientation'] ?? 'N',
                'magnification' => $data['qr_magnification'] ?? 4,
                'content_mode' => $data['qr_content_mode'] ?? 'auto',
                'separator' => $data['qr_separator'] ?? 'pipe',
                'serial_style' => $data['qr_serial_style'] ?? 'as_is',
                'custom_fields' => array_values(array_filter([
                    $data['qr_custom_field_1'] ?? null,
                    $data['qr_custom_field_2'] ?? null,
                    $data['qr_custom_field_3'] ?? null,
                ])),
            ],
            'rating_qr' => (bool) ($data['rating_with_qr'] ?? false),
            'rating_hide_sku' => (bool) ($data['rating_hide_sku'] ?? false),
            'sku' => [
                'x' => $data['sku_position_x'] ?? 170,
                'y' => $data['sku_position_y'] ?? 35,
                'font_size' => $data['sku_font_size'] ?? 44,
                'orientation' => $data['sku_orientation'] ?? 'N',
            ],
            'sn' => [
                'x' => $data['sn_position_x'] ?? 170,
                'y' => $data['sn_position_y'] ?? 95,
                'font_size' => $data['sn_font_size'] ?? 22,
                'orientation' => $data['sn_orientation'] ?? 'N',
                'prefix' => $data['sn_prefix'] ?? 'SN:',
            ],
            'serial_block' => [
                'count' => max(1, (int) ($data['serial_block_count'] ?? 1)),
                'offset_y' => (int) ($data['serial_block_offset_y'] ?? 180),
            ],
        ];
    }

    private function profilePayload(array $data, int $templateId): array
    {
        return [
            'label_sku_id' => $data['label_sku_id'],
            'label_type' => $data['label_type'],
            'serial_standard' => $data['serial_standard'],
            'label_template_id' => $templateId,
            'name' => $data['profile_name'],
            'default_printer_name' => $data['default_printer_name'] ?? null,
            'default_printer_ip' => $data['connection_type'] === 'network'
                ? ($data['default_printer_ip'] ?? null)
                : null,
            'dpi' => $data['profile_dpi'],
            'darkness' => $data['darkness'] ?? null,
            'speed' => $data['speed'] ?? null,
            'media_type' => $data['media_type'] ?? null,
            'media_tracking' => $data['media_tracking'] ?? null,
            'print_mode' => $data['print_mode'] ?? null,
            'offset_x' => $data['offset_x'] ?? 0,
            'offset_y' => $data['offset_y'] ?? 0,
            'settings' => [
                'connection_type' => $data['connection_type'],
                'usb_required' => $data['connection_type'] === 'usb',
            ],
            'is_active' => $data['profile_is_active'],
        ];
    }
}
