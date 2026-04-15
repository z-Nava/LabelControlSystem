<?php

namespace App\Services\Dummies;

use App\Models\DummyPrintBatch;
use App\Models\DummyPrintBatchItem;
use App\Models\DummyRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DummyPrintService
{
    public function createBatch(DummyRequest $dummyRequest, array $data, ?int $printedByUserId, string $printedByName): DummyPrintBatch
    {
        if ($dummyRequest->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'No se puede imprimir una requisición cancelada.',
            ]);
        }

        $isPrintBatch = ($data['batch_type'] ?? null) === 'print';

        if ($dummyRequest->status === 'completed' && $isPrintBatch) {
            throw ValidationException::withMessages([
                'status' => 'No se puede crear un print en una requisición completada. Usa reprint.',
            ]);
        }

        return DB::transaction(function () use ($dummyRequest, $data, $printedByUserId, $printedByName, $isPrintBatch): DummyPrintBatch {
            $alreadyHasPrintBatch = $dummyRequest->printBatches()
                ->where('batch_type', 'print')
                ->exists();

            if ($isPrintBatch && $alreadyHasPrintBatch) {
                throw ValidationException::withMessages([
                    'batch_type' => 'Ya existe un batch print para esta requisición. Usa reprint para evitar duplicados.',
                ]);
            }

            if (!$isPrintBatch && !$alreadyHasPrintBatch) {
                throw ValidationException::withMessages([
                    'batch_type' => 'Primero debes crear un batch de tipo print.',
                ]);
            }

            $copies = (int) ($data['copies'] ?? 1);
            $items = $dummyRequest->items()->select('id')->orderBy('consecutive')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'No hay dummys generados para esta requisición.',
                ]);
            }

            $batch = DummyPrintBatch::query()->create([
                'dummy_request_id' => $dummyRequest->id,
                'shift_id' => $dummyRequest->shift_id,
                'batch_type' => $data['batch_type'],
                'reason' => $data['reason'] ?: null,
                'printed_by_user_id' => $printedByUserId,
                'printed_by_name' => $printedByName,
                'quantity' => $items->count() * $copies,
                'printed_at' => null,
            ]);

            $payload = $items->map(fn ($item) => [
                'dummy_print_batch_id' => $batch->id,
                'dummy_request_item_id' => $item->id,
                'copies' => $copies,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            DummyPrintBatchItem::query()->insert($payload);

            $dummyRequest->items()->increment('print_count', $copies, ['last_printed_at' => now()]);

            if ($dummyRequest->status === 'requested') {
                $dummyRequest->update(['status' => 'in_progress']);
            }

            return $batch->load('dummyRequest');
        });
    }
}
