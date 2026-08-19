<?php

namespace App\Services\Masters;

use App\Models\MasterModelMapping;
use App\Models\MasterRequest;
use App\Models\MasterRequestFolio;
use App\Models\ProductionLine;
use App\Services\Catalogs\MasterModelMappingService;
use App\Services\Catalogs\StockLocatorService;
use App\Services\Oracle\OracleJobService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterRequestService
{
    public function __construct(
        private readonly OracleJobService $oracleJobService,
        private readonly StockLocatorService $stockLocatorService,
        private readonly MasterRequestProductionContextService $productionContextService,
        private readonly MasterModelMappingService $masterModelMappingService,
    ) {}

    public function create(array $data, string $requestSource): MasterRequest
    {
        if (! in_array($requestSource, MasterRequest::SOURCES, true)) {
            throw new \InvalidArgumentException('Invalid master request source.');
        }

        if ($requestSource === MasterRequest::SOURCE_LABEL_ROOM) {
            $data['request_date'] = null;
            $data['shift_id'] = null;
            $data['leader_name'] = null;
            unset($data['line_id']);
        }

        $data['request_source'] = $requestSource;

        return DB::transaction(function () use ($data) {

            $foliosFrom = (int) ($data['folios_from'] ?? 0);
            $foliosTo = (int) ($data['folios_to'] ?? 0);
            $hasPartialData = ! empty($data['partial_folio']) && ! empty($data['partial_qty']);

            if ($foliosFrom < 1 || $foliosTo < $foliosFrom) {
                throw ValidationException::withMessages([
                    'folios_from' => 'Rango de folios inválido.',
                ]);
            }

            if ($hasPartialData) {
                // El folio parcial siempre debe ser el consecutivo del último folio normal.
                $data['partial_folio'] = $foliosTo + 1;
            } else {
                $data['partial_folio'] = null;
                $data['partial_qty'] = null;
            }

            $oracleJob = ! empty($data['job_assembly'])
                ? $this->oracleJobService->findByJobNumber($data['job_assembly'])
                : null;
            $packagingOracleJob = ! empty($data['job_packaging'])
                ? $this->oracleJobService->findByJobNumber($data['job_packaging'])
                : null;

            // PO and destination belong to the packaging Job. Never trust values
            // submitted by the browser or fall back to the assembly Job.
            $data['po_number'] = $this->normalizeNullable($packagingOracleJob?->ttl_cust_po);
            $data['destination'] = $this->normalizeNullable($packagingOracleJob?->ship_code);
            $data['model'] = $this->normalizeNullable(
                $this->masterModelMappingService->resolveModelFromJobs(
                    (string) ($data['request_type'] ?? ''),
                    $oracleJob?->assembly,
                    $packagingOracleJob?->assembly,
                )
            );

            if (
                $data['request_source'] === MasterRequest::SOURCE_LABEL_ROOM
                && $data['model'] === null
            ) {
                throw ValidationException::withMessages([
                    'model' => 'No se puede enviar la requisición: el assembly no tiene un modelo activo en Master Model Mapping.',
                ]);
            }

            if ($data['request_source'] === MasterRequest::SOURCE_LABEL_ROOM) {
                $productionContext = $this->productionContextService->resolveForLabelRoom($data);
                $data = [...$data, ...$productionContext];
            } else {
                $oracleLine = $this->normalize(
                    ProductionLine::query()->whereKey($data['line_id'] ?? null)->value('code')
                );
                $isOrtAssembly = ($data['request_type'] ?? null) === MasterModelMapping::TYPE_ORT_ASSEMBLY;

                if ($isOrtAssembly) {
                    $resolvedLocal = $this->normalize($data['local'] ?? '') ?: MasterModelMapping::ORT_DEFAULT_LOCAL;
                    $resolvedSubinventory = $this->normalize($data['subinventory'] ?? '') ?: MasterModelMapping::ORT_DEFAULT_SUBINVENTORY;
                } else {
                    $lineMapping = $this->stockLocatorService->resolveActiveMappingByOracleLine($oracleLine);
                    $resolvedLocal = $this->normalize($lineMapping?->stock_locator);
                    $resolvedSubinventory = $this->normalize($lineMapping?->subinventory);
                }

                if ($oracleLine === '') {
                    throw ValidationException::withMessages([
                        'line_id' => 'La línea seleccionada no tiene una Oracle Line configurada.',
                    ]);
                }

                $data['oracle_line'] = $oracleLine;
                $data['subinventory'] = $resolvedSubinventory;
                $data['local'] = $resolvedLocal;
            }

            $mr = MasterRequest::create($data);

            // Folios normales
            for ($f = $foliosFrom; $f <= $foliosTo; $f++) {
                MasterRequestFolio::create([
                    'master_request_id' => $mr->id,
                    'folio_number' => $f,
                    'is_partial' => false,
                    'qty_for_folio' => $data['std_pack_qty'] ?? null,
                    'status' => 'pending',
                ]);
            }

            // Folio parcial (opcional)
            if ($hasPartialData) {
                MasterRequestFolio::create([
                    'master_request_id' => $mr->id,
                    'folio_number' => (int) $data['partial_folio'],
                    'is_partial' => true,
                    'qty_for_folio' => (int) $data['partial_qty'],
                    'status' => 'pending',
                ]);
            }

            return $mr->load(['line', 'shift', 'folios']);
        });
    }

    public function cancel(
        MasterRequest $masterRequest,
        string $reason,
        ?int $cancelledByUserId,
        string $cancelledByName,
    ): MasterRequest {
        $reason = trim($reason);

        if ($reason === '' || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'El motivo de cancelación es obligatorio y no puede exceder 500 caracteres.',
            ]);
        }

        return DB::transaction(function () use ($masterRequest, $reason, $cancelledByUserId, $cancelledByName): MasterRequest {
            $lockedRequest = MasterRequest::query()
                ->whereKey($masterRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== MasterRequest::STATUS_REQUESTED) {
                throw ValidationException::withMessages([
                    'status' => 'Solo se pueden cancelar requisiciones Master en estado requested.',
                ]);
            }

            $hasPrintedFolios = MasterRequestFolio::query()
                ->where('master_request_id', $lockedRequest->id)
                ->where('status', 'printed')
                ->exists();

            if ($hasPrintedFolios) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede cancelar: la requisición ya tiene folios impresos.',
                ]);
            }

            if ($lockedRequest->printBatches()->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede cancelar: la requisición ya tiene batches de impresión.',
                ]);
            }

            $lockedRequest->update([
                'status' => MasterRequest::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $cancelledByUserId,
                'cancelled_by_name' => $cancelledByName,
            ]);

            return $lockedRequest->refresh();
        });
    }

    /**
     * Lookup: dado un job_number te regresa lo que ocupará el front.
     */
    public function lookupOracleJob(string $jobNumber): array
    {
        $payload = $this->oracleJobService->buildLookupPayload($jobNumber);

        if ($payload['found'] ?? false) {
            $payload['production_context'] = $this->productionContextService
                ->describeOracleLine($payload['line'] ?? null);
            $payload['models_by_request_type'] = $this->masterModelMappingService
                ->resolveModelsForNp($payload['assembly'] ?? null);
        }

        return $payload;
    }

    private function normalize(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function normalizeNullable(mixed $value): ?string
    {
        $normalized = $this->normalize($value);

        return $normalized !== '' ? $normalized : null;
    }
}
