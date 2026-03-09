<?php

namespace App\Services\Labels;

use App\Models\LabelPrintBatch;
use App\Models\LabelPrintBatchItem;
use App\Models\LabelRequest;
use App\Models\LabelSku;
use App\Models\SkuSerialFormat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelPrintService
{
    public function createBatch(LabelRequest $labelRequest, array $data, ?int $printedByUserId, string $printedByName): LabelPrintBatch
    {
        if (in_array($labelRequest->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'No se puede imprimir una requisición cerrada.',
            ]);
        }

         if ($labelRequest->include_serial) {
            $labelSku = LabelSku::query()
                ->where('label_part_number', $labelRequest->label_part_number)
                ->where('is_active', true)
                ->first();

            if (!$labelSku) {
                throw ValidationException::withMessages([
                    'label_part_number' => 'El Label PN no está activo en el catálogo SKU/NP.',
                ]);
            }

            $hasActiveFormat = SkuSerialFormat::query()
                ->where('sku', $labelSku->sku)
                ->where('is_active', true)
                ->exists();

            if (!$hasActiveFormat) {
                throw ValidationException::withMessages([
                    'sku_serial_format' => 'Para imprimir seriales, el SKU debe tener un formato serial activo.',
                ]);
            }
        }

        return DB::transaction(function () use ($labelRequest, $data, $printedByUserId, $printedByName) {
            $batch = LabelPrintBatch::query()->create([
                'label_request_id' => $labelRequest->id,
                'shift_id' => $labelRequest->shift_id,
                'batch_type' => $data['batch_type'],
                'reason' => $data['reason'] ?? null,
                'printed_by_user_id' => $printedByUserId,
                'printed_by_name' => $printedByName,
                'printed_at' => now(),
            ]);

            LabelPrintBatchItem::query()->create([
                'label_print_batch_id' => $batch->id,
                'serial_unit_id' => null,
                'print_serial' => (bool) $labelRequest->include_serial,
                'print_rating' => (bool) $labelRequest->include_rating,
                'copies' => (int) $data['copies'],
            ]);

            if ($labelRequest->status === 'requested') {
                $labelRequest->update(['status' => 'in_progress']);
            }

            return $batch->load(['items', 'labelRequest']);
        });
    }
}
