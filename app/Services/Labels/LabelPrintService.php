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
use Illuminate\Support\Collection;
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
            $printSerial = (bool) $data['print_serial'];
            $printRating = (bool) $data['print_rating'];
            $selectedSerialUnitIds = collect($data['selected_serial_unit_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
            $selectedRatingUnitIds = collect($data['selected_rating_unit_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();

            if (!$printSerial && !$printRating) {
                throw ValidationException::withMessages([
                    'print_serial' => 'Debes seleccionar al menos una opción: serial o rating.',
                ]);
            }

            if ($printSerial && !$labelRequest->include_serial) {
                throw ValidationException::withMessages([
                    'print_serial' => 'La requisición no permite impresión de serial.',
                ]);
            }

            if ($printRating && !$labelRequest->include_rating) {
                throw ValidationException::withMessages([
                    'print_rating' => 'La requisición no permite impresión de rating.',
                ]);
            }

            $ranges = SerialRange::query()
                ->where('label_request_id', $labelRequest->id)
                ->orderBy('range_start')
                ->get();

            $batch = LabelPrintBatch::query()->create([
                'label_request_id' => $labelRequest->id,
                'serial_week_id' => null,
                'shift_id' => $labelRequest->shift_id,
                'batch_type' => $data['batch_type'],
                'reason' => $data['reason'] ?? null,
                'printed_by_user_id' => $printedByUserId,
                'printed_by_name' => $printedByName,
                'printed_at' => null,
            ]);

            if ($isPrintBatch) {
                if ((int) $labelRequest->quantity_requested <= 0) {
                    throw ValidationException::withMessages([
                        'status' => 'La requisición debe tener cantidad solicitada mayor a 0.',
                    ]);
                }

                if ($ranges->isEmpty()) {
                    $serialFormat = $this->resolveSerialFormat($labelRequest);
                    if (!$serialFormat) {
                        throw ValidationException::withMessages([
                            'batch_type' => 'No existe un formato activo en sku_serial_formats para este SKU.',
                        ]);
                    }

                    $week = $this->resolveSerialWeek($labelRequest, $serialFormat);
                    $reservedUnits = $this->reserveSerialUnits(
                        $week,
                        (int) $labelRequest->quantity_requested,
                        $labelRequest,
                        $serialFormat,
                        $printedByUserId,
                    );

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
                    $this->appendBatchItemsFromRanges($batch, $ranges, $printSerial, $printRating, $copies);
                }
            } else {
                if ($ranges->isEmpty()) {
                    throw ValidationException::withMessages([
                        'batch_type' => 'No hay rango asignado para reimprimir. Primero crea un batch de tipo print.',
                    ]);
                }

                if ($selectedSerialUnitIds->isNotEmpty() || $selectedRatingUnitIds->isNotEmpty()) {
                    $this->appendBatchItemsFromSelectedUnits(
                        batch: $batch,
                        labelRequest: $labelRequest,
                        ranges: $ranges,
                        selectedSerialUnitIds: $selectedSerialUnitIds,
                        selectedRatingUnitIds: $selectedRatingUnitIds,
                        copies: $copies,
                    );
                } else {
                    $this->appendBatchItemsFromRanges($batch, $ranges, $printSerial, $printRating, $copies);
                }
            }

            if ($isPrintBatch && $labelRequest->status === 'requested') {
                $labelRequest->update(['status' => 'in_progress']);
            }

            return $batch->load(['items', 'labelRequest']);
        });
    }

    private function appendBatchItemsFromRanges(LabelPrintBatch $batch, $ranges, bool $printSerial, bool $printRating, int $copies): void
    {
        $weekId = (int) $ranges->first()->serial_week_id;
        $batch->update(['serial_week_id' => $weekId]);

        $units = SerialUnit::query()
            ->where('serial_week_id', $weekId)
            ->where(function ($query) use ($ranges) {
                foreach ($ranges as $range) {
                    $query->orWhereBetween('serial_number', [$range->range_start, $range->range_end]);
                }
            })
            ->orderBy('serial_number')
            ->get(['id']);

        foreach ($units as $unit) {
            LabelPrintBatchItem::query()->create([
                'label_print_batch_id' => $batch->id,
                'serial_unit_id' => $unit->id,
                'print_serial' => $printSerial,
                'print_rating' => $printRating,
                'copies' => $copies,
            ]);
        }
    }

    private function appendBatchItemsFromSelectedUnits(
        LabelPrintBatch $batch,
        LabelRequest $labelRequest,
        $ranges,
        Collection $selectedSerialUnitIds,
        Collection $selectedRatingUnitIds,
        int $copies
    ): void {
        $weekId = (int) $ranges->first()->serial_week_id;
        $batch->update(['serial_week_id' => $weekId]);

        $selectedUnionIds = $selectedSerialUnitIds->merge($selectedRatingUnitIds)->unique()->values();

        if ($selectedUnionIds->isEmpty()) {
            throw ValidationException::withMessages([
                'selection' => 'Debes seleccionar al menos un serial o rating para reimpresión/retrabajo.',
            ]);
        }

        if ($selectedSerialUnitIds->isNotEmpty() && !$labelRequest->include_serial) {
            throw ValidationException::withMessages([
                'selection' => 'La requisición no permite serial; no puedes seleccionarlos en retrabajo.',
            ]);
        }

        if ($selectedRatingUnitIds->isNotEmpty() && !$labelRequest->include_rating) {
            throw ValidationException::withMessages([
                'selection' => 'La requisición no permite rating; no puedes seleccionarlos en retrabajo.',
            ]);
        }

        $allowedIds = SerialUnit::query()
            ->where('serial_week_id', $weekId)
            ->where(function ($query) use ($ranges) {
                foreach ($ranges as $range) {
                    $query->orWhereBetween('serial_number', [$range->range_start, $range->range_end]);
                }
            })
            ->whereIn('id', $selectedUnionIds)
            ->pluck('id');

        if ($allowedIds->count() !== $selectedUnionIds->count()) {
            throw ValidationException::withMessages([
                'selection' => 'Uno o más seriales seleccionados no pertenecen al rango de la requisición.',
            ]);
        }

        foreach ($allowedIds as $unitId) {
            LabelPrintBatchItem::query()->create([
                'label_print_batch_id' => $batch->id,
                'serial_unit_id' => $unitId,
                'print_serial' => $selectedSerialUnitIds->contains($unitId),
                'print_rating' => $selectedRatingUnitIds->contains($unitId),
                'copies' => $copies,
            ]);
        }
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
        if (!$serialFormat) {
            $yy = substr((string) $week->year, -2);
            $ww = str_pad((string) $week->week, 2, '0', STR_PAD_LEFT);
            $serial = str_pad((string) $serialNumber, 5, '0', STR_PAD_LEFT);

            return $labelRequest->label_part_number . "-{$yy}{$ww}{$serial}";
        }

        if (!empty($serialFormat->pattern)) {
            return $this->formatFromLegacyPattern($week, $serialFormat, $serialNumber);
        }

        return $this->formatFromComponents($week, $serialFormat, $serialNumber);
    }

    private function formatFromLegacyPattern(SerialWeek $week, SkuSerialFormat $serialFormat, int $serialNumber): string
    {
        $year = $this->resolveYearValue($week, (int) ($serialFormat->year_digits ?? 2));
        $weekValue = $this->resolveWeekValue($week, (int) ($serialFormat->week_digits ?? 2));
        $serial = str_pad((string) $serialNumber, $serialFormat->unit_length ?? 5, '0', STR_PAD_LEFT);

        $pattern = $this->normalizeSerialPattern((string) $serialFormat->pattern);

        return strtr($pattern, [
            '{PPP}' => (string) ($serialFormat->prefix ?? ''),
            '{C}' => (string) ($serialFormat->serial_break ?? ''),
            '{PL}' => (string) ($serialFormat->plant_code ?? ''),
            '{YY}' => $year,
            '{WW}' => $weekValue,
            '{SSSSS}' => $serial,
        ]);
    }

    private function formatFromComponents(SerialWeek $week, SkuSerialFormat $serialFormat, int $serialNumber): string
    {
        $components = [
            (string) ($serialFormat->prefix ?? ''),
            (string) ($serialFormat->serial_break ?? ''),
            (string) ($serialFormat->plant_code ?? ''),
        ];

        if ((bool) ($serialFormat->include_year ?? true)) {
            $components[] = $this->resolveYearValue($week, (int) ($serialFormat->year_digits ?? 2));
        }

        if ((bool) ($serialFormat->include_week ?? true)) {
            $components[] = $this->resolveWeekValue($week, (int) ($serialFormat->week_digits ?? 2));
        }

        $components[] = str_pad((string) $serialNumber, $serialFormat->unit_length ?? 5, '0', STR_PAD_LEFT);

        $separator = (string) ($serialFormat->separator ?? '');

        return collect($components)
            ->filter(fn (string $component) => $component !== '')
            ->implode($separator);
    }

    private function resolveYearValue(SerialWeek $week, int $digits): string
    {
        $year = (string) $week->year;

        if ($digits >= 4) {
            return str_pad(substr($year, -4), 4, '0', STR_PAD_LEFT);
        }

        return str_pad(substr($year, -2), 2, '0', STR_PAD_LEFT);
    }

    private function resolveWeekValue(SerialWeek $week, int $digits): string
    {
        return str_pad((string) $week->week, max(1, $digits), '0', STR_PAD_LEFT);
    }

    private function normalizeSerialPattern(string $pattern): string
    {
        $normalized = preg_replace('/\{\{\s*(PPP|C|PL|YY|WW|SSSSS)\s*\}\}/', '{$1}', $pattern) ?? $pattern;

        if (!str_contains($normalized, '{SSSSS}')) {
            $normalized .= '{SSSSS}';
        }

        return $normalized;
    }
}
