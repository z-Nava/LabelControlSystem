<?php

namespace App\Services\Kiosk;

use App\Models\MasterActionRequest;
use App\Models\MasterModelMapping;
use App\Models\MasterRequest;
use Illuminate\Support\Carbon;

class KioskMasterRequisitionLabelZplBuilder extends AbstractKioskRequisitionLabelZplBuilder
{
    public function buildAction(MasterActionRequest $action, int $dpi = self::BASE_DPI): string
    {
        $dimensions = $this->dimensions($dpi);
        $source = $action->masterRequest;
        $createdAt = $this->formatDate(
            $action->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now($this->displayTimezone())->format('d/m/Y H:i'),
            $this->displayTimezone(),
        );
        $requestedFolios = $action->selectedFolios->pluck('folio_number')->map(
            fn (mixed $number): string => 'F'.(int) $number
        );
        $folioLines = $requestedFolios->take(20)->chunk(5)->values();
        $remainingFolios = max(0, $requestedFolios->count() - 20);
        $reason = preg_replace('/\s+/u', ' ', trim((string) $action->reason)) ?? '';
        $shortReason = mb_substr($reason, 0, 216).(mb_strlen($reason) > 216 ? '...' : '');
        $reasonLines = collect(range(0, 3))->map(
            fn (int $index): string => mb_substr($shortReason, $index * 55, 55)
        );
        $isReprint = $action->action_type === MasterActionRequest::TYPE_REPRINT;
        $shift = $action->requestedBy?->shift?->code ?: 'SIN TURNO';
        $jobAssembly = (string) ($source?->job_assembly ?: 'N/A');
        $jobPackaging = (string) ($source?->job_packaging ?: 'N/A');

        return implode("\n", [
            ...$this->startLabel($dimensions),
            $this->field(28, 27, 620, 29, $isReprint ? 'SOLICITUD REIMPRESION MASTER' : 'SOLICITUD RETRABAJO MASTER', $dimensions),
            $this->field(28, 67, 610, 37, sprintf('#%06d', (int) $action->id), $dimensions),
            $this->qr(675, 24, $jobPackaging !== 'N/A' ? $jobPackaging : $jobAssembly, $dimensions, 3),
            $this->field(28, 120, 750, 18, "REGISTRADA: {$createdAt}  |  TURNO: {$shift}  |  LINEA: ".($source?->line?->code ?: 'N/A'), $dimensions),
            $this->line(25, 150, 765, 2, $dimensions),
            $this->field(30, 175, 750, 22, 'REQUISICION ORIGEN: #'.($source?->id ?: 'N/A'), $dimensions),
            $this->field(30, 225, 750, 19, 'JOB ENSAMBLE:', $dimensions),
            $this->field(30, 255, 750, 26, $jobAssembly, $dimensions),
            $this->field(30, 305, 750, 19, 'JOB EMPAQUE:', $dimensions),
            $this->field(30, 335, 750, 26, $jobPackaging, $dimensions),
            $this->field(30, 385, 750, 19, 'TIPO: '.MasterModelMapping::labelForType((string) $source?->request_type), $dimensions),
            $this->field(30, 430, 750, 19, 'MODELO: '.($source?->model ?: 'N/A'), $dimensions),
            $this->line(25, 480, 765, 2, $dimensions),
            $this->field(30, 505, 750, 21, "FOLIOS ORIGINALES ({$requestedFolios->count()}):", $dimensions),
            ...$folioLines->map(fn ($line, int $index): string => $this->field(
                30, 545 + $index * 42, 750, 24, $line->join('   '), $dimensions,
            ))->all(),
            ...($requestedFolios->isEmpty() ? [$this->field(30, 545, 750, 24, 'NINGUNO; SOLO FOLIOS ADICIONALES', $dimensions)] : []),
            $this->field(30, 725, 750, 18, $remainingFolios > 0 ? "+{$remainingFolios} FOLIOS MAS; VER SOLICITUD #{$action->id}" : '', $dimensions),
            $this->field(30, 775, 750, 22, $isReprint
                ? 'COPIAS POR FOLIO: '.number_format((int) $action->copies)
                : 'FOLIOS ADICIONALES: '.number_format((int) $action->additional_folios_count), $dimensions),
            $this->line(25, 825, 765, 2, $dimensions),
            $this->field(30, 845, 750, 20, 'MOTIVO:', $dimensions),
            ...$reasonLines->map(fn (string $line, int $index): string => $this->field(
                30, 880 + $index * 43, 750, 18, $line, $dimensions,
            ))->all(),
            $this->field(30, 1070, 750, 18, 'SOLICITO: '.($action->requested_by_name ?: 'N/A'), $dimensions),
            $this->field(30, 1105, 750, 18, 'ENVIADA A LABEL ROOM PARA REVISION', $dimensions),
            $this->line(25, 1150, 765, 2, $dimensions),
            $this->field(30, 1164, 95, 17, 'RECIBIO:', $dimensions),
            $this->line(125, 1189, 270, 2, $dimensions),
            $this->field(410, 1164, 365, 17, "TURNO: {$shift}", $dimensions),
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }

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
        $shift = $masterRequest->shift?->code
            ?: $masterRequest->requestedBy?->shift?->code
            ?: 'SIN TURNO';

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
            $this->line(25, 1150, 765, 2, $dimensions),
            $this->field(30, 1164, 95, 17, 'RECIBIO:', $dimensions),
            $this->line(125, 1189, 270, 2, $dimensions),
            $this->field(410, 1164, 365, 17, "TURNO: {$shift}", $dimensions),
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }
}
