<?php

namespace App\Services\Masters;

use App\Models\MasterModelMapping;
use App\Models\MasterRequest;
use App\Models\MasterRequestFolio;
use App\Models\ProductionLine;
use App\Services\Catalogs\StockLocatorService;
use App\Services\Oracle\OracleJobService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterRequestService
{
    public function __construct(
        private readonly OracleJobService $oracleJobService,
        private readonly StockLocatorService $stockLocatorService,
    ) {}

    public function create(array $data): MasterRequest
    {
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

            $selectedOracleLine = $this->normalize(
                ProductionLine::query()->whereKey($data['line_id'] ?? null)->value('code')
            );
            $jobOracleLine = $this->normalize($oracleJob?->line);
            $oracleLine = $selectedOracleLine !== '' ? $selectedOracleLine : $jobOracleLine;
            $isOrtAssembly = ($data['request_type'] ?? null) === MasterModelMapping::TYPE_ORT_ASSEMBLY;

            if ($isOrtAssembly) {
                $resolvedLocal = $this->normalize($data['local'] ?? '') ?: MasterModelMapping::ORT_DEFAULT_LOCAL;
                $resolvedSubinventory = $this->normalize($data['subinventory'] ?? '') ?: MasterModelMapping::ORT_DEFAULT_SUBINVENTORY;
            } else {
                $lineMapping = $this->stockLocatorService->resolveActiveMappingByOracleLine($oracleLine);
                $resolvedLocal = $this->normalize($lineMapping?->stock_locator);
                $resolvedSubinventory = $this->normalize($lineMapping?->subinventory);
            }

            if ($oracleLine === '' || $resolvedLocal === '' || $resolvedSubinventory === '') {
                throw ValidationException::withMessages([
                    'line_id' => sprintf(
                        'La Oracle Line %s no tiene un destino completo configurado en Locals by Oracle Line.',
                        $oracleLine !== '' ? $oracleLine : 'seleccionada',
                    ),
                ]);
            }

            $data['oracle_line'] = $oracleLine;
            $data['subinventory'] = $resolvedSubinventory;
            $data['local'] = $resolvedLocal;

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

    /**
     * Lookup: dado un job_number te regresa lo que ocupará el front.
     */
    public function lookupOracleJob(string $jobNumber): array
    {
        return $this->oracleJobService->buildLookupPayload($jobNumber);
    }

    private function normalize(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }
}
