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
        $lineName = trim(implode(' ', array_filter([
            $dummyRequest->line?->code,
            $dummyRequest->line?->name,
        ]))) ?: 'SIN LINEA';
        $shiftName = trim(implode(' ', array_filter([
            $dummyRequest->shift?->code,
            $dummyRequest->shift?->name,
        ]))) ?: 'SIN TURNO';
        $displayTimezone = $this->displayTimezone();
        $createdAt = $this->formatDate(
            $dummyRequest->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now($displayTimezone)->format('d/m/Y H:i'),
            $displayTimezone,
        );
        $folio = sprintf('#%06d', (int) $dummyRequest->id);
        $rangeFrom = str_pad((string) $dummyRequest->range_from, 10, '0', STR_PAD_LEFT);
        $rangeTo = str_pad((string) $dummyRequest->range_to, 10, '0', STR_PAD_LEFT);
        $notes = Str::limit(trim((string) $dummyRequest->notes), 120, '...') ?: 'SIN NOTAS';
        $qrPayload = (string) ($dummyRequest->job_number ?: $dummyRequest->id);

        return implode("\n", [
            ...$this->startLabel($dimensions),
            $this->field(28, 27, 620, 31, 'REQUISICION DUMMY QR', $dimensions),
            $this->field(28, 67, 610, 37, $folio, $dimensions),
            $this->qr(675, 24, $qrPayload, $dimensions, 3),
            $this->field(28, 120, 750, 18, "REGISTRADA: {$createdAt}  |  SEMANA: {$dummyRequest->week}  |  LINEA: {$lineName}", $dimensions),
            $this->line(25, 150, 765, 2, $dimensions),

            $this->field(30, 175, 750, 21, 'TIPO:', $dimensions),
            $this->field(30, 209, 750, 27, $dummyRequest->requestTypeTitle(), $dimensions),
            $this->field(30, 270, 750, 21, 'JOB:', $dimensions),
            $this->field(30, 304, 750, 28, (string) $dummyRequest->job_number, $dimensions),
            $this->field(30, 365, 750, 21, 'FG:', $dimensions),
            $this->field(30, 399, 750, 27, (string) $dummyRequest->fg_code, $dimensions),
            $this->line(25, 460, 765, 2, $dimensions),

            $this->field(30, 486, 365, 22, 'CANTIDAD: '.number_format((int) $dummyRequest->quantity_requested), $dimensions),
            $this->field(405, 486, 375, 22, "TURNO: {$shiftName}", $dimensions),
            $this->field(30, 546, 750, 23, "CONSECUTIVOS: {$rangeFrom} AL {$rangeTo}", $dimensions),
            $this->field(30, 606, 365, 21, 'LIDER: '.($dummyRequest->leader_name ?: 'N/A'), $dimensions),
            $this->field(405, 606, 375, 21, 'SOLICITA: '.($dummyRequest->requested_by_name ?: 'N/A'), $dimensions),
            $this->field(30, 666, 750, 20, "NOTAS: {$notes}", $dimensions),
            ...$this->footer($dimensions),
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }
}
