<?php

namespace App\Services\Kiosk;

use App\Models\LabelRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class KioskRequisitionLabelZplBuilder extends AbstractKioskRequisitionLabelZplBuilder
{
    public function build(LabelRequest $labelRequest, int $dpi = self::BASE_DPI): string
    {
        $dimensions = $this->dimensions($dpi);
        $widthDots = $dimensions['width'];
        $heightDots = $dimensions['height'];
        $scale = $dimensions['scale'];

        $lineName = trim(implode(' ', array_filter([
            $labelRequest->line?->code,
            $labelRequest->line?->name,
        ]))) ?: 'SIN LINEA';
        $types = implode(' + ', $labelRequest->requestedLabelTypes()) ?: 'SIN TIPO';
        $serialDetails = $this->formatRequestItems($labelRequest->requestedSerialItems());
        $ratingDetails = $this->formatRequestItems($labelRequest->requestedRatingItems());
        $shippingItems = $labelRequest->requestedShippingItems();
        $innerDetails = $labelRequest->include_inner
            ? $this->formatRequestItems([[
                'part_number' => (string) $labelRequest->inner_part_number,
                'model' => filled($labelRequest->inner_model) ? (string) $labelRequest->inner_model : null,
            ]])
            : 'NO REQUERIDO';
        $shippingDetails = $this->formatRequestItems($shippingItems ?: [[
            'part_number' => (string) $labelRequest->shipping_part_number,
            'model' => filled($labelRequest->shipping_model) ? (string) $labelRequest->shipping_model : null,
        ]]);
        $shippingQuantity = $labelRequest->include_shipping
            ? (string) ($labelRequest->shipping_quantity ?? $labelRequest->quantity_requested)
            : 'NO REQUERIDO';
        $title = $labelRequest->isLpk()
            ? 'REQUISICION DE ETIQUETAS LPK'
            : 'REQUISICION DE ETIQUETAS';
        $hasGroupedLpkDetails = $labelRequest->hasGroupedLpkDetails();
        $modelLabel = $labelRequest->isLpk() ? 'ENSAMBLE FINAL' : 'MODELO';
        $shippingLine = 'SHIPPING: '.$shippingQuantity.'  |  PO: '.($labelRequest->po_number ?: 'N/A');
        $destinationLine = 'DESTINO: '.($labelRequest->destination ?: 'N/A');
        $displayTimezone = $this->displayTimezone();
        $createdAt = $this->formatDate(
            $labelRequest->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now($displayTimezone)->format('d/m/Y H:i'),
            $displayTimezone,
        );
        $folio = sprintf('#%06d', (int) $labelRequest->id);
        $qrPayload = $hasGroupedLpkDetails
            ? "LPK:{$labelRequest->id}"
            : (string) $labelRequest->job_number;

        $details = $hasGroupedLpkDetails
            ? $this->groupedLpkFields($labelRequest, $lineName, $createdAt, $qrPayload, $scale)
            : ($labelRequest->isLpk()
            ? [
                $this->field(38, 137, 720, 16, "REGISTRADA: {$createdAt} | SEMANA: {$labelRequest->week} | LINEA: {$lineName}", $scale, alignment: 'C'),
                $this->field(38, 168, 720, 32, 'JOB: '.(string) $labelRequest->job_number, $scale),
                $this->field(38, 208, 720, 22, $modelLabel.': '.($labelRequest->model ?: 'N/A'), $scale, maxLines: 2),
                $this->field(38, 259, 720, 17, 'LIDER: '.(string) $labelRequest->leader_name, $scale, maxLines: 2),
                $this->field(38, 296, 720, 17, 'SOLICITA: '.(string) $labelRequest->requested_by_name, $scale, maxLines: 2),
                $this->field(38, 333, 720, 19, 'CANTIDAD: '.number_format((int) $labelRequest->quantity_requested).'  |  TIPOS: '.$types.' | INNER: '.$innerDetails, $scale, maxLines: 2),
                $this->field(38, 374, 720, 16, 'SERIAL NP / MODELO: '.$serialDetails, $scale, maxLines: 2),
                $this->field(38, 410, 720, 16, 'RATING NP / MODELO: '.$ratingDetails, $scale, maxLines: 2),
                $this->field(
                    38,
                    446,
                    720,
                    16,
                    sprintf(
                        'SHIPPING: %s | %d NP',
                        $shippingQuantity,
                        count($shippingItems),
                    ),
                    $scale,
                ),
                ...$this->lpkShippingItemFields($shippingItems, $scale),
                $this->field(
                    38,
                    653,
                    590,
                    17,
                    'PO: '.($labelRequest->po_number ?: 'N/A').' | DESTINO: '.($labelRequest->destination ?: 'N/A'),
                    $scale,
                    maxLines: 2,
                ),
                $this->field(38, 714, 95, 17, 'IMPRIMIO:', $scale),
                $this->line(130, 734, 145, 2, $scale),
                $this->field(300, 714, 90, 17, 'RECIBIO:', $scale),
                $this->line(390, 734, 160, 2, $scale),
                $this->field(38, 740, 130, 17, 'FOLIO INICIAL:', $scale),
                $this->line(165, 760, 110, 2, $scale),
                $this->field(300, 740, 125, 17, 'FOLIO FINAL:', $scale),
                $this->line(425, 760, 125, 2, $scale),
                $this->field(38, 763, 70, 17, 'TURNO:', $scale),
                $this->line(105, 783, 170, 2, $scale),
                $this->qr(650, 650, $qrPayload, $scale),
            ]
            : [
                $this->field(38, 140, 720, 16, "REGISTRADA: {$createdAt} | SEMANA: {$labelRequest->week} | LINEA: {$lineName}", $scale, alignment: 'C'),
                $this->field(38, 176, 720, 36, 'JOB: '.(string) $labelRequest->job_number, $scale),
                $this->field(38, 220, 720, 27, $modelLabel.': '.($labelRequest->model ?: 'N/A'), $scale, maxLines: 2),
                $this->field(38, 286, 720, 23, 'LIDER: '.(string) $labelRequest->leader_name, $scale, maxLines: 2),
                $this->field(38, 332, 720, 23, 'SOLICITA: '.(string) $labelRequest->requested_by_name, $scale, maxLines: 2),
                $this->field(38, 378, 720, 25, 'CANTIDAD: '.number_format((int) $labelRequest->quantity_requested).'  |  TIPOS: '.$types, $scale, maxLines: 2),
                $this->field(38, 423, 720, 18, 'SERIAL NP / MODELO: '.$serialDetails, $scale, maxLines: 2),
                $this->field(38, 466, 720, 18, 'RATING NP / MODELO: '.$ratingDetails, $scale, maxLines: 2),
                $this->field(38, 509, 720, 18, 'INNER NP / MODELO: '.$innerDetails, $scale),
                $this->field(38, 542, 720, 18, $shippingLine.' | NP / MODELO: '.$shippingDetails, $scale, maxLines: 2),
                $this->field(38, 595, 530, 18, $destinationLine, $scale),
                $this->field(38, 710, 95, 17, 'IMPRIMIO:', $scale),
                $this->line(130, 730, 145, 2, $scale),
                $this->field(300, 710, 90, 17, 'RECIBIO:', $scale),
                $this->line(390, 730, 160, 2, $scale),
                $this->field(38, 738, 130, 17, 'FOLIO INICIAL:', $scale),
                $this->line(165, 758, 110, 2, $scale),
                $this->field(300, 738, 125, 17, 'FOLIO FINAL:', $scale),
                $this->line(425, 758, 125, 2, $scale),
                $this->field(38, 763, 70, 17, 'TURNO:', $scale),
                $this->line(105, 783, 170, 2, $scale),
                $this->qr(596, 606, $qrPayload, $scale),
            ]);

        return implode("\n", [
            '^XA',
            '^CI28',
            "^PW{$widthDots}",
            "^LL{$heightDots}",
            '^LH0,0',
            '^LS0',
            '^MMT',
            $this->box(12, 12, 775, 775, 3, $scale),
            $this->field(28, 25, 742, $labelRequest->isLpk() ? 34 : 30, $title, $scale, alignment: 'C'),
            $this->field(28, 65, 742, 52, $folio, $scale, alignment: 'C'),
            $this->line(28, 124, 742, 3, $scale),
            ...$details,
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function groupedLpkFields(
        LabelRequest $labelRequest,
        string $lineName,
        string $createdAt,
        string $qrPayload,
        float $scale,
    ): array {
        $summaryLines = $labelRequest->lpkLabelGroups
            ->map(function ($group): string {
                $jobs = $group->items->pluck('job_number')->unique()->implode(',');

                return sprintf(
                    '%s NP %s | %d REN. | JOB %s | MAX %s',
                    strtoupper($group->type_label),
                    $group->part_number,
                    $group->items->count(),
                    $jobs,
                    number_format((int) $group->items->max('quantity')),
                );
            })
            ->concat($labelRequest->lpkShippingGroups->map(function ($group): string {
                return sprintf(
                    'SHIPPING NP %s | %s ETIQ. | %d REN. | PO %s',
                    $group->part_number,
                    number_format((int) $group->quantity),
                    $group->items->count(),
                    $group->po_number ?: 'N/A',
                );
            }))
            ->values();

        $visibleLines = $summaryLines->take(9);

        if ($summaryLines->count() > 9) {
            $visibleLines = $summaryLines->take(8)->push(
                sprintf('+%d GRUPOS ADICIONALES EN HOJA', $summaryLines->count() - 8),
            );
        }

        [$groupFontSize, $groupStep, $groupMaxLines, $groupTextLimit] = match (true) {
            $visibleLines->count() <= 4 => [24, 58, 2, 50],
            $visibleLines->count() <= 6 => [21, 45, 2, 58],
            default => [18, 32, 1, 68],
        };

        $groupFields = $visibleLines
            ->map(fn (string $line, int $index): string => $this->field(
                42,
                316 + ($index * $groupStep),
                710,
                $groupFontSize,
                sprintf('%d. %s', $index + 1, Str::limit($line, $groupTextLimit, '...')),
                $scale,
                maxLines: $groupMaxLines,
            ))
            ->all();

        return [
            $this->field(38, 137, 720, 18, "REGISTRADA: {$createdAt} | SEMANA: {$labelRequest->week} | LINEA: {$lineName}", $scale, maxLines: 2, alignment: 'C'),
            $this->field(38, 183, 720, 22, 'LIDER: '.(string) $labelRequest->leader_name, $scale, maxLines: 1),
            $this->field(38, 222, 720, 22, 'SOLICITA: '.(string) $labelRequest->requested_by_name, $scale, maxLines: 1),
            $this->field(
                38,
                260,
                720,
                20,
                sprintf(
                    'RESERVA JOBS: %s | GRUPOS PROD: %d | SHIPPING: %d',
                    number_format((int) $labelRequest->quantity_requested),
                    $labelRequest->lpkLabelGroups->count(),
                    $labelRequest->lpkShippingGroups->count(),
                ),
                $scale,
                maxLines: 2,
            ),
            ...$groupFields,
            $this->field(38, 610, 590, 17, 'SHIPPING: JOBS/MODELOS INFORMATIVOS; NO RESERVA CANTIDAD', $scale),
            $this->field(38, 676, 105, 19, 'IMPRIMIO:', $scale),
            $this->line(130, 696, 145, 2, $scale),
            $this->field(300, 676, 100, 19, 'RECIBIO:', $scale),
            $this->line(390, 696, 160, 2, $scale),
            $this->field(38, 710, 145, 19, 'FOLIO INICIAL:', $scale),
            $this->line(165, 730, 110, 2, $scale),
            $this->field(300, 710, 140, 19, 'FOLIO FINAL:', $scale),
            $this->line(425, 730, 125, 2, $scale),
            $this->field(38, 749, 80, 19, 'TURNO:', $scale),
            $this->line(105, 769, 170, 2, $scale),
            $this->qr(650, 650, $qrPayload, $scale),
        ];
    }

    /**
     * @param  array<int, array{part_number: string, model: ?string}>  $shippingItems
     * @return array<int, string>
     */
    private function lpkShippingItemFields(array $shippingItems, float $scale): array
    {
        if ($shippingItems === []) {
            return [$this->field(48, 507, 700, 15, 'NO REQUERIDOS', $scale)];
        }

        $visibleItems = array_slice($shippingItems, 0, 8);

        if (count($shippingItems) > 8) {
            $visibleItems = array_slice($shippingItems, 0, 7);
            $visibleItems[] = [
                'part_number' => sprintf('+%d ITEMS ADICIONALES', count($shippingItems) - 7),
                'model' => null,
            ];
        }

        return array_map(
            fn (array $item, int $index): string => $this->field(
                48,
                507 + ($index * 18),
                700,
                15,
                sprintf('%d. %s', $index + 1, Str::limit($this->formatRequestItem($item), 40, '...')),
                $scale,
            ),
            $visibleItems,
            array_keys($visibleItems),
        );
    }

    /**
     * @param  array<int, array{part_number: string, model: ?string}>  $items
     */
    private function formatRequestItems(array $items): string
    {
        $formatted = array_values(array_filter(array_map(
            fn (array $item): string => $this->formatRequestItem($item),
            $items,
        )));

        return $formatted !== [] ? implode(', ', $formatted) : 'NO REQUERIDO';
    }

    /**
     * @param  array{part_number: string, model: ?string}  $item
     */
    private function formatRequestItem(array $item): string
    {
        $partNumber = trim((string) ($item['part_number'] ?? ''));
        $model = trim((string) ($item['model'] ?? ''));

        if ($partNumber === '') {
            return '';
        }

        return $model !== '' ? "{$partNumber} ({$model})" : $partNumber;
    }
}
