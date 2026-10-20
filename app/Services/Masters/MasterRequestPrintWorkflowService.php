<?php

namespace App\Services\Masters;

use App\Models\MasterPrintBatch;
use App\Models\MasterRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterRequestPrintWorkflowService
{
    public function __construct(
        private readonly MasterRequestService $masterRequestService,
        private readonly MasterPrintService $masterPrintService,
    ) {}

    public function createAndStartInitialPrint(
        array $data,
        string $requestSource,
        ?int $userId,
        string $userName,
    ): MasterPrintBatch {
        return DB::transaction(function () use ($data, $requestSource, $userId, $userName): MasterPrintBatch {
            $masterRequest = $this->masterRequestService->create($data, $requestSource);

            return $this->masterPrintService->createBatch(
                masterRequest: $masterRequest,
                folioIds: $masterRequest->folios->modelKeys(),
                batchType: 'print',
                copies: 1,
                reason: null,
                printedByUserId: $userId,
                printedByName: $userName,
            );
        });
    }

    public function createAndStartManualPrint(
        array $data,
        ?int $userId,
        string $userName,
    ): MasterPrintBatch {
        return DB::transaction(function () use ($data, $userId, $userName): MasterPrintBatch {
            $masterRequest = $this->masterRequestService->createManual($data);

            return $this->masterPrintService->createBatch(
                masterRequest: $masterRequest,
                folioIds: $masterRequest->folios->modelKeys(),
                batchType: 'print',
                copies: 1,
                reason: null,
                printedByUserId: $userId,
                printedByName: $userName,
            );
        });
    }

    public function startPendingKioskPrint(
        MasterRequest $masterRequest,
        ?int $userId,
        string $userName,
    ): MasterPrintBatch {
        return DB::transaction(function () use ($masterRequest, $userId, $userName): MasterPrintBatch {
            $masterRequest = MasterRequest::query()
                ->whereKey($masterRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $masterRequest->isFromKiosk() || $masterRequest->isRework() || $masterRequest->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'Esta requisición no está disponible para la impresión directa de Kiosk.',
                ]);
            }

            $folioIds = $masterRequest->folios()
                ->where('status', 'pending')
                ->orderBy('folio_number')
                ->pluck('id')
                ->all();

            if ($folioIds === []) {
                throw ValidationException::withMessages([
                    'folio_ids' => 'Esta requisición no tiene folios pendientes por imprimir.',
                ]);
            }

            $pendingBatch = $masterRequest->printBatches()
                ->pending()
                ->where('batch_type', 'print')
                ->whereHas('items', fn ($items) => $items->whereIn('master_request_folio_id', $folioIds))
                ->oldest('id')
                ->first();

            if ($pendingBatch) {
                return $pendingBatch;
            }

            return $this->masterPrintService->createBatch(
                masterRequest: $masterRequest,
                folioIds: $folioIds,
                batchType: 'print',
                copies: 1,
                reason: null,
                printedByUserId: $userId,
                printedByName: $userName,
            );
        });
    }
}
