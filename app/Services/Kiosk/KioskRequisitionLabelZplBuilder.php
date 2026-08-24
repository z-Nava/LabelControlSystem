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
        $serialPartNumbers = implode(', ', $labelRequest->requestedSerialPartNumbers()) ?: 'NO REQUERIDO';
        $ratingPartNumbers = implode(', ', $labelRequest->requestedRatingPartNumbers()) ?: 'NO REQUERIDO';
        $shippingItemReferences = $labelRequest->requestedShippingItemReferences();
        $shippingQuantity = $labelRequest->include_shipping
            ? (string) ($labelRequest->shipping_quantity ?? $labelRequest->quantity_requested)
            : 'NO REQUERIDO';
        $title = $labelRequest->isLpk()
            ? 'REQUISICION DE ETIQUETAS LPK'
            : 'REQUISICION DE ETIQUETAS';
        $modelLabel = $labelRequest->isLpk() ? 'ENSAMBLE FINAL' : 'MODELO';
        $shippingLine = 'SHIPPING: '.$shippingQuantity.'  |  PO: '.($labelRequest->po_number ?: 'N/A');
        $destinationLine = 'DESTINO: '.($labelRequest->destination ?: 'N/A');
        $requestDate = $this->formatDate($labelRequest->getRawOriginal('request_date'), 'd/m/Y');
        $createdAt = $this->formatDate(
            $labelRequest->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now()->format('d/m/Y H:i'),
        );
        $folio = sprintf('#%06d', (int) $labelRequest->id);
        $qrPayload = (string) $labelRequest->job_number;

        $details = $labelRequest->isLpk()
            ? [
                $this->field(38, 137, 720, 22, "FECHA: {$requestDate}  |  SEMANA: {$labelRequest->week}", $scale),
                $this->field(38, 168, 720, 32, 'JOB: '.(string) $labelRequest->job_number, $scale),
                $this->field(38, 208, 720, 22, $modelLabel.': '.(string) $labelRequest->model, $scale, maxLines: 2),
                $this->field(38, 259, 720, 18, "LINEA: {$lineName}", $scale, maxLines: 2),
                $this->field(38, 300, 720, 17, 'LIDER: '.(string) $labelRequest->leader_name, $scale, maxLines: 2),
                $this->field(38, 337, 720, 17, 'SOLICITA: '.(string) $labelRequest->requested_by_name, $scale, maxLines: 2),
                $this->field(38, 374, 720, 19, 'CANTIDAD: '.number_format((int) $labelRequest->quantity_requested).'  |  TIPOS: '.$types, $scale, maxLines: 2),
                $this->field(38, 415, 720, 16, 'NP SERIAL: '.$serialPartNumbers, $scale, maxLines: 2),
                $this->field(38, 451, 720, 16, 'NP RATING: '.$ratingPartNumbers, $scale, maxLines: 2),
                $this->field(
                    38,
                    487,
                    720,
                    16,
                    sprintf(
                        'SHIPPING: %s | %d MODELOS / HERRAMIENTAS',
                        $shippingQuantity,
                        count($shippingItemReferences),
                    ),
                    $scale,
                ),
                ...$this->lpkShippingItemFields($shippingItemReferences, $scale),
                $this->field(
                    38,
                    653,
                    590,
                    17,
                    'PO: '.($labelRequest->po_number ?: 'N/A').' | DESTINO: '.($labelRequest->destination ?: 'N/A'),
                    $scale,
                    maxLines: 2,
                ),
                $this->field(38, 692, 575, 15, "REGISTRADA: {$createdAt}", $scale),
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
                $this->field(38, 140, 720, 25, "FECHA: {$requestDate}  |  SEMANA: {$labelRequest->week}", $scale),
                $this->field(38, 176, 720, 36, 'JOB: '.(string) $labelRequest->job_number, $scale),
                $this->field(38, 220, 720, 27, $modelLabel.': '.(string) $labelRequest->model, $scale, maxLines: 2),
                $this->field(38, 276, 720, 24, "LINEA: {$lineName}", $scale, maxLines: 2),
                $this->field(38, 326, 720, 23, 'LIDER: '.(string) $labelRequest->leader_name, $scale, maxLines: 2),
                $this->field(38, 374, 720, 23, 'SOLICITA: '.(string) $labelRequest->requested_by_name, $scale, maxLines: 2),
                $this->field(38, 422, 720, 25, 'CANTIDAD: '.number_format((int) $labelRequest->quantity_requested).'  |  TIPOS: '.$types, $scale, maxLines: 2),
                $this->field(38, 475, 720, 22, 'NP SERIAL: '.$serialPartNumbers, $scale, maxLines: 2),
                $this->field(38, 525, 720, 22, 'NP RATING: '.$ratingPartNumbers, $scale, maxLines: 2),
                $this->field(38, 575, 530, 21, $shippingLine, $scale, maxLines: 2),
                $this->field(38, 625, 530, 21, $destinationLine, $scale, maxLines: 2),
                $this->field(38, 681, 515, 18, "REGISTRADA: {$createdAt}", $scale),
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
            ];

        return implode("\n", [
            '^XA',
            '^CI28',
            "^PW{$widthDots}",
            "^LL{$heightDots}",
            '^LH0,0',
            '^LS0',
            '^MMT',
            $this->box(12, 12, 775, 775, 3, $scale),
            $this->field(28, 25, 742, $labelRequest->isLpk() ? 27 : 30, $title, $scale, alignment: 'C'),
            $this->field(28, 65, 742, 52, $folio, $scale, alignment: 'C'),
            $this->line(28, 124, 742, 3, $scale),
            ...$details,
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }

    /**
     * @param  array<int, string>  $shippingItems
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
            $visibleItems[] = sprintf('+%d ITEMS ADICIONALES', count($shippingItems) - 7);
        }

        return array_map(
            fn (string $item, int $index): string => $this->field(
                48,
                507 + ($index * 18),
                700,
                15,
                sprintf('%d. %s', $index + 1, Str::limit($item, 40, '...')),
                $scale,
            ),
            $visibleItems,
            array_keys($visibleItems),
        );
    }
}
