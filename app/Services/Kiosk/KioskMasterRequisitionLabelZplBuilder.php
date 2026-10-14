<?php

namespace App\Services\Kiosk;

use App\Models\MasterActionRequest;
use App\Models\MasterModelMapping;
use App\Models\MasterRequest;
use App\Models\ProductionLine;
use Illuminate\Support\Carbon;

class KioskMasterRequisitionLabelZplBuilder extends AbstractKioskRequisitionLabelZplBuilder
{
    public const JOB_QR_MARKER = '^FX KIOSK_JOB_QRS_V2^FS';

    public function buildAction(MasterActionRequest $action, int $dpi = self::BASE_DPI): string
    {
        $action->loadMissing([
            'masterRequest.line',
            'masterRequest.folios',
            'selectedFolios',
            'requestedBy.shift',
        ]);
        $dimensions = $this->dimensions($dpi);
        $source = $action->masterRequest;
        $createdAt = $this->formatDate(
            $action->getRawOriginal('created_at'),
            'd/m/Y H:i',
            Carbon::now($this->displayTimezone())->format('d/m/Y H:i'),
            $this->displayTimezone(),
        );
        $isReprint = $action->action_type === MasterActionRequest::TYPE_REPRINT;
        $proposal = $action->proposed_rework;
        $jobAssembly = $proposal !== null ? ($proposal['job_assembly'] ?? null) : $source?->job_assembly;
        $jobPackaging = $proposal !== null ? ($proposal['job_packaging'] ?? null) : $source?->job_packaging;
        $shift = $action->requestedBy?->shift?->code ?: 'SIN TURNO';
        $rows = $this->actionRows($action);
        $pages = $this->actionPages($rows);

        return implode("\n", array_map(
            fn (array $page, int $index): string => implode("\n", [
                ...$this->startLabel($dimensions),
                $this->field(28, 27, 510, 20, $isReprint ? 'COMPROBANTE REIMPRESION MASTER' : 'COMPROBANTE RETRABAJO MASTER', $dimensions),
                $this->field(28, 67, 510, 23, sprintf('SOL. #%06d | PAGINA %d/%d', (int) $action->id, $index + 1, count($pages)), $dimensions),
                ...$this->jobQrFields($jobAssembly, $jobPackaging, $dimensions),
                $this->field(28, 122, 515, 17, "ORIGEN #{$action->master_request_id} | REG: {$createdAt}", $dimensions),
                $this->field(28, 157, 750, 17, 'PENDIENTE DE VALIDACION EN LABEL ROOM', $dimensions),
                $this->line(25, 192, 765, 2, $dimensions),
                ...array_map(
                    fn (array $row, int $rowIndex): string => $this->field(
                        30,
                        213 + $rowIndex * 28,
                        750,
                        $row['heading'] ? 20 : 18,
                        $row['text'],
                        $dimensions,
                    ),
                    $page,
                    array_keys($page),
                ),
                $this->line(25, 1080, 765, 2, $dimensions),
                $this->field(30, 1097, 750, 18, $index + 1 < count($pages)
                    ? 'CONTINUA EN LA SIGUIENTE ETIQUETA'
                    : 'FIN DEL COMPROBANTE', $dimensions),
                $this->line(25, 1150, 765, 2, $dimensions),
                $this->field(30, 1164, 95, 17, 'RECIBIO:', $dimensions),
                $this->line(125, 1189, 270, 2, $dimensions),
                $this->field(410, 1164, 365, 17, "TURNO: {$shift}", $dimensions),
                '^PQ1,0,1,N',
                '^XZ',
            ]), $pages, array_keys($pages),
        ));
    }

    /** @return array<int, array{text: string, heading: bool}> */
    private function actionRows(MasterActionRequest $action): array
    {
        $source = $action->masterRequest;
        $proposal = $action->proposed_rework;
        $originalModel = $source?->model ?: $this->printedModel($source);
        $rows = [];
        $this->addActionRow($rows, 'DATOS DE LA REQUISICION ORIGINAL', true);
        $this->addActionRow($rows, 'JOB ENSAMBLE: '.$this->displayValue($source?->job_assembly));
        $this->addActionRow($rows, 'JOB EMPAQUE: '.$this->displayValue($source?->job_packaging));
        $this->addActionRow($rows, 'TIPO: '.MasterModelMapping::labelForType((string) $source?->request_type));
        $this->addActionRow($rows, 'MODELO: '.$this->displayValue($originalModel));
        $this->addActionRow($rows, 'LINEA: '.$this->displayValue($source?->line?->code));
        $this->addActionRow($rows, 'LOCAL: '.$this->displayValue($source?->local));
        $this->addActionRow($rows, 'SUBINVENTORY: '.$this->displayValue($source?->subinventory));
        $this->addActionRow($rows, 'CUSTOM PO: '.$this->displayValue($source?->po_number));
        $this->addActionRow($rows, 'DESTINO: '.$this->displayValue($source?->destination));
        $this->addActionRow($rows, 'STD PACK: '.$this->displayQuantity($source?->std_pack_qty));

        if ($action->action_type === MasterActionRequest::TYPE_REPRINT) {
            $this->addActionRow($rows, 'REIMPRESION SIN CAMBIOS', true);
            $this->addActionRow($rows, 'COPIAS POR FOLIO: '.number_format((int) $action->copies));
            $this->addActionRow($rows, 'FOLIOS EXACTOS Y CANTIDAD ORIGINAL:', true);

            foreach ($action->selectedFolios as $folio) {
                $this->addActionRow($rows, sprintf(
                    'F%d: %s | %d COPIAS',
                    (int) $folio->folio_number,
                    $this->displayQuantity($folio->qty_for_folio),
                    (int) $action->copies,
                ));
            }

            $this->addActionRow($rows, sprintf(
                'TOTAL DE HOJAS SOLICITADAS: %d',
                $action->selectedFolios->count() * (int) $action->copies,
            ));
        } else {
            $this->addActionRow($rows, 'FOLIOS: ANTES -> DESPUES', true);
            $this->addActionRow($rows, sprintf(
                'EXISTENTES: %d | NUEVOS: %d',
                $action->selectedFolios->count(),
                (int) $action->additional_folios_count,
            ));

            foreach ($action->selectedFolios as $folio) {
                $newQuantity = $proposal === null
                    ? 'POR DEFINIR EN LABEL ROOM'
                    : $this->displayQuantity($this->proposedQuantity($proposal, (int) $folio->folio_number));
                $this->addActionRow($rows, sprintf(
                    'F%d: %s -> %s',
                    (int) $folio->folio_number,
                    $this->displayQuantity($folio->qty_for_folio),
                    $newQuantity,
                ));
            }

            $maximum = (int) $source?->folios->max('folio_number');
            for ($offset = 1; $offset <= (int) $action->additional_folios_count; $offset++) {
                $number = $maximum + $offset;
                $newQuantity = $proposal === null
                    ? 'POR DEFINIR EN LABEL ROOM'
                    : $this->displayQuantity($this->proposedQuantity($proposal, $number));
                $this->addActionRow($rows, "F{$number} NUEVO: SIN ORIGINAL -> {$newQuantity}");
            }

            if ($proposal !== null) {
                $this->addActionRow($rows, 'DATOS: ORIGINAL -> PROPUESTO', true);
                $line = ProductionLine::query()->find($proposal['line_id'] ?? null)?->code;
                $originalPartial = $source?->partial_folio
                    ? 'F'.$source->partial_folio.' / '.$this->displayQuantity($source->partial_qty)
                    : null;
                $proposedPartial = ! empty($proposal['partial_folio'])
                    ? 'F'.$proposal['partial_folio'].' / '.$this->displayQuantity($proposal['partial_qty'] ?? null)
                    : null;
                $changes = [
                    ['JOB ENSAMBLE', $source?->job_assembly, $proposal['job_assembly'] ?? null],
                    ['JOB EMPAQUE', $source?->job_packaging, $proposal['job_packaging'] ?? null],
                    ['TIPO', MasterModelMapping::labelForType((string) $source?->request_type), MasterModelMapping::labelForType((string) ($proposal['request_type'] ?? ''))],
                    ['LINEA', $source?->line?->code, $line],
                    ['LOCAL', $source?->local, $proposal['local'] ?? null],
                    ['SUBINVENTORY', $source?->subinventory, $proposal['subinventory'] ?? null],
                    ['MODELO', $originalModel, $proposal['model'] ?? null],
                    ['CUSTOM PO', $source?->po_number, $proposal['po_number'] ?? null],
                    ['DESTINO', $source?->destination, $proposal['destination'] ?? null],
                    ['STD PACK', $source?->std_pack_qty === null ? null : $this->displayQuantity($source->std_pack_qty), isset($proposal['std_pack_qty']) ? $this->displayQuantity($proposal['std_pack_qty']) : null],
                    ['PARCIAL', $originalPartial, $proposedPartial],
                ];
                $changeCount = 0;

                foreach ($changes as [$label, $original, $requested]) {
                    if (trim((string) $original) === trim((string) $requested)) {
                        continue;
                    }

                    $changeCount++;
                    $this->addActionRow($rows, "{$label}: {$this->displayValue($original)} -> {$this->displayValue($requested)}");
                }

                if ($changeCount === 0) {
                    $this->addActionRow($rows, 'DATOS GENERALES SIN CAMBIO; VER CANTIDADES POR FOLIO.');
                } else {
                    $this->addActionRow($rows, 'LOS DATOS NO LISTADOS CONSERVAN SU VALOR ORIGINAL.');
                }
            } else {
                $this->addActionRow($rows, 'SOLICITUD ANTERIOR: CAMBIOS POR DEFINIR EN LABEL ROOM.');
            }
        }

        $this->addActionRow($rows, 'SOLICITO: '.$this->displayValue($action->requested_by_name));
        $this->addActionRow($rows, 'MOTIVO DE KIOSK:', true);
        $this->addActionRow($rows, $this->displayValue($action->reason));

        if ($proposal !== null && ! empty($proposal['notes'])) {
            $this->addActionRow($rows, 'NOTAS DE KIOSK:', true);
            $this->addActionRow($rows, (string) $proposal['notes']);
        }

        return $rows;
    }

    private function proposedQuantity(array $proposal, int $folioNumber): mixed
    {
        return (int) ($proposal['partial_folio'] ?? 0) === $folioNumber
            ? ($proposal['partial_qty'] ?? null)
            : ($proposal['std_pack_qty'] ?? null);
    }

    private function printedModel(?MasterRequest $source): ?string
    {
        if ($source === null) {
            return null;
        }

        $source->loadMissing('printBatches.items');

        foreach ($source->printBatches->filter->isConfirmed()->sortBy([['printed_at', 'asc'], ['id', 'asc']]) as $batch) {
            foreach ($batch->items->sortBy('id') as $item) {
                if (filled($item->sheet_snapshot['model'] ?? null)) {
                    return (string) $item->sheet_snapshot['model'];
                }
            }
        }

        return null;
    }

    private function displayQuantity(mixed $quantity): string
    {
        return $quantity === null ? 'SIN CANTIDAD REGISTRADA' : number_format((int) $quantity).' PZ';
    }

    private function displayValue(mixed $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? 'NO REGISTRADO' : $value;
    }

    /** @param array<int, array{text: string, heading: bool}> $rows */
    private function addActionRow(array &$rows, string $text, bool $heading = false): void
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        $limit = $heading ? 35 : 39;
        $line = '';

        foreach (explode(' ', $text) as $word) {
            if (mb_strlen($word) > $limit) {
                if ($line !== '') {
                    $rows[] = ['text' => $line, 'heading' => $heading];
                    $line = '';
                }

                foreach (mb_str_split($word, $limit) as $piece) {
                    if (mb_strlen($piece) === $limit) {
                        $rows[] = ['text' => $piece, 'heading' => $heading];
                    } else {
                        $line = $piece;
                    }
                }

                continue;
            }

            if ($line === '') {
                $line = $word;
            } elseif (mb_strlen($line) + 1 + mb_strlen($word) <= $limit) {
                $line .= ' '.$word;
            } else {
                $rows[] = ['text' => $line, 'heading' => $heading];
                $line = $word;
            }
        }

        if ($line !== '') {
            $rows[] = ['text' => $line, 'heading' => $heading];
        }
    }

    /**
     * @param  array<int, array{text: string, heading: bool}>  $rows
     * @return array<int, array<int, array{text: string, heading: bool}>>
     */
    private function actionPages(array $rows): array
    {
        $pages = [];
        $page = [];

        foreach ($rows as $row) {
            if (count($page) === 30 || ($row['heading'] && count($page) >= 29)) {
                $pages[] = $page;
                $page = [];
            }

            $page[] = $row;
        }

        if ($page !== []) {
            $pages[] = $page;
        }

        return $pages;
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
        $folio = sprintf('#%06d', (int) $masterRequest->id);
        $shift = $masterRequest->shift?->code
            ?: $masterRequest->requestedBy?->shift?->code
            ?: 'SIN TURNO';

        return implode("\n", [
            ...$this->startLabel($dimensions),
            $this->field(28, 27, 620, 31, 'REQUISICION MASTER', $dimensions),
            $this->field(28, 67, 610, 37, $folio, $dimensions),
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
            ...$this->jobQrFields($masterRequest->job_assembly, $masterRequest->job_packaging, $dimensions, true),
            $this->line(25, 1150, 765, 2, $dimensions),
            $this->field(30, 1164, 95, 17, 'RECIBIO:', $dimensions),
            $this->line(125, 1189, 270, 2, $dimensions),
            $this->field(410, 1164, 365, 17, "TURNO: {$shift}", $dimensions),
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }

    /**
     * @param  array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float}  $dimensions
     * @return array<int, string>
     */
    private function jobQrFields(?string $jobAssembly, ?string $jobPackaging, array $dimensions, bool $inBody = false): array
    {
        $jobs = array_values(array_filter([
            ['label' => 'QR ENSAMBLE', 'job' => trim((string) $jobAssembly)],
            ['label' => 'QR EMPAQUE', 'job' => trim((string) $jobPackaging)],
        ], fn (array $item): bool => $item['job'] !== ''));
        $fields = [self::JOB_QR_MARKER];

        foreach ($jobs as $index => $item) {
            if ($inBody) {
                $x = count($jobs) === 2 ? ($index === 0 ? 240 : 490) : 365;
                $fields[] = $this->qr($x, 850, $item['job'], $dimensions, 4);
                $fields[] = $this->field($x - 20, 1025, 190, 18, $item['label'], $dimensions, 'C');
            } else {
                $x = count($jobs) === 2 && $index === 0 ? 555 : 675;
                $fields[] = $this->qr($x, 18, $item['job'], $dimensions, 3);
                $fields[] = $this->field($x - 6, 132, 118, 14, $item['label'], $dimensions, 'C');
            }
        }

        return $fields;
    }
}
