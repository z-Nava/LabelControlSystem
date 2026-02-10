<?php

namespace App\Services\Masters;

use App\Models\MasterRequest;
use App\Models\MasterRequestFolio;

class MasterRequestStatusService
{
    /**
     * Reglas:
     * - cancelled: no tocar (estado terminal)
     * - Si NO hay folios impresos -> requested (si no hay batches, lo dejamos así)
     * - Si hay al menos 1 folio printed -> in_progress
     * - Si TODOS los folios están printed -> completed  (Opción A)
     *
     * Nota: Si luego quieres Opción B (completed solo con delivery recibido),
     * aquí lo cambiamos para que completed dependa de deliveries.
     */
    public function recalculate(MasterRequest $mr): MasterRequest
    {
        $mr->refresh();

        if ($mr->status === 'cancelled') {
            return $mr;
        }

        // Si no hay folios (caso raro), dejamos requested
        $totalFolios = MasterRequestFolio::query()
            ->where('master_request_id', $mr->id)
            ->count();

        if ($totalFolios === 0) {
            $mr->status = 'requested';
            $mr->save();
            return $mr;
        }

        $pendingExists = MasterRequestFolio::query()
            ->where('master_request_id', $mr->id)
            ->where('status', '!=', 'printed')
            ->exists();

        // Si ya todos impresos -> completed
        if (! $pendingExists) {
            $mr->status = 'completed';
            $mr->save();
            return $mr;
        }

        // Si existe al menos uno impreso -> in_progress, si no -> requested
        $anyPrinted = MasterRequestFolio::query()
            ->where('master_request_id', $mr->id)
            ->where('status', 'printed')
            ->exists();

        $mr->status = $anyPrinted ? 'in_progress' : 'requested';
        $mr->save();

        return $mr;
    }
}
