<?php

namespace App\Services\Labels;

use App\Models\LabelPrintBatch;
use App\Models\LabelPrintBatchItem;
use App\Models\LabelPrintBlock;
use App\Models\LabelPrintProfile;
use App\Models\LabelSku;
use App\Models\LabelTemplate;
use App\Models\SerialUnit;
use App\Models\SkuSerialFormat;
use App\Support\LabelDimensions;
use Illuminate\Support\Collection;
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
        $batch->loadMissing([
            'labelRequest',
            'blocks' => fn ($query) => $query->orderBy('sequence')->orderBy('label_type'),
        ]);

        if ($batch->blocks->isEmpty()) {
            throw ValidationException::withMessages([
                'batch' => 'El batch no tiene bloques de impresion asociados para renderizar.',
            ]);
        }

        $block = $this->nextPrintableBlock($batch);
        $blocks = $this->blocksSummary($batch);

        if (!$block) {
            return [
                'batch_id' => $batch->id,
                'label_request_id' => $batch->label_request_id,
                'batch_complete' => true,
                'blocks' => $blocks,
                'current_block' => null,
                'documents' => [],
                'alignment_documents' => [],
                'zpl' => '',
            ];
        }

        $preview = $this->buildBlockPreview($batch, $block, $blocks);
        $preview['alignment_documents'] = $this->buildAlignmentDocuments($batch, $preview['documents']);

        return $preview;
    }

    public function confirmPrinted(LabelPrintBatch $batch, ?int $blockId = null): array
    {
        return DB::transaction(function () use ($batch, $blockId) {
            $block = $this->resolveActionBlock($batch, $blockId);

            if ($block->status === 'confirmed') {
                return $this->buildConfirmationResult($batch, $block, 0, 'Este bloque ya estaba confirmado como impreso.');
            }

            $now = now();
            $block->forceFill([
                'status' => 'confirmed',
                'attempts' => max(1, (int) $block->attempts + ($block->status === 'sent' ? 0 : 1)),
                'sent_at' => $block->sent_at ?: $now,
                'confirmed_at' => $now,
                'failed_at' => null,
                'last_error' => null,
            ])->save();

            $unitIds = $this->unitIdsForBlock($block);
            if ($unitIds->isNotEmpty()) {
                $field = $block->label_type === 'rating' ? 'rating_printed_at' : 'serial_printed_at';

                SerialUnit::query()
                    ->whereIn('id', $unitIds)
                    ->update([$field => $now]);

                $this->syncAggregateSerialUnitStatus($batch, $unitIds->all(), $now);
            }

            $batchComplete = !$batch->blocks()
                ->where('status', '!=', 'confirmed')
                ->exists();

            if ($batchComplete && $batch->printed_at === null) {
                $batch->update(['printed_at' => $now]);
            }

            return $this->buildConfirmationResult(
                $batch->fresh(['blocks']),
                $block->fresh(),
                $unitIds->count(),
                $batchComplete
                    ? 'Todos los bloques fueron confirmados. Impresion completa.'
                    : 'Bloque confirmado. Continua con el siguiente bloque pendiente.'
            );
        });
    }

    public function failBlock(LabelPrintBatch $batch, ?int $blockId, ?string $message = null): array
    {
        return DB::transaction(function () use ($batch, $blockId, $message) {
            $block = $this->resolveActionBlock($batch, $blockId);

            if ($block->status === 'confirmed') {
                throw ValidationException::withMessages([
                    'block' => 'No se puede marcar como fallido un bloque ya confirmado.',
                ]);
            }

            $now = now();
            $block->forceFill([
                'status' => 'failed',
                'attempts' => (int) $block->attempts + 1,
                'sent_at' => $block->sent_at ?: $now,
                'failed_at' => $now,
                'last_error' => mb_substr((string) ($message ?: 'Falla reportada durante impresion.'), 0, 255),
            ])->save();

            $batch->load(['blocks' => fn ($query) => $query->orderBy('sequence')->orderBy('label_type')]);

            return [
                'message' => 'Bloque marcado como fallido. Queda disponible para reintento.',
                'batch_id' => $batch->id,
                'block_id' => $block->id,
                'batch_complete' => false,
                'blocks' => $this->blocksSummary($batch),
                'next_block' => $this->blockSummary($this->nextPrintableBlock($batch)),
            ];
        });
    }

    private function buildBlockPreview(LabelPrintBatch $batch, LabelPrintBlock $block, array $blocks): array
    {
        $block->loadMissing(['blockItems.batchItem.serialUnit']);

        $items = $block->blockItems
            ->map(fn ($blockItem) => $blockItem->batchItem)
            ->filter(fn ($item) => $item instanceof LabelPrintBatchItem && $item->serialUnit !== null)
            ->sortBy(fn (LabelPrintBatchItem $item) => $item->serialUnit?->serial_number ?? 0)
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'block' => 'El bloque no tiene serial units asociados para renderizar.',
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
        $labelType = (string) $block->label_type;
        $standard = (string) ($batch->labelRequest?->serial_standard ?? 'UL');
        $serialFormat = $this->resolveSerialFormat($sku?->sku, $standard);
        $profile = $this->resolveProfile($skuId, $labelType, $standard);
        $template = $this->resolveTemplate($profile?->label_template_id, $skuId, $labelType, $standard);

        if (!$template) {
            throw ValidationException::withMessages([
                'template' => "No existe template activo para tipo {$labelType}.",
            ]);
        }

        $zplLabels = [];
        $testLabel = null;
        foreach ($items as $item) {
            $payload = $this->buildPayload($batch, $item->serialUnit, $labelType, $sku, $serialFormat);
            $templateZpl = $this->resolveTemplateZpl($template, $labelType, $standard);
            $rendered = $this->renderTemplate($templateZpl, $payload);
            $testLabel ??= $rendered;

            for ($copy = 1; $copy <= (int) $item->copies; $copy++) {
                $zplLabels[] = $rendered;
            }
        }

        $documents = [[
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
            'label_size' => LabelDimensions::fromTemplate($template, $profile),
            'units_count' => count($zplLabels),
            'test_zpl' => (string) ($testLabel ?? ''),
            'zpl' => implode("\n", $zplLabels),
        ]];

        return [
            'batch_id' => $batch->id,
            'label_request_id' => $batch->label_request_id,
            'batch_complete' => false,
            'block_id' => $block->id,
            'current_block' => $this->blockSummary($block),
            'blocks' => $blocks,
            'documents' => $documents,
            'zpl' => collect($documents)->pluck('zpl')->filter()->implode("\n"),
        ];
    }

    /**
     * Build one lightweight editable sample for every label type in the batch.
     * The documents used by the print action remain limited to the active block.
     *
     * @param array<int, array<string, mixed>> $currentDocuments
     * @return array<int, array<string, mixed>>
     */
    private function buildAlignmentDocuments(LabelPrintBatch $batch, array $currentDocuments): array
    {
        $currentByType = collect($currentDocuments)->keyBy('label_type');

        return collect(['serial', 'rating'])
            ->map(function (string $labelType) use ($batch, $currentByType): ?array {
                $document = $currentByType->get($labelType);

                if (!$document) {
                    $sampleBlock = $batch->blocks->first(
                        fn (LabelPrintBlock $block) => $block->label_type === $labelType
                    );
                    if (!$sampleBlock) {
                        return null;
                    }

                    try {
                        $document = $this->buildBlockPreview($batch, $sampleBlock, [])['documents'][0] ?? null;
                    } catch (ValidationException) {
                        return null;
                    }
                }

                if (!$document) {
                    return null;
                }

                return collect($document)->only([
                    'label_type',
                    'profile',
                    'template',
                    'label_size',
                    'test_zpl',
                ])->all();
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildConfirmationResult(LabelPrintBatch $batch, LabelPrintBlock $block, int $updatedUnits, string $message): array
    {
        $batch->load(['blocks' => fn ($query) => $query->orderBy('sequence')->orderBy('label_type')]);
        $batchComplete = !$batch->blocks->contains(fn (LabelPrintBlock $candidate) => $candidate->status !== 'confirmed');

        return [
            'message' => $message,
            'batch_id' => $batch->id,
            'block_id' => $block->id,
            'block' => $this->blockSummary($block),
            'blocks' => $this->blocksSummary($batch),
            'next_block' => $this->blockSummary($this->nextPrintableBlock($batch)),
            'batch_complete' => $batchComplete,
            'updated_serial_units' => $updatedUnits,
            'printed_at' => $batch->fresh()->printed_at?->toDateTimeString(),
        ];
    }

    private function resolveActionBlock(LabelPrintBatch $batch, ?int $blockId): LabelPrintBlock
    {
        if ($blockId) {
            $block = LabelPrintBlock::query()
                ->where('label_print_batch_id', $batch->id)
                ->whereKey($blockId)
                ->first();
        } else {
            $batch->load(['blocks' => fn ($query) => $query->orderBy('sequence')->orderBy('label_type')]);
            $block = $this->nextPrintableBlock($batch);
        }

        if (!$block) {
            throw ValidationException::withMessages([
                'block' => 'No hay bloque pendiente para esta impresion.',
            ]);
        }

        return $block;
    }

    private function nextPrintableBlock(LabelPrintBatch $batch): ?LabelPrintBlock
    {
        $batch->loadMissing(['blocks' => fn ($query) => $query->orderBy('sequence')->orderBy('label_type')]);

        return $batch->blocks
            ->filter(fn (LabelPrintBlock $block) => in_array($block->status, ['pending', 'sent', 'failed'], true))
            ->sortBy(fn (LabelPrintBlock $block) => $this->blockSortKey($block))
            ->first();
    }

    private function blocksSummary(LabelPrintBatch $batch): array
    {
        $batch->loadMissing(['blocks' => fn ($query) => $query->orderBy('sequence')->orderBy('label_type')]);

        return $batch->blocks
            ->sortBy(fn (LabelPrintBlock $block) => $this->blockSortKey($block))
            ->map(fn (LabelPrintBlock $block) => $this->blockSummary($block))
            ->values()
            ->all();
    }

    private function blockSortKey(LabelPrintBlock $block): string
    {
        $typeOrder = $block->label_type === 'serial' ? 0 : 1;

        return str_pad((string) $block->sequence, 8, '0', STR_PAD_LEFT).'-'.$typeOrder;
    }

    private function blockSummary(?LabelPrintBlock $block): ?array
    {
        if (!$block) {
            return null;
        }

        return [
            'id' => $block->id,
            'label_type' => $block->label_type,
            'sequence' => $block->sequence,
            'unit_count' => $block->unit_count,
            'label_count' => $block->label_count,
            'status' => $block->status,
            'attempts' => $block->attempts,
            'sent_at' => $block->sent_at?->toDateTimeString(),
            'confirmed_at' => $block->confirmed_at?->toDateTimeString(),
            'failed_at' => $block->failed_at?->toDateTimeString(),
            'last_error' => $block->last_error,
        ];
    }

    private function unitIdsForBlock(LabelPrintBlock $block): Collection
    {
        $block->loadMissing(['blockItems.batchItem']);

        return $block->blockItems
            ->map(fn ($blockItem) => $blockItem->batchItem?->serial_unit_id)
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * @param array<int, int> $unitIds
     */
    private function syncAggregateSerialUnitStatus(LabelPrintBatch $batch, array $unitIds, mixed $printedAt): void
    {
        $itemsByUnit = $batch->items()
            ->whereIn('serial_unit_id', $unitIds)
            ->get(['serial_unit_id', 'print_serial', 'print_rating'])
            ->groupBy('serial_unit_id');

        $units = SerialUnit::query()
            ->whereIn('id', $unitIds)
            ->get(['id', 'serial_printed_at', 'rating_printed_at']);

        foreach ($units as $unit) {
            $items = $itemsByUnit->get($unit->id, collect());
            $requiresSerial = $items->contains(fn (LabelPrintBatchItem $item) => (bool) $item->print_serial);
            $requiresRating = $items->contains(fn (LabelPrintBatchItem $item) => (bool) $item->print_rating);
            $isComplete = (!$requiresSerial || $unit->serial_printed_at !== null)
                && (!$requiresRating || $unit->rating_printed_at !== null);

            if ($isComplete) {
                SerialUnit::query()
                    ->whereKey($unit->id)
                    ->update([
                        'status' => 'printed',
                        'printed_at' => $printedAt,
                    ]);
            }
        }
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
            'fixed_103' => (string) (($sku?->assembly_part_number ?: '103')),
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
