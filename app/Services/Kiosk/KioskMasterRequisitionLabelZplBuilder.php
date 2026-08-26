<?php

namespace App\Services\Kiosk;

use App\Models\MasterModelMapping;
use App\Models\MasterRequest;
use Illuminate\Support\Carbon;

class KioskMasterRequisitionLabelZplBuilder extends AbstractKioskRequisitionLabelZplBuilder
{
    public function build(MasterRequest $masterRequest, int $dpi = self::BASE_DPI): string
    {
        $dimensions = $this->dimensions($dpi);
        $widthDots = $dimensions['width'];
        $heightDots = $dimensions['height'];
        $scale = $dimensions['scale'];

        $lineName = trim(implode(' ', array_filter([
            $masterRequest->line?->code,
            $masterRequest->line?->name,
        ]))) ?: 'SIN LINEA';
        $shiftName = trim(implode(' ', array_filter([
            $masterRequest->shift?->code,
            $masterRequest->shift?->name,
        ]))) ?: 'SIN TURNO';
        $requestType = MasterModelMapping::labelForType((string) $masterRequest->request_type);
        $displayTimezone = $this->displayTimezone();
        $createdAt = $this->formatDate(
            $masterRequest->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now($displayTimezone)->format('d/m/Y H:i'),
            $displayTimezone,
        );
        $folio = sprintf('#%06d', (int) $masterRequest->id);
        $jobAssembly = $masterRequest->job_assembly ?: 'N/A';
        $jobPackaging = $masterRequest->job_packaging ?: 'N/A';
        $model = $masterRequest->model ?: 'SIN MODELO';
        $normalFolios = sprintf('%s AL %s', $masterRequest->folios_from, $masterRequest->folios_to);
        $standardPack = $masterRequest->std_pack_qty
            ? number_format((int) $masterRequest->std_pack_qty)
            : 'N/A';
        $kind = match ($masterRequest->kind) {
            'reposition' => 'REPOSICION',
            'new' => 'NUEVO',
            default => strtoupper((string) ($masterRequest->kind ?: 'N/A')),
        };
        $partial = $masterRequest->partial_folio && $masterRequest->partial_qty
            ? sprintf('FOLIO %s / %s PZAS', $masterRequest->partial_folio, number_format((int) $masterRequest->partial_qty))
            : 'NO REQUERIDO';
        $jobQrValue = (string) ($masterRequest->job_packaging ?: $masterRequest->job_assembly ?: $masterRequest->id);
        $poQrValue = (string) ($masterRequest->po_number ?: 'N/A');
        $scanCards = [];

        if ($masterRequest->job_assembly) {
            $scanCards[] = [
                'label' => 'JOB ENSAMBLE',
                'value' => (string) $masterRequest->job_assembly,
                'magnification' => 3,
            ];
        }

        if ($masterRequest->job_packaging) {
            $scanCards[] = [
                'label' => 'JOB EMPAQUE',
                'value' => (string) $masterRequest->job_packaging,
                'magnification' => 3,
            ];
        }

        if ($scanCards === []) {
            $scanCards[] = [
                'label' => 'JOB',
                'value' => $jobQrValue,
                'magnification' => 3,
            ];
        }

        $scanCards[] = [
            'label' => 'PO',
            'value' => $poQrValue,
            'magnification' => 3,
        ];
        $scanCardFields = $this->scanCardFields($scanCards, $scale);

        return implode("\n", [
            '^XA',
            '^CI28',
            "^PW{$widthDots}",
            "^LL{$heightDots}",
            '^LH0,0',
            '^LS0',
            '^MMT',
            $this->box(12, 12, 775, 775, 3, $scale),
            $this->field(28, 23, 742, 29, 'REQUISICION MASTER', $scale, alignment: 'C'),
            $this->field(28, 56, 742, 43, $folio, $scale, alignment: 'C'),
            $this->field(28, 98, 742, 16, "REGISTRADA: {$createdAt}  |  SEMANA: {$masterRequest->week}  |  LINEA: {$lineName}", $scale, alignment: 'C'),
            $this->line(28, 119, 742, 3, $scale),

            // Datos de lectura rápida: cada valor cuenta con su propio QR.
            ...$scanCardFields,

            // Información general de la requisición.
            $this->box(24, 326, 750, 126, 2, $scale),
            $this->field(34, 334, 730, 17, 'DATOS DE REQUISICION', $scale, alignment: 'C'),
            $this->line(34, 357, 730, 2, $scale),
            $this->box(400, 357, 2, 87, 2, $scale),
            $this->field(38, 367, 350, 17, "TIPO: {$requestType}", $scale, maxLines: 2),
            $this->field(38, 405, 350, 17, "MODELO: {$model}", $scale, maxLines: 2),
            $this->field(414, 367, 346, 17, "SOLICITUD: {$kind}", $scale),
            $this->field(414, 394, 346, 16, "JOB ENSAMBLE: {$jobAssembly}", $scale),
            $this->field(414, 421, 346, 16, "JOB EMPAQUE: {$jobPackaging}", $scale),

            // Control de folios y cantidades.
            $this->box(24, 460, 750, 94, 2, $scale),
            $this->field(34, 468, 730, 17, 'CONTROL DE FOLIOS', $scale, alignment: 'C'),
            $this->line(34, 491, 730, 2, $scale),
            $this->field(38, 500, 445, 18, "FOLIOS: {$normalFolios}", $scale),
            $this->field(500, 500, 260, 18, "STD PACK: {$standardPack}", $scale),
            $this->field(38, 528, 722, 17, "PARCIAL: {$partial}", $scale),

            // Ubicación y entrega.
            $this->box(24, 562, 750, 91, 2, $scale),
            $this->field(34, 570, 730, 17, 'UBICACION Y ENTREGA', $scale, alignment: 'C'),
            $this->line(34, 593, 730, 2, $scale),
            $this->field(38, 602, 722, 17, 'LOCAL: '.($masterRequest->local ?: 'N/A').'  |  SUBINV: '.($masterRequest->subinventory ?: 'N/A'), $scale),
            $this->field(38, 628, 722, 17, 'DESTINO: '.($masterRequest->destination ?: 'N/A'), $scale, maxLines: 2),

            // Responsables y cierre de la requisición.
            $this->box(24, 661, 750, 66, 2, $scale),
            $this->field(38, 670, 722, 16, 'LIDER: '.(string) $masterRequest->leader_name, $scale, maxLines: 2),
            $this->field(38, 699, 722, 16, 'SOLICITA: '.(string) $masterRequest->requested_by_name, $scale, maxLines: 2),
            $this->field(38, 735, 722, 14, "TURNO: {$shiftName}", $scale, alignment: 'C'),
            $this->field(38, 758, 95, 15, 'IMPRIMIO:', $scale),
            $this->line(130, 777, 145, 2, $scale),
            $this->field(300, 758, 90, 15, 'RECIBIO:', $scale),
            $this->line(390, 777, 160, 2, $scale),
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }

    /**
     * @param  array<int, array{label: string, value: string, magnification: int}>  $cards
     * @return array<int, string>
     */
    private function scanCardFields(array $cards, float $scale): array
    {
        $cardCount = count($cards);
        $cardWidth = $cardCount === 2 ? 369 : 242;
        $cardGap = 12;
        $fields = [];

        foreach ($cards as $index => $card) {
            $x = 24 + ($index * ($cardWidth + $cardGap));
            $contentX = $x + 10;
            $contentWidth = $cardWidth - 20;
            $estimatedQrWidth = $card['magnification'] === 2 ? 90 : 110;
            $qrX = $x + (int) round(($cardWidth - $estimatedQrWidth) / 2);

            $fields[] = $this->box($x, 128, $cardWidth, 190, 2, $scale);
            $fields[] = $this->field(
                $contentX,
                137,
                $contentWidth,
                17,
                $card['label'],
                $scale,
                alignment: 'C',
            );
            $fields[] = $this->field(
                $contentX,
                161,
                $contentWidth,
                18,
                $card['value'],
                $scale,
                maxLines: 2,
                alignment: 'C',
            );
            $fields[] = $this->qr(
                $qrX,
                205,
                $card['value'],
                $scale,
                $card['magnification'],
            );
        }

        return $fields;
    }
}
