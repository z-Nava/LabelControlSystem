<?php

namespace App\Services\Masters;

use App\Models\MasterPrintBatch;
use Illuminate\Support\Facades\DB;

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
}
