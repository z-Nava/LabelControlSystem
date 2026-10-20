<?php

namespace App\Services\Kiosk;

use App\Models\LabelRequest;
use Illuminate\Support\Carbon;

class KioskRequisitionLabelZplBuilder extends AbstractKioskRequisitionLabelZplBuilder
{
    private const VISIBLE_ROWS = 8;

    public function build(LabelRequest $labelRequest, int $dpi = self::BASE_DPI): string
    {
        $dimensions = $this->dimensions($dpi);
        $rows = $this->requestRows($labelRequest);

        if ($labelRequest->isLpk()) {
            [$orderedRows, $visibleRows] = $this->lpkRows($rows);
        } else {
            $shippingRows = array_values(array_filter($rows, fn (array $row): bool => str_contains($row['type'], 'SHIPPING')));
            $otherRows = array_values(array_filter($rows, fn (array $row): bool => ! str_contains($row['type'], 'SHIPPING')));
            $orderedRows = [...$shippingRows, ...$otherRows];
            $visibleRows = array_slice($orderedRows, 0, self::VISIBLE_ROWS);
        }

        return implode("\n", $this->page($labelRequest, $orderedRows, $visibleRows, $dimensions));
    }

    /**
     * Keep at least one row of each requested LPK type visible. Fill the
     * remaining space in Rating, Serial, Shipping, Inner order.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array{array<int, array<string, string>>, array<int, array<string, string>>}
     */
    private function lpkRows(array $rows): array
    {
        $groups = [
            'RATING' => [],
            'SERIAL' => [],
            'SHIPPING' => [],
            'INNER' => [],
            'OTHER' => [],
        ];

        foreach ($rows as $row) {
            $type = $row['type'];
            $key = match (true) {
                str_contains($type, 'RATING') => 'RATING',
                str_contains($type, 'SERIAL') => 'SERIAL',
                str_contains($type, 'SHIPPING') => 'SHIPPING',
                str_contains($type, 'INNER') => 'INNER',
                default => 'OTHER',
            };
            $groups[$key][] = $row;
        }

        $orderedRows = array_merge(...array_values($groups));
        $visibleGroups = array_fill_keys(array_keys($groups), []);
        $visibleCount = 0;

        foreach ($groups as $key => $groupRows) {
            if ($groupRows !== [] && $visibleCount < self::VISIBLE_ROWS) {
                $visibleGroups[$key][] = array_shift($groups[$key]);
                $visibleCount++;
            }
        }

        foreach ($groups as $key => $groupRows) {
            foreach ($groupRows as $row) {
                if ($visibleCount >= self::VISIBLE_ROWS) {
                    break 2;
                }

                $visibleGroups[$key][] = $row;
                $visibleCount++;
            }
        }

        return [$orderedRows, array_merge(...array_values($visibleGroups))];
    }

    /**
     * @param  array<int, array{type: string, part_number: string, job: string, model: string, quantity: string, po: string, destination: string}>  $allRows
     * @param  array<int, array{type: string, part_number: string, job: string, model: string, quantity: string, po: string, destination: string}>  $rows
     * @param  array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float}  $dimensions
     * @return array<int, string>
     */
    private function page(LabelRequest $labelRequest, array $allRows, array $rows, array $dimensions): array
    {
        $jobs = collect($allRows)->pluck('job')->filter(fn (string $job): bool => $job !== 'N/A')->unique()->values();
        $job = match ($jobs->count()) {
            0 => $labelRequest->job_number ?: 'N/A',
            1 => (string) $jobs->first(),
            default => 'VARIAS JOBS (VER RENGLONES)',
        };
        $title = $labelRequest->isLpk() ? 'REQUISICION ETIQUETAS LPK' : 'REQUISICION DE ETIQUETAS';
        $ratingRow = collect($allRows)->first(fn (array $row): bool => str_contains($row['type'], 'RATING') && $row['job'] !== 'N/A');
        $qrPayload = $labelRequest->isLpk()
            ? ($ratingRow['job'] ?? "LPK:{$labelRequest->id}")
            : (string) ($labelRequest->job_number ?: $labelRequest->id);
        $folio = sprintf('#%06d', (int) $labelRequest->id);
        $displayTimezone = $this->displayTimezone();
        $createdAt = $this->formatDate(
            $labelRequest->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now($displayTimezone)->format('d/m/Y H:i'),
            $displayTimezone,
        );
        $lineName = trim(implode(' ', array_filter([
            $labelRequest->line?->code,
            $labelRequest->line?->name,
        ]))) ?: 'SIN LINEA';
        $types = $labelRequest->isLpk()
            ? implode(', ', array_unique(array_map(
                fn (array $row): string => match (true) {
                    str_contains($row['type'], 'SHIPPING') => 'Shipping LPK',
                    str_contains($row['type'], 'RATING') => 'Rating',
                    str_contains($row['type'], 'SERIAL') => 'Serial',
                    str_contains($row['type'], 'INNER') => 'Inner',
                    default => $row['type'],
                },
                $allRows,
            )))
            : implode(', ', $labelRequest->requestedLabelTypes());
        $types = $types ?: 'VER RENGLONES';
        $commonModel = $this->commonValue($allRows, 'model', (string) ($labelRequest->model ?: 'N/A'));
        $commonPo = $this->commonValue($allRows, 'po', (string) ($labelRequest->po_number ?: 'N/A'));
        $commonDestination = $this->commonValue($allRows, 'destination', (string) ($labelRequest->destination ?: 'N/A'));
        $remaining = count($allRows) - count($rows);

        $fields = [
            ...$this->startLabel($dimensions),
            $this->field(28, 27, 620, 31, $title, $dimensions),
            $this->field(28, 67, 610, 37, $folio, $dimensions),
            $this->qr(675, 24, $qrPayload, $dimensions, 3),
            $this->field(28, 120, 750, 18, "REGISTRADA: {$createdAt}  |  LINEA: {$lineName}", $dimensions),
            $this->line(25, 150, 765, 2, $dimensions),
            $this->field(30, 164, 760, 25, "JOB: {$job}", $dimensions),
            $this->field(30, 198, 440, 18, "MODELO: {$commonModel}", $dimensions),
            $this->field(475, 198, 305, 18, 'CANTIDAD: '.number_format((int) $labelRequest->quantity_requested), $dimensions),
            $this->field(30, 225, 375, 18, "PO: {$commonPo}", $dimensions),
            $this->field(410, 225, 370, 18, "DESTINO: {$commonDestination}", $dimensions),
            $this->field(30, 252, 375, 18, 'LIDER: '.($labelRequest->leader_name ?: 'N/A'), $dimensions),
            $this->field(410, 252, 370, 18, 'SOLICITA: '.($labelRequest->requested_by_name ?: 'N/A'), $dimensions),
            $this->field(30, 279, 375, 18, 'TRABAJO: '.$labelRequest->folioModeLabel(), $dimensions),
            $this->field(410, 279, 370, 18, "TIPOS: {$types}", $dimensions),
            $this->line(25, 307, 765, 2, $dimensions),
        ];

        foreach ($rows as $index => $row) {
            $y = 315 + ($index * 100);
            $fields[] = $this->box(25, $y, 765, 94, 2, $dimensions);
            $fields[] = $this->field(36, $y + 7, 165, 19, $row['type'], $dimensions);
            $fields[] = $this->field(205, $y + 7, 290, 19, 'NP: '.$row['part_number'], $dimensions);
            $fields[] = $this->field(500, $y + 7, 275, 17, $row['quantity'], $dimensions);
            $fields[] = $this->field(36, $y + 34, 740, 22, 'JOB: '.$row['job'], $dimensions);
            $fields[] = $this->field(36, $y + 65, 265, 16, 'MODELO: '.$row['model'], $dimensions);
            $fields[] = $this->field(305, $y + 65, 220, 16, 'PO: '.$row['po'], $dimensions);
            $fields[] = $this->field(535, $y + 65, 240, 16, 'DESTINO: '.$row['destination'], $dimensions);
        }

        if ($remaining > 0) {
            $fields[] = $this->field(30, 1121, 750, 17, "+{$remaining} RENGLONES MAS: CONSULTAR REQUISICION {$folio}", $dimensions);
        }

        return [
            ...$fields,
            ...$this->footer($dimensions),
            '^PQ1,0,1,N',
            '^XZ',
        ];
    }

    /**
     * @return array<int, array{type: string, part_number: string, job: string, model: string, quantity: string, po: string, destination: string}>
     */
    private function requestRows(LabelRequest $labelRequest): array
    {
        $rows = [];

        foreach ($labelRequest->requestedLabelLines() as $line) {
            $shippingItems = $line['shipping_items'] ?? [];

            if ($shippingItems !== []) {
                foreach ($shippingItems as $item) {
                    $rows[] = $this->row(
                        $line,
                        $labelRequest,
                        $item,
                        'TOTAL COMP. '.number_format((int) ($line['quantity'] ?? 0)),
                    );
                }

                continue;
            }

            $rows[] = $this->row($line, $labelRequest);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $line
     * @param  array<string, mixed>  $item
     * @return array{type: string, part_number: string, job: string, model: string, quantity: string, po: string, destination: string}
     */
    private function row(array $line, LabelRequest $labelRequest, array $item = [], ?string $quantity = null): array
    {
        return [
            'type' => strtoupper((string) ($line['type'] ?? 'ETIQUETA')),
            'part_number' => $this->displayValue($line['part_number'] ?? null),
            'job' => $this->displayValue($item['job_number'] ?? $line['job_number'] ?? $labelRequest->job_number),
            'model' => $this->displayValue($item['model'] ?? $line['model'] ?? $labelRequest->model),
            'quantity' => $quantity ?: 'CANT: '.number_format((int) ($line['quantity'] ?? 0)),
            'po' => $this->displayValue($item['po_number'] ?? $line['po_number'] ?? $labelRequest->po_number),
            'destination' => $this->displayValue($item['destination'] ?? $line['destination'] ?? $labelRequest->destination),
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function commonValue(array $rows, string $key, string $fallback): string
    {
        $values = collect($rows)->pluck($key)->filter(fn (string $value): bool => $value !== 'N/A')->unique()->values();

        return $values->count() === 1 ? (string) $values->first() : ($values->isEmpty() ? $fallback : 'VER RENGLONES');
    }

    private function displayValue(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : 'N/A';
    }
}
