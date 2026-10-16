<?php

namespace App\Services\Kiosk;

use App\Models\MasterActionRequest;
use App\Models\MasterModelMapping;
use App\Models\MasterRequest;
use App\Models\ProductionLine;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class KioskMasterRequisitionLabelZplBuilder extends AbstractKioskRequisitionLabelZplBuilder
{
    public const RECEIPT_LAYOUT_MARKER = '^FX KIOSK_MASTER_RECEIPT_V3^FS';

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
        $content = $this->actionSectionFields($this->actionSections($action), $dimensions, $isReprint);

        return implode("\n", [
            ...$this->startLabel($dimensions),
            $this->field(28, 27, 510, 20, $isReprint ? 'COMPROBANTE REIMPRESION MASTER' : 'COMPROBANTE RETRABAJO MASTER', $dimensions),
            $this->field(28, 67, 510, 23, sprintf('SOL. #%06d', (int) $action->id), $dimensions),
            ...$this->jobQrFields($jobAssembly, $jobPackaging, $dimensions),
            $this->field(28, 122, 515, 17, "ORIGEN #{$action->master_request_id} | REG: {$createdAt}", $dimensions),
            $this->field(28, 145, 515, 16, 'SOLICITO: '.$this->displayValue($action->requested_by_name), $dimensions),
            $this->field(28, 169, 750, 17, 'PENDIENTE DE VALIDACION EN LABEL ROOM', $dimensions),
            $this->line(25, 194, 765, 2, $dimensions),
            ...$content,
            ...($isReprint ? [] : [
                $this->box(465, 1060, 320, 210, 2, $dimensions),
                $this->field(472, 1070, 306, 16, 'SELLADO DE AUTORIZACION', $dimensions, 'C'),
                $this->line(465, 1096, 320, 2, $dimensions),
            ]),
            $this->field(30, 1164, 95, 17, 'RECIBIO:', $dimensions),
            $this->line(125, 1189, $isReprint ? 270 : 330, 2, $dimensions),
            $this->field(30, 1220, 400, 17, "TURNO: {$shift}", $dimensions),
            '^PQ1,0,1,N',
            '^XZ',
        ]);
    }

    /** @return array<int, array{title: string, kind: string, rows: array, intro?: array}> */
    private function actionSections(MasterActionRequest $action): array
    {
        $source = $action->masterRequest;
        $proposal = $action->proposed_rework;
        $originalModel = $source?->model ?: $this->printedModel($source);
        $originalPartial = $source?->partial_folio
            ? 'F'.$source->partial_folio.' / '.$this->displayQuantity($source->partial_qty)
            : 'NO REQUERIDO';
        $sections = [[
            'title' => 'DATOS DE LA REQUISICION ORIGINAL',
            'kind' => 'pairs',
            'rows' => [
                ['JOB ENSAMBLE: '.$this->displayValue($source?->job_assembly), 'JOB EMPAQUE: '.$this->displayValue($source?->job_packaging)],
                ['TIPO: '.MasterModelMapping::labelForType((string) $source?->request_type), 'MODELO: '.$this->displayValue($originalModel)],
                ['LINEA: '.$this->displayValue($source?->line?->code), 'LOCAL: '.$this->displayValue($source?->local)],
                ['SUBINVENTORY: '.$this->displayValue($source?->subinventory), 'CUSTOM PO: '.$this->displayValue($source?->po_number)],
                ['DESTINO: '.$this->displayValue($source?->destination), 'STD PACK: '.$this->displayQuantity($source?->std_pack_qty)],
                ['PARCIAL: '.$originalPartial, ''],
            ],
        ]];

        $isReprint = $action->action_type === MasterActionRequest::TYPE_REPRINT;
        $sections[] = [
            'title' => $isReprint ? 'FOLIOS EXACTOS Y CANTIDAD ORIGINAL' : 'FOLIOS: ANTES -> DESPUES',
            'kind' => 'folios',
            'intro' => $isReprint
                ? [
                    'REIMPRESION SIN CAMBIOS',
                    'COPIAS POR FOLIO: '.number_format((int) $action->copies)
                        .' | TOTAL DE HOJAS SOLICITADAS: '.($action->selectedFolios->count() * (int) $action->copies),
                ]
                : ['EXISTENTES: '.$action->selectedFolios->count().' | NUEVOS: '.(int) $action->additional_folios_count],
            'rows' => $this->folioRows($action),
        ];

        if (! $isReprint) {
            $sections[] = [
                'title' => 'DATOS: ORIGINAL -> PROPUESTO',
                'kind' => 'text',
                'rows' => $this->changeRows($source, $proposal, $originalModel),
            ];
        }

        $notes = ['MOTIVO: '.$this->displayValue($action->reason)];
        if ($proposal !== null && ! empty($proposal['notes'])) {
            $notes[] = 'NOTAS DE KIOSK: '.(string) $proposal['notes'];
        }
        $sections[] = [
            'title' => 'MOTIVO Y NOTAS DE KIOSK',
            'kind' => 'text',
            'rows' => $notes,
        ];

        return $sections;
    }

    /** @return array<int, string> */
    private function folioRows(MasterActionRequest $action): array
    {
        $proposal = $action->proposed_rework;
        $isReprint = $action->action_type === MasterActionRequest::TYPE_REPRINT;
        $entries = [];

        foreach ($action->selectedFolios->sortBy('folio_number') as $folio) {
            $number = (int) $folio->folio_number;
            $entries[] = [
                'number' => $number,
                'original' => $this->displayQuantity($folio->qty_for_folio),
                'requested' => $isReprint
                    ? (int) $action->copies.' COPIAS'
                    : ($proposal === null ? 'POR DEFINIR EN LABEL ROOM' : $this->displayQuantity($this->proposedQuantity($proposal, $number))),
                'new' => false,
            ];
        }

        if (! $isReprint) {
            $maximum = (int) $action->masterRequest?->folios->max('folio_number');
            for ($offset = 1; $offset <= (int) $action->additional_folios_count; $offset++) {
                $number = $maximum + $offset;
                $entries[] = [
                    'number' => $number,
                    'original' => 'SIN ORIGINAL',
                    'requested' => $proposal === null
                        ? 'POR DEFINIR EN LABEL ROOM'
                        : $this->displayQuantity($this->proposedQuantity($proposal, $number)),
                    'new' => true,
                ];
            }
        }

        $rows = [];
        $group = null;
        foreach ($entries as $entry) {
            if ($group !== null
                && $entry['number'] === $group['end'] + 1
                && $entry['original'] === $group['original']
                && $entry['requested'] === $group['requested']
                && $entry['new'] === $group['new']) {
                $group['end'] = $entry['number'];
                continue;
            }

            if ($group !== null) {
                $rows[] = $this->folioGroupText($group, $isReprint);
            }
            $group = [...$entry, 'end' => $entry['number']];
        }
        if ($group !== null) {
            $rows[] = $this->folioGroupText($group, $isReprint);
        }

        return $rows;
    }

    /** @param array{number: int, end: int, original: string, requested: string, new: bool} $group */
    private function folioGroupText(array $group, bool $isReprint): string
    {
        $numbers = 'F'.$group['number'].($group['end'] > $group['number'] ? '-F'.$group['end'] : '');

        return $numbers.($group['new'] ? ' NUEVO' : '').': '.$group['original']
            .($isReprint ? ' | ' : ' -> ').$group['requested'];
    }

    /** @return array<int, string> */
    private function changeRows(?MasterRequest $source, ?array $proposal, ?string $originalModel): array
    {
        if ($proposal === null) {
            return ['SOLICITUD ANTERIOR: CAMBIOS POR DEFINIR EN LABEL ROOM.'];
        }

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
        $rows = [];
        foreach ($changes as [$label, $original, $requested]) {
            if (trim((string) $original) !== trim((string) $requested)) {
                $rows[] = "{$label}: {$this->displayValue($original)} -> {$this->displayValue($requested)}";
            }
        }

        if ($rows === []) {
            return ['DATOS GENERALES SIN CAMBIO; VER CANTIDADES POR FOLIO.'];
        }

        $rows[] = 'LOS DATOS NO LISTADOS CONSERVAN SU VALOR ORIGINAL.';

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

    /**
     * @param array<int, array{title: string, kind: string, rows: array, intro?: array}> $sections
     * @param array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float} $dimensions
     * @return array<int, string>
     */
    private function actionSectionFields(array $sections, array $dimensions, bool $isReprint): array
    {
        $presets = [
            ['font' => 16, 'line' => 23, 'heading' => 17, 'heading_height' => 34, 'gap' => 9],
            ['font' => 14, 'line' => 20, 'heading' => 16, 'heading_height' => 31, 'gap' => 7],
            ['font' => 12, 'line' => 17, 'heading' => 14, 'heading_height' => 27, 'gap' => 5],
            ['font' => 11, 'line' => 15, 'heading' => 13, 'heading_height' => 25, 'gap' => 4],
        ];
        $bottom = $isReprint ? 1138 : 1042;

        foreach ($presets as $preset) {
            $y = 205;
            $fields = [];
            $fits = true;

            foreach ($sections as $section) {
                [$height, $sectionFields] = $this->layoutActionSection($section, $dimensions, $preset, $y);
                if ($y + $height > $bottom) {
                    $fits = false;
                    break;
                }
                array_push($fields, ...$sectionFields);
                $y += $height + $preset['gap'];
            }

            if ($fits) {
                return $fields;
            }
        }

        throw ValidationException::withMessages([
            $isReprint ? 'folio_ids' : 'selected_folio_numbers' =>
                'El detalle no cabe en un comprobante de una sola etiqueta. Divide los folios o acorta el motivo y las notas.',
        ]);
    }

    /**
     * @param array{title: string, kind: string, rows: array, intro?: array} $section
     * @param array{width: int, height: int, x_scale: float, y_scale: float, font_scale: float} $dimensions
     * @param array{font: int, line: int, heading: int, heading_height: int, gap: int} $preset
     * @return array{int, array<int, string>}
     */
    private function layoutActionSection(array $section, array $dimensions, array $preset, int $top): array
    {
        $font = $preset['font'];
        $lineHeight = $preset['line'];
        $headingHeight = $preset['heading_height'];
        $y = $top + $headingHeight + 7;
        $fields = [];
        $dividerFrom = $y;
        $hasDivider = $section['kind'] === 'pairs';

        if ($section['kind'] === 'pairs') {
            foreach ($section['rows'] as [$left, $right]) {
                $leftLines = $this->wrapActionText($left, 350, $font);
                $rightLines = $right === '' ? [] : $this->wrapActionText($right, 350, $font);
                foreach ($leftLines as $index => $line) {
                    $fields[] = $this->field(36, $y + $index * $lineHeight, 350, $font, $line, $dimensions);
                }
                foreach ($rightLines as $index => $line) {
                    $fields[] = $this->field(417, $y + $index * $lineHeight, 350, $font, $line, $dimensions);
                }
                $y += max(count($leftLines), count($rightLines)) * $lineHeight + 3;
            }
        } elseif ($section['kind'] === 'folios') {
            foreach ($section['intro'] ?? [] as $intro) {
                foreach ($this->wrapActionText($intro, 735, $font) as $line) {
                    $fields[] = $this->field(36, $y, 735, $font, $line, $dimensions);
                    $y += $lineHeight;
                }
            }
            $y += 3;
            $dividerFrom = $y;
            $hasDivider = count($section['rows']) > 4;
            if ($hasDivider) {
                foreach (array_chunk($section['rows'], 2) as $pair) {
                    $leftLines = $this->wrapActionText($pair[0], 350, $font);
                    $rightLines = isset($pair[1]) ? $this->wrapActionText($pair[1], 350, $font) : [];
                    foreach ($leftLines as $index => $line) {
                        $fields[] = $this->field(36, $y + $index * $lineHeight, 350, $font, $line, $dimensions);
                    }
                    foreach ($rightLines as $index => $line) {
                        $fields[] = $this->field(417, $y + $index * $lineHeight, 350, $font, $line, $dimensions);
                    }
                    $y += max(count($leftLines), count($rightLines)) * $lineHeight + 3;
                }
            } else {
                foreach ($section['rows'] as $row) {
                    foreach ($this->wrapActionText($row, 735, $font) as $line) {
                        $fields[] = $this->field(36, $y, 735, $font, $line, $dimensions);
                        $y += $lineHeight;
                    }
                    $y += 3;
                }
            }
        } else {
            foreach ($section['rows'] as $row) {
                foreach ($this->wrapActionText($row, 735, $font) as $line) {
                    $fields[] = $this->field(36, $y, 735, $font, $line, $dimensions);
                    $y += $lineHeight;
                }
                $y += 3;
            }
        }

        $height = $y - $top + 5;
        $frame = [
            $this->box(25, $top, 765, $height, 2, $dimensions),
            $this->field(36, $top + 7, 735, $preset['heading'], $section['title'], $dimensions),
            $this->line(25, $top + $headingHeight, 765, 2, $dimensions),
        ];
        if ($hasDivider && $y > $dividerFrom + $lineHeight) {
            $frame[] = $this->box(404, $dividerFrom - 2, 2, $y - $dividerFrom + 5, 2, $dimensions);
        }

        return [$height, [...$frame, ...$fields]];
    }

    /** @return array<int, string> */
    private function wrapActionText(string $value, int $width, int $font): array
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        if ($value === '') {
            return [];
        }

        $limit = max(18, (int) floor($width / ($font * 0.85)));
        $lines = [];
        $current = '';
        foreach (explode(' ', $value) as $word) {
            while (mb_strlen($word) > $limit) {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }
                $lines[] = mb_substr($word, 0, $limit);
                $word = mb_substr($word, $limit);
            }
            if ($word === '') {
                continue;
            }
            if ($current === '') {
                $current = $word;
            } elseif (mb_strlen($current) + 1 + mb_strlen($word) <= $limit) {
                $current .= ' '.$word;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
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
        $po = trim((string) $masterRequest->po_number);
        $detailWidth = $po !== '' ? 585 : 750;
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

            $this->field(30, 170, 555, 21, 'JOB ENSAMBLE:', $dimensions),
            $this->field(30, 204, 555, $this->jobTextSize($jobAssembly), $jobAssembly, $dimensions),
            $this->line(25, 282, 765, 2, $dimensions),
            $this->field(30, 295, 555, 21, 'JOB EMPAQUE:', $dimensions),
            $this->field(30, 329, 555, $this->jobTextSize($jobPackaging), $jobPackaging, $dimensions),
            $this->line(25, 407, 765, 2, $dimensions),
            ...$this->masterJobQrFields($masterRequest->job_assembly, $masterRequest->job_packaging, $dimensions),
            $this->field(30, 430, $detailWidth, 21, 'PO:', $dimensions),
            $this->field(30, 461, $detailWidth, 27, $po !== '' ? $po : 'N/A', $dimensions),
            $this->field(30, 505, $detailWidth, 21, 'DESTINO:', $dimensions),
            $this->field(30, 536, $detailWidth, 26, (string) ($masterRequest->destination ?: 'N/A'), $dimensions),
            ...($po !== '' ? [
                $this->field(625, 418, 165, 14, 'QR PO', $dimensions, 'C'),
                $this->qr(635, 438, $po, $dimensions, 3),
            ] : []),
            $this->line(25, 582, 765, 2, $dimensions),

            $this->field(30, 607, 750, 21, 'TIPO: '.MasterModelMapping::labelForType((string) $masterRequest->request_type), $dimensions),
            $this->field(30, 657, 750, 21, 'MODELO: '.($masterRequest->model ?: 'N/A'), $dimensions),
            $this->field(30, 707, 360, 21, "SOLICITUD: {$kind}", $dimensions),
            $this->field(405, 707, 375, 21, "FOLIOS: {$masterRequest->folios_from} AL {$masterRequest->folios_to}", $dimensions),
            $this->field(30, 757, 360, 21, 'STD PACK: '.number_format((int) $masterRequest->std_pack_qty), $dimensions),
            $this->field(405, 757, 375, 21, "PARCIAL: {$partial}", $dimensions),
            $this->field(30, 807, 360, 21, 'LOCAL: '.($masterRequest->local ?: 'N/A'), $dimensions),
            $this->field(405, 807, 375, 21, 'SUBINV: '.($masterRequest->subinventory ?: 'N/A'), $dimensions),
            $this->field(30, 857, 360, 21, 'LIDER: '.($masterRequest->leader_name ?: 'N/A'), $dimensions),
            $this->field(405, 857, 375, 21, 'SOLICITA: '.($masterRequest->requested_by_name ?: 'N/A'), $dimensions),
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
    private function masterJobQrFields(?string $jobAssembly, ?string $jobPackaging, array $dimensions): array
    {
        $fields = [self::RECEIPT_LAYOUT_MARKER];

        foreach ([
            ['job' => $jobAssembly, 'y' => 166, 'caption_y' => 262, 'caption' => 'QR ENSAMBLE'],
            ['job' => $jobPackaging, 'y' => 291, 'caption_y' => 387, 'caption' => 'QR EMPAQUE'],
        ] as $item) {
            if (blank($item['job'])) {
                continue;
            }

            $fields[] = $this->qr(650, $item['y'], trim((string) $item['job']), $dimensions, 3);
            $fields[] = $this->field(625, $item['caption_y'], 160, 14, $item['caption'], $dimensions, 'C');
        }

        return $fields;
    }

    private function jobTextSize(string $job): int
    {
        return max(16, min(28, (int) floor(555 / max(1, mb_strlen($job)) / 0.8)));
    }

    private function jobQrFields(?string $jobAssembly, ?string $jobPackaging, array $dimensions): array
    {
        $jobs = array_values(array_filter([
            ['label' => 'QR ENSAMBLE', 'job' => trim((string) $jobAssembly)],
            ['label' => 'QR EMPAQUE', 'job' => trim((string) $jobPackaging)],
        ], fn (array $item): bool => $item['job'] !== ''));
        $fields = [self::RECEIPT_LAYOUT_MARKER];

        foreach ($jobs as $index => $item) {
            $x = count($jobs) === 2 && $index === 0 ? 555 : 675;
            $fields[] = $this->qr($x, 18, $item['job'], $dimensions, 3);
            $fields[] = $this->field($x - 6, 132, 118, 14, $item['label'], $dimensions, 'C');
        }

        return $fields;
    }
}
