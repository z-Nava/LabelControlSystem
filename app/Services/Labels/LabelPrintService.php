<?php

namespace App\Services\Labels;

use App\Models\LabelPrintBatch;
use App\Models\LabelPrintBatchItem;
use App\Models\LabelRequest;
use App\Models\LabelSku;
use App\Models\SerialRange;
use App\Models\SerialUnit;
use App\Models\SerialWeek;
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

        return DB::transaction(function () use ($labelRequest, $data, $printedByUserId, $printedByName) {
            $copies = (int) $data['copies'];
            $isPrintBatch = $data['batch_type'] === 'print';
            $printSerial = (bool) $labelRequest->include_serial;
            $printRating = (bool) $labelRequest->include_rating;
            $needsSerialAllocation = $isPrintBatch && ($printSerial || $printRating);

            if ($isPrintBatch) {
                $printedQty = $this->printedQuantity($labelRequest->id);
                $remaining = (int) $labelRequest->quantity_requested - $printedQty;

                if ($remaining <= 0) {
                    throw ValidationException::withMessages([
                        'copies' => 'La requisición ya tiene toda la cantidad solicitada impresa.',
                    ]);
                }

                if ($copies > $remaining) {
                    throw ValidationException::withMessages([
                        'copies' => "Solo puedes imprimir {$remaining} copias para completar la requisición.",
                    ]);
                }
            }

            $batch = LabelPrintBatch::query()->create([
                'label_request_id' => $labelRequest->id,
                'serial_week_id' => null,
                'shift_id' => $labelRequest->shift_id,
                'batch_type' => $data['batch_type'],
                'reason' => $data['reason'] ?? null,
                'printed_by_user_id' => $printedByUserId,
                'printed_by_name' => $printedByName,
                'printed_at' => now(),
            ]);

            if ($needsSerialAllocation) {
                $serialFormat = $this->resolveSerialFormat($labelRequest);
                $week = $this->resolveSerialWeek($labelRequest, $serialFormat);
                $reservedUnits = $this->reserveSerialUnits($week, $copies, $labelRequest, $serialFormat, $printedByUserId);

                $batch->update(['serial_week_id' => $week->id]);

                foreach ($reservedUnits as $unit) {
                    LabelPrintBatchItem::query()->create([
                        'label_print_batch_id' => $batch->id,
                        'serial_unit_id' => $unit->id,
                        'print_serial' => $printSerial,
                        'print_rating' => $printRating,
                        'copies' => 1,
                    ]);
                }
            } else {
                LabelPrintBatchItem::query()->create([
                    'label_print_batch_id' => $batch->id,
                    'serial_unit_id' => null,
                    'print_serial' => $printSerial,
                    'print_rating' => $printRating,
                    'copies' => $copies,
                ]);
            }

            if ($isPrintBatch) {
                $newPrintedQty = $this->printedQuantity($labelRequest->id);
                $newStatus = $newPrintedQty >= (int) $labelRequest->quantity_requested
                    ? 'completed'
                    : 'in_progress';

                if ($labelRequest->status !== $newStatus) {
                    $labelRequest->update(['status' => $newStatus]);
                }
            }

            return $batch->load(['items', 'labelRequest']);
        });
    }

    private function printedQuantity(int $labelRequestId): int
    {
        return (int) LabelPrintBatchItem::query()
            ->selectRaw('COALESCE(SUM(label_print_batch_items.copies), 0) as total')
            ->join('label_print_batches', 'label_print_batches.id', '=', 'label_print_batch_items.label_print_batch_id')
            ->where('label_print_batches.label_request_id', $labelRequestId)
            ->where('label_print_batches.batch_type', 'print')
            ->value('total');
    }

    private function resolveSerialWeek(LabelRequest $labelRequest, ?SkuSerialFormat $serialFormat): SerialWeek
    {
        $year = (int) $labelRequest->request_date->format('Y');

        return SerialWeek::query()->firstOrCreate(
            [
                'label_part_number' => $labelRequest->label_part_number,
                'week' => (int) $labelRequest->week,
                'year' => $year,
            ],
            [
                'prefix' => $serialFormat?->prefix,
                'last_serial_number' => 0,
            ],
        );
    }

    private function resolveSerialFormat(LabelRequest $labelRequest): ?SkuSerialFormat
    {
        $sku = LabelSku::query()
            ->where('label_part_number', $labelRequest->label_part_number)
            ->where('is_active', true)
            ->value('sku');

        if (!$sku) {
            return null;
        }

        return SkuSerialFormat::query()
            ->where('sku', $sku)
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    /**
     * @return array<int, SerialUnit>
     */
    private function reserveSerialUnits(SerialWeek $week, int $quantity, LabelRequest $labelRequest, ?SkuSerialFormat $serialFormat, ?int $printedByUserId): array
    {
        $week = SerialWeek::query()->lockForUpdate()->findOrFail($week->id);

        $start = (int) $week->last_serial_number + 1;
        $end = $start + $quantity - 1;

        SerialRange::query()->create([
            'serial_week_id' => $week->id,
            'range_start' => $start,
            'range_end' => $end,
            'quantity' => $quantity,
            'label_request_id' => $labelRequest->id,
            'created_by_user_id' => $printedByUserId,
        ]);

        $units = [];
        for ($number = $start; $number <= $end; $number++) {
            $units[] = SerialUnit::query()->create([
                'serial_week_id' => $week->id,
                'serial_number' => $number,
                'serial_full' => $this->formatSerialFull($labelRequest, $week, $serialFormat, $number),
                'status' => 'allocated',
            ]);
        }

        $week->update(['last_serial_number' => $end]);

        return $units;
    }

    private function formatSerialFull(LabelRequest $labelRequest, SerialWeek $week, ?SkuSerialFormat $serialFormat, int $serialNumber): string
    {
        $yy = substr((string) $week->year, -2);
        $ww = str_pad((string) $week->week, 2, '0', STR_PAD_LEFT);
        $serial = str_pad((string) $serialNumber, $serialFormat?->unit_length ?? 5, '0', STR_PAD_LEFT);

        if (!$serialFormat) {
            return $labelRequest->label_part_number . "-{$yy}{$ww}{$serial}";
        }

        $pattern = $serialFormat->pattern ?: '{PPP}{C}{PL}{YY}{WW}{SSSSS}';

        return strtr($pattern, [
            '{PPP}' => (string) ($serialFormat->prefix ?? ''),
            '{C}' => (string) ($serialFormat->serial_break ?? ''),
            '{PL}' => (string) ($serialFormat->plant_code ?? ''),
            '{YY}' => $yy,
            '{WW}' => $ww,
            '{SSSSS}' => $serial,
        ]);
    }
}