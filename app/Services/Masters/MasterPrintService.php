<?php

namespace App\Services\Masters;

use App\Models\MasterPrintBatch;
use App\Models\MasterRequest;
use App\Models\MasterRequestBatchItem;
use App\Models\MasterRequestFolio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\OracleJob;
use App\Services\Catalogs\MasterModelMappingService;
use App\Services\Catalogs\StockLocatorService;

class MasterPrintService
{
    public function __construct(
        private readonly MasterRequestStatusService $statusService,
        private readonly StockLocatorService $stockLocatorService,
        private readonly MasterModelMappingService $masterModelMappingService,
    ) {}

    public function createBatch(
        MasterRequest $masterRequest,
        array $folioIds,
        string $batchType,
        int $copies,
        ?string $reason,
        ?int $printedByUserId,
        ?string $printedByName
    ): MasterPrintBatch {
        return DB::transaction(function () use (
            $masterRequest, $folioIds, $batchType, $copies, $reason, $printedByUserId, $printedByName
        ) {
            $masterRequest->refresh();

            if ($masterRequest->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'batch_type' => 'No se puede imprimir: la requisición está cancelada.',
                ]);
            }

            // Solo folios de ESTA requisición
            $folios = MasterRequestFolio::query()
                ->where('master_request_id', $masterRequest->id)
                ->whereIn('id', $folioIds)
                ->get();

            if ($folios->count() !== count($folioIds)) {
                throw ValidationException::withMessages([
                    'folio_ids' => 'Uno o más folios no pertenecen a esta requisición.',
                ]);
            }

            // Reglas por tipo
            if ($batchType === 'print') {
                $alreadyPrinted = $folios->where('status', 'printed');
                if ($alreadyPrinted->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'folio_ids' => 'Hay folios ya impresos. Usa reprint/rework para esos folios.',
                    ]);
                }
            }

            $batch = MasterPrintBatch::create([
                'master_request_id' => $masterRequest->id,
                'shift_id' => $masterRequest->shift_id,
                'batch_type' => $batchType,
                'reason' => $reason,
                'printed_by_user_id' => $printedByUserId,
                'printed_by_name' => $printedByName,
                'printed_at' => now(),
            ]);

            foreach ($folios as $folio) {
                MasterRequestBatchItem::create([
                    'master_print_batch_id' => $batch->id,
                    'master_request_folio_id' => $folio->id,
                    'copies' => $copies,
                ]);
            }

            // Marcar como printed (solo los del batch)
            MasterRequestFolio::query()
                ->whereIn('id', $folios->pluck('id'))
                ->update(['status' => 'printed']);

            // ✅ Recalcular status del master_request
            $this->statusService->recalculate($masterRequest);

            return $batch;
        });
    }

    public function renderPdf(MasterPrintBatch $batch): View
    {
        $batch->load([
            'masterRequest.line',
            'masterRequest.shift',
            'items.folio',
        ]);

        $mr = $batch->masterRequest;
        $folios = $batch->items->map(fn($i) => $i->folio)->sortBy('folio_number');

        $view = match ($mr->request_type) {
            'assembly' => 'master_print.pdf.assembly',
            'batteries_assembly' => 'master_print.pdf.batteries_assembly',
            'assembly_packaging' => 'master_print.pdf.assembly_packaging',
            'motors_molding' => 'master_print.pdf.motors_molding',
            default => 'master_print.pdf.assembly',
        };

        return view($view, compact('batch', 'mr', 'folios'));
    }

    public function downloadPdf(MasterPrintBatch $batch)
    {
        $data = $this->buildMasterEnsambleData($batch);

        $suffix = $this->resolveTemplateSuffix($batch->masterRequest?->request_type);
        $fileName = "master_batch_{$batch->id}_{$suffix}.pdf";

        $templateView = $this->resolveTemplateView($batch->masterRequest?->request_type);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView($templateView, $data + ['mode' => 'pdf']);
        $pdf->setPaper('letter', 'landscape');

        return $pdf->download($fileName);
    }

    public function renderPrintable(MasterPrintBatch $batch): View
    {
        $data = $this->buildMasterEnsambleData($batch);

        return view($this->resolveTemplateView($batch->masterRequest?->request_type), $data + ['mode' => 'print']);
    }

    protected function resolveTemplateView(?string $requestType): string
    {
        return match ($requestType) {
            'batteries_assembly' => 'master_print.templates.batteries_assembly',
            'motors_molding' => 'master_print.templates.motors_molding',
            'assembly_packaging' => 'master_print.templates.assembly_packaging',
            default => 'master_print.templates.assembly',
        };
    }

    protected function resolveTemplateSuffix(?string $requestType): string
    {
        return match ($requestType) {
            'batteries_assembly' => 'ensamble_baterias',
            'motors_molding' => 'motores_moldeo',
            'assembly_packaging' => 'ensamble_empaque',
            default => 'ensamble',
        };
    }

    protected function buildMasterEnsambleData(MasterPrintBatch $batch): array
    {
        $batch->load([
            'masterRequest.line',
            'masterRequest.shift',
            'items.folio',
        ]);

        $mr = $batch->masterRequest;

        $items = $batch->items
            ->map(fn ($i) => $i->folio)
            ->sortBy('folio_number')
            ->values();

        $oracle = OracleJob::query()
            ->where('job_number', $mr->job_assembly)
            ->first();

        $oraclePackaging = OracleJob::query()
            ->where('job_number', $mr->job_packaging)
            ->first();

        // Construimos "sheets" (1 hoja por folio) para NO usar @php en Blade
        $sheets = $items->map(function ($folio) use ($mr, $oracle, $oraclePackaging) {
            $folioNo = str_pad((string) $folio->folio_number, 2, '0', STR_PAD_LEFT);

            $job = (string) ($mr->job_assembly ?? '');
            $jobPackaging = (string) ($mr->job_packaging ?? '');
            $np  = (string) ($oracle?->assembly ?? '');
            $npPackaging = (string) ($oraclePackaging?->assembly ?? '');
            $desc = (string) ($oracle?->part_description ?? '');
            $descPackaging = (string) ($oraclePackaging?->part_description ?? '');

            $isMotors = ($mr->request_type ?? '') === 'motors_molding';
            $isAssemblyPackaging = ($mr->request_type ?? '') === 'assembly_packaging';
            $oracleLine = strtoupper(trim((string) ($oracle?->line ?? $oraclePackaging?->line ?? $mr->line?->code ?? '')));
            $resolvedLocal = $mr->local ? strtoupper(trim((string) $mr->local)) : $oracleLine;
            $mapping = $this->stockLocatorService->resolveActiveMappingByStockLocator($resolvedLocal);
            $requestType = (string) ($mr->request_type ?? '');
            $mappedModel = $requestType === 'assembly_packaging'
                ? $this->masterModelMappingService->resolveModelFromJobs($requestType, $npPackaging, $np)
                : $this->masterModelMappingService->resolveModelFromJobs($requestType, $np, $npPackaging);
            $resolvedModel = $isAssemblyPackaging
                ? (string) ($mappedModel ?? '')
                : (string) ($mappedModel ?? $mr->job_description ?? $oracle?->job_description ?? $oraclePackaging?->job_description ?? '');

            $lote = $job !== '' ? ($job . '-' . $folioNo) : '';
            $lotePackaging = $jobPackaging !== '' ? ($jobPackaging . '-' . $folioNo) : '';

            return [
                'leader' => (string) $mr->leader_name,
                'shift'  => (string) ($mr->shift?->code ?? $mr->shift?->name ?? ''),
                'line'   => (string) ($mr->line?->code ?? ''),
                'model' => $resolvedModel,

                
                'date'   => optional($mr->request_date)->format('d/m/Y'),
                'folio_id'   => $folio->id,
                'folio_no'   => $folioNo,
                'job'        => $job,
                'job_packaging' => $jobPackaging,
                'np'         => $np,
                'np_packaging' => $npPackaging,
                'desc'       => $desc,
                'desc_packaging' => $descPackaging,
                'lote'       => $lote,
                'lote_packaging' => $lotePackaging,
                'revision'   => (string) ($oracle?->bom_revision ?? ''),
                'po_number'  => (string) ($mr->po_number ?? ''),
                'destination' => (string) ($mr->destination ?? ''),

                // constantes del formato (si luego quieres configurarlas, las movemos a config/DB)
                'subinventory' => (string) ($mapping?->subinventory ?? ''),
                'local' => (string) ($resolvedLocal ?? ''),
                'WIP-MOTORS' => ($isAssemblyPackaging ? (string) ($mr->line?->code ?? '') : 'SMARKET-1'),
                'qty_pallet'   => (string) ($folio->qty_for_folio ?? $mr->std_pack_qty ?? ($isMotors ? 0 : '')),
            ];
        })->values();

        return compact('batch', 'mr', 'oracle', 'oraclePackaging', 'sheets');
    }

}
