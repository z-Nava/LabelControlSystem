<?php

namespace App\Services\Masters;

use App\Models\MasterPrintBatch;
use App\Models\MasterRequest;
use App\Models\MasterRequestBatchItem;
use App\Models\MasterRequestFolio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MasterPrintService
{
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

        // Aquí seleccionaremos el template según request_type
        $view = match ($mr->request_type) {
            'assembly' => 'master_print.pdf.assembly',
            'batteries_assembly' => 'master_print.pdf.batteries_assembly',
            'assembly_packaging' => 'master_print.pdf.assembly_packaging',
            'motors_molding' => 'master_print.pdf.motors_molding',
            default => 'master_print.pdf.assembly',
        };

        return view($view, compact('batch', 'mr', 'folios'));
    }
}
