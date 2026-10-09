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
        $displayTimezone = $this->displayTimezone();
        $createdAt = $this->formatDate(
            $masterRequest->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now($displayTimezone)->format('d/m/Y H:i'),
            $displayTimezone,
        );
        $lineName = trim(implode(' ', array_filter([
            $masterRequest->line?->code,
            $masterRequest->line?->name,
        ]))) ?: 'SIN LINEA';
        $kind = match ($masterRequest->kind) {
            'reposition' => 'REPOSICION',
            'new' => 'NUEVO',
            default => strtoupper((string) ($masterRequest->kind ?: 'N/A')),
        };
        $partial = $masterRequest->partial_folio && $masterRequest->partial_qty
            ? sprintf('%s / %s PZAS', $masterRequest->partial_folio, number_format((int) $masterRequest->partial_qty))
            : 'NO REQUERIDO';
        $jobAssembly = (string) ($masterRequest->job_assembly ?: 'N/A');
        $jobPackaging = (string) ($masterRequest->job_packaging ?: 'N/A');
        $qrPayload = (string) ($masterRequest->job_packaging ?: $masterRequest->job_assembly ?: $masterRequest->id);
        $folio = sprintf('#%06d', (int) $masterRequest->id);

        return implode("\n", [
            ...$this->startLabel($dimensions),
            $this->field(28, 27, 620, 31, 'REQUISICION MASTER', $dimensions),
            $this->field(28, 67, 610, 37, $folio, $dimensions),
            $this->qr(675, 24, $qrPayload, $dimensions, 3),
            $this->field(28, 120, 750, 18, "REGISTRADA: {$createdAt}  |  SEMANA: {$masterRequest->week}  |  LINEA: {$lineName}", $dimensions),
            $this->line(25, 150, 765, 2, $dimensions),

            $this->field(30, 172, 750, 21, 'JOB ENSAMBLE:', $dimensions),
            $this->field(30, 203, 750, 28, $jobAssembly, $dimensions),
            $this->field(30, 255, 750, 21, 'JOB EMPAQUE:', $dimensions),
            $this->field(30, 286, 750, 28, $jobPackaging, $dimensions),
            $this->field(30, 338, 750, 21, 'PO:', $dimensions),
            $this->field(30, 369, 750, 27, (string) ($masterRequest->po_number ?: 'N/A'), $dimensions),
            $this->field(30, 421, 750, 21, 'DESTINO:', $dimensions),
            $this->field(30, 452, 750, 26, (string) ($masterRequest->destination ?: 'N/A'), $dimensions),
            $this->line(25, 498, 765, 2, $dimensions),

            $this->field(30, 523, 750, 21, 'TIPO: '.MasterModelMapping::labelForType((string) $masterRequest->request_type), $dimensions),
            $this->field(30, 573, 750, 21, 'MODELO: '.($masterRequest->model ?: 'N/A'), $dimensions),
            $this->field(30, 623, 360, 21, "SOLICITUD: {$kind}", $dimensions),
            $this->field(405, 623, 375, 21, "FOLIOS: {$masterRequest->folios_from} AL {$masterRequest->folios_to}", $dimensions),
            $this->field(30, 673, 360, 21, 'STD PACK: '.number_format((int) $masterRequest->std_pack_qty), $dimensions),
            $this->field(405, 673, 375, 21, "PARCIAL: {$partial}", $dimensions),
            $this->field(30, 723, 360, 21, 'LOCAL: '.($masterRequest->local ?: 'N/A'), $dimensions),
            $this->field(405, 723, 375, 21, 'SUBINV: '.($masterRequest->subinventory ?: 'N/A'), $dimensions),
            $this->field(30, 773, 360, 21, 'LIDER: '.($masterRequest->leader_name ?: 'N/A'), $dimensions),
            $this->field(405, 773, 375, 21, 'SOLICITA: '.($masterRequest->requested_by_name ?: 'N/A'), $dimensions),
            ...$this->footer($dimensions),
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }
}
