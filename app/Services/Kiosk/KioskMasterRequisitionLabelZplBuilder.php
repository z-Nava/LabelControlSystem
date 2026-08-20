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
        $requestDate = $this->formatDate($masterRequest->getRawOriginal('request_date'), 'd/m/Y');
        $createdAt = $this->formatDate(
            $masterRequest->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now()->format('d/m/Y H:i'),
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
        $qrPayload = (string) ($masterRequest->job_packaging ?: $masterRequest->job_assembly ?: $masterRequest->id);

        return implode("\n", [
            '^XA',
            '^CI28',
            "^PW{$widthDots}",
            "^LL{$heightDots}",
            '^LH0,0',
            '^LS0',
            '^MMT',
            $this->box(12, 12, 775, 775, 3, $scale),
            $this->field(28, 25, 742, 31, 'REQUISICION MASTER', $scale, alignment: 'C'),
            $this->field(28, 65, 742, 52, $folio, $scale, alignment: 'C'),
            $this->line(28, 124, 742, 3, $scale),
            $this->field(38, 140, 720, 24, "FECHA: {$requestDate} | SEMANA: {$masterRequest->week}", $scale),
            $this->field(38, 176, 720, 26, "TIPO: {$requestType} | SOLICITUD: {$kind}", $scale, maxLines: 2),
            $this->field(38, 228, 720, 26, "MODELO: {$model}", $scale, maxLines: 2),
            $this->field(38, 280, 720, 25, "JOB ENSAMBLE: {$jobAssembly}", $scale),
            $this->field(38, 317, 720, 25, "JOB EMPAQUE: {$jobPackaging}", $scale),
            $this->field(38, 358, 720, 23, "LINEA: {$lineName}", $scale, maxLines: 2),
            $this->field(38, 405, 720, 22, 'LIDER: '.(string) $masterRequest->leader_name, $scale, maxLines: 2),
            $this->field(38, 450, 720, 22, 'SOLICITA: '.(string) $masterRequest->requested_by_name, $scale, maxLines: 2),
            $this->field(38, 496, 720, 23, "FOLIOS: {$normalFolios} | STD PACK: {$standardPack}", $scale, maxLines: 2),
            $this->field(38, 544, 720, 22, "PARCIAL: {$partial}", $scale, maxLines: 2),
            $this->field(38, 590, 530, 21, 'LOCAL: '.($masterRequest->local ?: 'N/A').' | SUBINV: '.($masterRequest->subinventory ?: 'N/A'), $scale, maxLines: 2),
            $this->field(38, 638, 530, 20, 'PO: '.($masterRequest->po_number ?: 'N/A').' | DESTINO: '.($masterRequest->destination ?: 'N/A'), $scale, maxLines: 2),
            $this->field(38, 687, 515, 18, "REGISTRADA: {$createdAt}", $scale),
            $this->field(38, 716, 95, 17, 'IMPRIMIO:', $scale),
            $this->line(130, 736, 145, 2, $scale),
            $this->field(300, 716, 90, 17, 'RECIBIO:', $scale),
            $this->line(390, 736, 160, 2, $scale),
            $this->field(38, 748, 70, 17, 'TURNO:', $scale),
            $this->field(108, 748, 165, 17, $shiftName, $scale),
            $this->qr(596, 606, $qrPayload, $scale),
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }
}
