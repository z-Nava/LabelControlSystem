<?php

namespace App\Services\Masters;

use App\Models\MasterRequest;
use App\Models\MasterRequestFolio;
use App\Services\Catalogs\MasterModelMappingService;
use App\Services\Kiosk\KioskMasterRequestValidationService;
use App\Services\Oracle\OracleJobService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterRequestService
{
    public function __construct(
        private readonly OracleJobService $oracleJobService,
        private readonly MasterRequestProductionContextService $productionContextService,
        private readonly MasterModelMappingService $masterModelMappingService,
        private readonly MasterRequestLabelRoomValidationService $labelRoomValidationService,
        private readonly KioskMasterRequestValidationService $kioskValidationService,
        private readonly MasterRequestJobStateService $jobStateService,
    ) {}

    public function create(array $data, string $requestSource): MasterRequest
    {
        if (! in_array($requestSource, MasterRequest::SOURCES, true)) {
            throw new \InvalidArgumentException('Invalid master request source.');
        }

        $data['request_date'] = null;
        $data['shift_id'] = null;
        $data['leader_name'] = null;
        unset($data['line_id']);

        $data['request_source'] = $requestSource;
        $validationService = $this->validationServiceFor($requestSource);

        return DB::transaction(function () use ($data, $validationService) {

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

            $validationService->validate(
                $data,
                $oracleJob,
                $packagingOracleJob,
            );

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

            $validationService->validateResolvedModel($data['model']);

            $productionContext = $this->productionContextService->resolveFromJobs($data);
            $data = [...$data, ...$productionContext];

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
    public function lookupOracleJob(
        string $jobNumber,
        bool $includeLabelRoomState = false,
        ?string $role = null,
        ?string $counterpartJobNumber = null,
    ): array {
        $payload = $this->oracleJobService->buildLookupPayload($jobNumber);

        if ($payload['found'] ?? false) {
            $payload['production_context'] = $this->productionContextService
                ->describeOracleLine($payload['line'] ?? null);
            $payload['models_by_request_type'] = $this->masterModelMappingService
                ->resolveModelsForNp($payload['assembly'] ?? null);

            $oracleJob = $includeLabelRoomState
                ? $this->oracleJobService->findByJobNumber($payload['job_number'])
                : null;

            if ($oracleJob && $role !== null) {
                $payload['master_request_state'] = $this->jobStateService->summaryForJob($oracleJob, $role);

                $counterpartJobNumber = $this->normalizeNullable($counterpartJobNumber);

                if (
                    $role === MasterRequestJobStateService::ROLE_PACKAGING
                    || $counterpartJobNumber !== null
                ) {
                    $assemblyJobNumber = $role === MasterRequestJobStateService::ROLE_ASSEMBLY
                        ? (string) $oracleJob->job_number
                        : $counterpartJobNumber;
                    $packagingJobNumber = $role === MasterRequestJobStateService::ROLE_PACKAGING
                        ? (string) $oracleJob->job_number
                        : $counterpartJobNumber;
                    $payload['master_request_pair_state'] = $this->jobStateService->summaryForPair(
                        $assemblyJobNumber,
                        $packagingJobNumber,
                    );
                }
            }
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

    private function validationServiceFor(string $requestSource): MasterRequestValidationService
    {
        return match ($requestSource) {
            MasterRequest::SOURCE_KIOSK => $this->kioskValidationService,
            default => $this->labelRoomValidationService,
        };
    }
}
