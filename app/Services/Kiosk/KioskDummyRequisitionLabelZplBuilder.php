<?php

namespace App\Services\Kiosk;

use App\Models\DummyRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class KioskDummyRequisitionLabelZplBuilder extends AbstractKioskRequisitionLabelZplBuilder
{
    public function build(DummyRequest $dummyRequest, int $dpi = self::BASE_DPI): string
    {
        $dimensions = $this->dimensions($dpi);
        $widthDots = $dimensions['width'];
        $heightDots = $dimensions['height'];
        $scale = $dimensions['scale'];

        $lineName = trim(implode(' ', array_filter([
            $dummyRequest->line?->code,
            $dummyRequest->line?->name,
        ]))) ?: 'SIN LINEA';
        $shiftName = trim(implode(' ', array_filter([
            $dummyRequest->shift?->code,
            $dummyRequest->shift?->name,
        ]))) ?: 'SIN TURNO';
        $requestDate = $this->formatDate($dummyRequest->getRawOriginal('request_date'), 'd/m/Y');
        $createdAt = $this->formatDate(
            $dummyRequest->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now()->format('d/m/Y H:i'),
        );
        $folio = sprintf('#%06d', (int) $dummyRequest->id);
        $rangeFrom = str_pad((string) $dummyRequest->range_from, 10, '0', STR_PAD_LEFT);
        $rangeTo = str_pad((string) $dummyRequest->range_to, 10, '0', STR_PAD_LEFT);
        $notes = Str::limit(trim((string) $dummyRequest->notes), 120, '...') ?: 'SIN NOTAS';
        $qrPayload = (string) ($dummyRequest->job_number ?: $dummyRequest->id);

        return implode("\n", [
            '^XA',
            '^CI28',
            "^PW{$widthDots}",
            "^LL{$heightDots}",
            '^LH0,0',
            '^LS0',
            '^MMT',
            $this->box(12, 12, 775, 775, 3, $scale),
            $this->field(28, 25, 742, 31, 'REQUISICION DUMMY QR', $scale, alignment: 'C'),
            $this->field(28, 65, 742, 52, $folio, $scale, alignment: 'C'),
            $this->line(28, 124, 742, 3, $scale),
            $this->field(38, 140, 720, 24, "FECHA: {$requestDate} | SEMANA: {$dummyRequest->week}", $scale),
            $this->field(38, 178, 720, 27, 'TIPO: '.$dummyRequest->requestTypeTitle(), $scale),
            $this->field(38, 220, 720, 34, 'JOB: '.(string) $dummyRequest->job_number, $scale),
            $this->field(38, 270, 720, 27, 'FG: '.(string) $dummyRequest->fg_code, $scale, maxLines: 2),
            $this->field(38, 322, 720, 25, 'CANTIDAD: '.number_format((int) $dummyRequest->quantity_requested), $scale),
            $this->field(38, 364, 720, 23, "CONSECUTIVOS: {$rangeFrom} AL {$rangeTo}", $scale, maxLines: 2),
            $this->field(38, 414, 720, 23, "LINEA: {$lineName}", $scale, maxLines: 2),
            $this->field(38, 462, 720, 22, "TURNO: {$shiftName}", $scale, maxLines: 2),
            $this->field(38, 508, 720, 22, 'LIDER: '.(string) $dummyRequest->leader_name, $scale, maxLines: 2),
            $this->field(38, 554, 720, 22, 'SOLICITA: '.(string) $dummyRequest->requested_by_name, $scale, maxLines: 2),
            $this->field(38, 602, 530, 20, "NOTAS: {$notes}", $scale, maxLines: 3),
            $this->field(38, 681, 515, 18, "REGISTRADA: {$createdAt}", $scale),
            $this->field(38, 710, 95, 17, 'IMPRIMIO:', $scale),
            $this->line(130, 730, 145, 2, $scale),
            $this->field(300, 710, 90, 17, 'RECIBIO:', $scale),
            $this->line(390, 730, 160, 2, $scale),
            $this->field(38, 742, 130, 17, 'CANT. ENTREGADA:', $scale),
            $this->line(165, 762, 110, 2, $scale),
            $this->field(300, 742, 125, 17, 'HORA ENTREGA:', $scale),
            $this->line(425, 762, 125, 2, $scale),
            $this->qr(596, 606, $qrPayload, $scale),
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }
}
