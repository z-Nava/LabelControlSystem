<?php

namespace App\Services\Labels;

use App\Models\LabelPrintBatch;
use App\Models\LabelPrintProfile;
use App\Models\LabelSku;
use App\Models\LabelTemplate;
use App\Models\SerialUnit;
use App\Models\SkuSerialFormat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelBatchPrintExecutionService
{
    public function __construct(
        private readonly SerialTemplateZplBuilder $zplBuilder,
    ) {
    }

    public function buildPreview(LabelPrintBatch $batch): array
    {
        $batch->loadMissing(['labelRequest', 'items', 'items.serialUnit']);

        $items = $batch->items->filter(fn ($item) => $item->serialUnit !== null)->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'batch' => 'El batch no tiene serial units asociados para renderizar.',
            ]);
        }

        $sku = LabelSku::query()
            ->select([
                'id',
                'sku',
                'label_part_number',
                'serial_standard',
                'console_sku',
                'assembly_part_number',
                'packaging_part_number',
                'emea_sku',
                'anz_sku',
            ])
            ->where('label_part_number', $batch->labelRequest?->label_part_number)
            ->where('serial_standard', (string) ($batch->labelRequest?->serial_standard ?? 'UL'))
            ->first();

        $skuId = $sku?->id;
        $serialFormat = $this->resolveSerialFormat($sku?->sku, (string) ($batch->labelRequest?->serial_standard ?? 'UL'));

        $documents = [];

        foreach (['serial', 'rating'] as $labelType) {
            $needsType = $items->contains(fn ($item) => (bool) $item->{'print_'.$labelType});

            if (!$needsType) {
                continue;
            }

            $standard = (string) ($batch->labelRequest?->serial_standard ?? 'UL');
            $profile = $this->resolveProfile($skuId, $labelType, $standard);
            $template = $this->resolveTemplate($profile?->label_template_id, $skuId, $labelType, $standard);

            if (!$template) {
                throw ValidationException::withMessages([
                    'template' => "No existe template activo para tipo {$labelType}.",
                ]);
            }

            $zplLabels = [];
            foreach ($items as $item) {
                if (!(bool) $item->{'print_'.$labelType}) {
                    continue;
                }

                $payload = $this->buildPayload($batch, $item->serialUnit, $labelType, $sku, $serialFormat);
                $templateZpl = $this->resolveTemplateZpl($template, $labelType, $standard);
                $rendered = $this->renderTemplate($templateZpl, $payload);

                for ($copy = 1; $copy <= (int) $item->copies; $copy++) {
                    $zplLabels[] = $rendered;
                }
            }

            $documents[] = [
                'label_type' => $labelType,
                'profile' => $profile ? [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'default_printer_name' => $profile->default_printer_name,
                    'default_printer_ip' => $profile->default_printer_ip,
                ] : null,
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                ],
                'units_count' => count($zplLabels),
                'zpl' => implode("\n", $zplLabels),
            ];
        }

        return [
            'batch_id' => $batch->id,
            'label_request_id' => $batch->label_request_id,
            'documents' => $documents,
            'zpl' => collect($documents)->pluck('zpl')->filter()->implode("\n"),
        ];
    }

    public function confirmPrinted(LabelPrintBatch $batch): array
    {
        $batch->loadMissing(['items']);

        return DB::transaction(function () use ($batch) {
            $unitIds = $batch->items
                ->pluck('serial_unit_id')
                ->filter()
                ->unique()
                ->values();

            if ($unitIds->isNotEmpty()) {
                SerialUnit::query()
                    ->whereIn('id', $unitIds)
                    ->update([
                        'status' => 'printed',
                        'printed_at' => now(),
                    ]);
            }

            $batch->update(['printed_at' => now()]);

            return [
                'message' => 'Impresión confirmada y trazabilidad actualizada.',
                'updated_serial_units' => $unitIds->count(),
                'printed_at' => $batch->fresh()->printed_at?->toDateTimeString(),
            ];
        });
    }

    private function buildPayload(LabelPrintBatch $batch, SerialUnit $serialUnit, string $labelType, ?LabelSku $sku, ?SkuSerialFormat $serialFormat): array
    {
        return [
            'serial_full' => $serialUnit->serial_full,
            'serial_full_spaced' => $this->toSegmentedSerial((string) $serialUnit->serial_full),
            'serial_full_compact' => $this->toCompactSerial((string) $serialUnit->serial_full),
            'rating_qr_code' => (string) ($serialUnit->rating_qr_code ?? ''),
            'rating_qr_code_spaced' => $this->toSegmentedSerial((string) ($serialUnit->rating_qr_code ?? '')),
            'rating_qr_code_compact' => $this->toCompactSerial((string) ($serialUnit->rating_qr_code ?? '')),
            'serial_number' => (string) $serialUnit->serial_number,
            'label_type' => $labelType,
            'label_request_id' => (string) $batch->label_request_id,
            'batch_id' => (string) $batch->id,
            'label_part_number' => (string) ($batch->labelRequest?->label_part_number ?? ''),
            'sku' => (string) ($sku?->sku ?? ''),
            'console_sku' => (string) ($sku?->console_sku ?? ''),
            'assembly_part_number' => (string) ($sku?->assembly_part_number ?? ''),
            'packaging_part_number' => (string) ($sku?->packaging_part_number ?? ''),
            'emea_sku' => (string) ($sku?->emea_sku ?? ''),
            'anz_sku' => (string) ($sku?->anz_sku ?? ''),
            'anz_customer_tool_code' => strtoupper(trim((string) ($serialFormat?->anz_customer_tool_code ?? ''))),
            'week' => (string) ($batch->labelRequest?->week ?? ''),
            'year' => (string) ($batch->labelRequest?->request_date?->format('Y') ?? ''),
            'serial_standard' => (string) ($batch->labelRequest?->serial_standard ?? 'UL'),
        ];
    }

    private function resolveSerialFormat(?string $sku, string $serialStandard): ?SkuSerialFormat
    {
        if (!$sku) {
            return null;
        }

        return SkuSerialFormat::query()
            ->with(['ulConfig', 'emeaConfig', 'anzConfig'])
            ->active()
            ->where('sku', $sku)
            ->where(function ($query) use ($serialStandard) {
                $standard = strtoupper(trim($serialStandard));

                $query->where('serial_standard', $standard)
                    ->orWhere('market', $standard);
            })
            ->latest('id')
            ->first();
    }

    private function toCompactSerial(string $value): string
    {
        return preg_replace('/[\s\|]+/', '', trim($value)) ?? trim($value);
    }

    private function toSegmentedSerial(string $value): string
    {
        $compact = strtoupper($this->toCompactSerial($value));

        if (!preg_match('/^[A-Z0-9]{19}$/', $compact)) {
            return trim($value);
        }

        return implode(' ', [
            substr($compact, 0, 4),
            substr($compact, 4, 2),
            substr($compact, 6, 2),
            substr($compact, 8, 6),
            substr($compact, 14, 5),
        ]);
    }

    private function renderTemplate(string $template, array $payload): string
    {
        return preg_replace_callback('/\{\{\s*([\w\.\-]+)\s*\}\}/', function (array $matches) use ($payload) {
            return (string) ($payload[$matches[1]] ?? '');
        }, $template) ?? $template;
    }

    private function resolveProfile(?int $skuId, string $labelType, string $standard): ?LabelPrintProfile
    {
        return LabelPrintProfile::query()
            ->active()
            ->where('label_type', $labelType)
            ->where('serial_standard', $standard)
            ->where(function ($query) use ($skuId) {
                $query->where('label_sku_id', $skuId)->orWhereNull('label_sku_id');
            })
            ->orderByRaw('CASE WHEN label_sku_id IS NULL THEN 1 ELSE 0 END')
            ->latest('id')
            ->first();
    }

    private function resolveTemplate(?int $profileTemplateId, ?int $skuId, string $labelType, string $standard): ?LabelTemplate
    {
        if ($profileTemplateId) {
            return LabelTemplate::query()->whereKey($profileTemplateId)->first();
        }

        return LabelTemplate::query()
            ->active()
            ->where('label_type', $labelType)
            ->where('serial_standard', $standard)
            ->where(function ($query) use ($skuId) {
                $query->where('label_sku_id', $skuId)->orWhereNull('label_sku_id');
            })
            ->orderByRaw('CASE WHEN label_sku_id IS NULL THEN 1 ELSE 0 END')
            ->latest('id')
            ->first();
    }

    private function resolveTemplateZpl(LabelTemplate $template, string $labelType, string $standard): string
    {
        $layout = $template->resolved_serial_layout;

        if (in_array($labelType, ['serial', 'rating'], true) && is_array($layout) && $layout !== []) {
            return $this->zplBuilder->build($labelType, $layout, $standard);
        }

        return (string) $template->zpl;
    }
}
