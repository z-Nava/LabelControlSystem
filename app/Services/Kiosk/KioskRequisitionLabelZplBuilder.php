<?php

namespace App\Services\Kiosk;

use App\Models\LabelRequest;
use Illuminate\Support\Carbon;

class KioskRequisitionLabelZplBuilder extends AbstractKioskRequisitionLabelZplBuilder
{
    private const LAYOUT_MARKER = '^FX KIOSK_LABELS_BY_TYPE_V2^FS';

    private const LINE_HEIGHT = 25;

    private const FOOTER_Y = 1200;

    public function build(LabelRequest $labelRequest, int $dpi = self::BASE_DPI): string
    {
        $dimensions = $this->dimensions($dpi);
        $rows = $this->requestRows($labelRequest);
        $title = $labelRequest->isLpk() ? 'REQUISICION ETIQUETAS LPK' : 'REQUISICION DE ETIQUETAS';
        $ratingRow = collect($rows)->first(fn (array $row): bool => $row['type'] === 'RATING' && $row['job'] !== 'N/A');
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
        $header = [
            ...$this->startLabel($dimensions),
            $this->field(28, 27, 620, 31, $title, $dimensions),
            $this->field(28, 67, 610, 37, $folio, $dimensions),
            $this->qr(675, 24, $qrPayload, $dimensions, 3),
        ];
        $metadata = $this->columnLines([
            ["REGISTRADA: {$createdAt}", "LÍNEA: {$lineName}"],
            ['LÍDER: '.($labelRequest->leader_name ?: 'N/A'), 'SOLICITA: '.($labelRequest->requested_by_name ?: 'N/A')],
            ['TRABAJO: '.$labelRequest->folioModeLabel(), ''],
        ]);

        return $this->pagesByType($rows, $header, $metadata, $dimensions);
    }

    public function hasCurrentLayout(string $zpl): bool
    {
        return str_contains($zpl, self::LAYOUT_MARKER);
    }

    private function pagesByType(array $rows, array $header, array $metadata, array $dimensions): string
    {
        $groups = array_fill_keys(['RATING', 'SERIAL', 'SHIPPING', 'SHIPPING LPK', 'INNER'], []);
        foreach ($rows as $row) {
            $groups[$row['type']][] = $row;
        }
        // Older requests can lack printable details. Still issue their receipt.
        $groups = array_filter($groups) ?: ['SIN DETALLE' => []];
        $firstRowY = 134 + count($metadata) * self::LINE_HEIGHT;
        $header[] = self::LAYOUT_MARKER;
        $header = [...$header, ...$this->columnFields($metadata, 120, $dimensions)];
        $footer = [
            $this->line(25, self::FOOTER_Y, 765, 2, $dimensions),
            $this->field(36, 1218, 290, 20, 'IMPRIMIÓ:', $dimensions),
            $this->field(342, 1218, 290, 20, 'RECIBIÓ:', $dimensions),
            $this->field(650, 1218, 125, 20, 'TURNO:', $dimensions),
            $this->line(36, 1276, 285, 2, $dimensions),
            $this->line(342, 1276, 285, 2, $dimensions),
            $this->line(650, 1276, 125, 2, $dimensions),
            '^PQ1,0,1,N',
            '^XZ',
        ];

        $labels = [];
        foreach ($groups as $type => $typeRows) {
            $withFolios = in_array($type, ['RATING', 'SERIAL'], true);
            $pages = [];
            $fields = [];
            $y = $firstRowY;
            foreach ($typeRows as $row) {
                $lines = $this->columnLines([
                    ['JOB: '.$row['job'], $row['quantity']],
                    ['NP: '.$row['part_number'], 'MODELO: '.$row['model']],
                    ['PO: '.$row['po'], 'DESTINO: '.$row['destination']],
                ]);
                $height = 16 + count($lines) * self::LINE_HEIGHT + ($withFolios ? 52 : 0);
                if ($fields !== [] && $y + $height > self::FOOTER_Y) {
                    $pages[] = $fields;
                    $fields = [];
                    $y = $firstRowY;
                }

                $fields[] = $this->box(25, $y, 765, $height - 5, 2, $dimensions);
                array_push($fields, ...$this->columnFields($lines, $y + 7, $dimensions));
                if ($withFolios) {
                    $folioY = $y + $height - 37;
                    $fields[] = $this->field(36, $folioY, 145, 18, 'FOLIO INICIAL:', $dimensions);
                    $fields[] = $this->line(162, $folioY + 19, 230, 2, $dimensions);
                    $fields[] = $this->field(418, $folioY, 130, 18, 'FOLIO FINAL:', $dimensions);
                    $fields[] = $this->line(535, $folioY + 19, 240, 2, $dimensions);
                }
                $y += $height;
            }
            $pages[] = $fields;

            foreach ($pages as $index => $pageFields) {
                $pageNumber = ($index + 1).' de '.count($pages);
                $labels[] = implode("\n", [
                    ...$header,
                    $this->field(240, 71, 410, 28, "{$type}  |  {$pageNumber}", $dimensions),
                    ...$pageFields,
                    ...$footer,
                ]);
            }
        }

        return implode("\n", $labels);
    }

    /** @return array<int, array{0: ?string, 1: ?string}> */
    private function columnLines(array $pairs): array
    {
        $lines = [];
        foreach ($pairs as [$left, $right]) {
            // Each column has room for 20 full-width glyphs at 18 dots.
            $leftLines = $this->wrapText($left, 20);
            $rightLines = $this->wrapText($right, 20);
            array_push($lines, ...array_map(null, $leftLines, $rightLines));
        }

        return $lines;
    }

    private function columnFields(array $lines, int $y, array $dimensions): array
    {
        $fields = [];
        foreach ($lines as [$left, $right]) {
            if ($left !== null) {
                $fields[] = $this->field(36, $y, 365, 18, $left, $dimensions);
            }
            if ($right !== null) {
                $fields[] = $this->field(418, $y, 360, 18, $right, $dimensions);
            }
            $y += self::LINE_HEIGHT;
        }

        return $fields;
    }

    /**
     * @return array<int, array{type: string, part_number: string, job: string, model: string, quantity: string, po: string, destination: string}>
     */
    private function requestRows(LabelRequest $labelRequest): array
    {
        $rows = [];
        $lines = $labelRequest->requestedLabelLines();
        if (! $labelRequest->isLpk()) {
            // Older standard requests can have a selected type but no saved part number.
            $types = $labelRequest->requestedLabelTypes() ?: ['SIN DETALLE'];
            foreach (array_diff($types, array_column($lines, 'type')) as $type) {
                $lines[] = [
                    'type' => $type,
                    'part_number' => $labelRequest->label_part_number,
                    'quantity' => $labelRequest->quantity_requested,
                ];
            }
        }

        foreach ($lines as $line) {
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
            'type' => $labelRequest->isLpk() && ($line['type'] ?? '') === 'Shipping'
                ? 'SHIPPING LPK'
                : strtoupper((string) ($line['type'] ?? 'ETIQUETA')),
            'part_number' => $this->displayValue($line['part_number'] ?? null),
            'job' => $this->displayValue($item['job_number'] ?? $line['job_number'] ?? $labelRequest->job_number),
            'model' => $this->displayValue($item['model'] ?? $line['model'] ?? $labelRequest->model),
            'quantity' => $quantity ?: 'CANT: '.number_format((int) ($line['quantity'] ?? 0)),
            'po' => $this->displayValue($item['po_number'] ?? $line['po_number'] ?? $labelRequest->po_number),
            'destination' => $this->displayValue($item['destination'] ?? $line['destination'] ?? $labelRequest->destination),
        ];
    }

    private function displayValue(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : 'N/A';
    }
}
