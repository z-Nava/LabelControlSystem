<?php

namespace App\Services\Masters;

use App\Models\MasterModelMapping;
use App\Models\MasterPrintBatch;
use App\Models\MasterRequest;
use App\Models\ProductionLine;
use App\Models\StockLocator;
use App\Services\Catalogs\MasterModelMappingService;
use App\Services\Catalogs\StockLocatorService;
use App\Services\Oracle\OracleJobService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterReworkService
{
    public function __construct(
        private readonly OracleJobService $oracleJobService,
        private readonly StockLocatorService $stockLocatorService,
        private readonly MasterRequestProductionContextService $productionContextService,
        private readonly MasterModelMappingService $masterModelMappingService,
        private readonly MasterPrintService $masterPrintService,
    ) {}

    public function buildCreateFormData(MasterRequest $masterRequest): array
    {
        $this->ensureCanBeReworked($masterRequest);

        $masterRequest->loadMissing([
            'line',
            'shift',
            'folios' => fn ($query) => $query->orderBy('folio_number'),
            'printBatches.items',
        ]);

        $inventoryMappings = StockLocator::query()
            ->where('active', true)
            ->get()
            ->keyBy(fn (StockLocator $mapping): string => $this->normalize($mapping->oracle_line));
        $lines = ProductionLine::query()
            ->where('active', true)
            ->orderBy('code')
            ->get()
            ->map(function (ProductionLine $line) use ($inventoryMappings): ProductionLine {
                $mapping = $inventoryMappings->get($this->normalize($line->code));
                $line->setAttribute('suggested_local', $this->normalizeNullable($mapping?->stock_locator));
                $line->setAttribute('suggested_subinventory', $this->normalizeNullable($mapping?->subinventory));

                return $line;
            });
        $snapshot = $this->firstPrintedSnapshot($masterRequest);
        $initialModel = $masterRequest->model
            ?: ($snapshot['model'] ?? null)
            ?: $this->resolveModelFromRequest($masterRequest);

        return [
            'masterRequest' => $masterRequest,
            'lines' => $lines,
            'masterRequestTypes' => MasterModelMapping::requestOptions(),
            'ortAssemblyConfig' => MasterModelMapping::ortAssemblyRequestConfiguration(),
            'originalSnapshot' => $snapshot,
            'initialModel' => $initialModel,
        ];
    }

    public function createRevision(
        MasterRequest $baseRequest,
        array $data,
        ?int $userId,
        string $userName,
    ): MasterRequest {
        return DB::transaction(function () use ($baseRequest, $data, $userId, $userName): MasterRequest {
            $baseRequest = MasterRequest::query()
                ->with(['line', 'folios', 'printBatches.items'])
                ->lockForUpdate()
                ->findOrFail($baseRequest->id);
            $this->ensureCanBeReworked($baseRequest);

            $rootRequestId = $baseRequest->parent_master_request_id ?: $baseRequest->id;
            $rootRequest = MasterRequest::query()->lockForUpdate()->findOrFail($rootRequestId);
            $revisionNumber = ((int) MasterRequest::query()
                ->where('parent_master_request_id', $rootRequestId)
                ->max('revision_number')) + 1;

            $resolvedContext = $this->productionContextService->resolveForLabelRoom([
                ...$data,
                'local' => null,
                'subinventory' => null,
            ]);
            $assemblyJob = ! empty($data['job_assembly'])
                ? $this->oracleJobService->findByJobNumber($data['job_assembly'])
                : null;
            $packagingJob = ! empty($data['job_packaging'])
                ? $this->oracleJobService->findByJobNumber($data['job_packaging'])
                : null;
            $finalLine = ProductionLine::query()
                ->where('active', true)
                ->findOrFail($data['line_id']);
            $lineInventory = $this->stockLocatorService
                ->resolveActiveMappingByOracleLine((string) $finalLine->code);
            $isOrtAssembly = $data['request_type'] === MasterModelMapping::TYPE_ORT_ASSEMBLY;
            $suggestedLocal = $isOrtAssembly
                ? MasterModelMapping::ORT_DEFAULT_LOCAL
                : $this->normalizeNullable($lineInventory?->stock_locator);
            $suggestedSubinventory = $isOrtAssembly
                ? MasterModelMapping::ORT_DEFAULT_SUBINVENTORY
                : $this->normalizeNullable($lineInventory?->subinventory);
            $suggestedModel = $this->masterModelMappingService->resolveModelFromJobs(
                $data['request_type'],
                $assemblyJob?->assembly,
                $packagingJob?->assembly,
            );
            $finalLocal = $this->normalizeNullable($data['local'] ?? null) ?: $suggestedLocal;
            $finalSubinventory = $this->normalizeNullable($data['subinventory'] ?? null) ?: $suggestedSubinventory;
            $finalModel = $this->normalizeNullable($data['model'] ?? null)
                ?: $this->normalizeNullable($suggestedModel);

            $originalFolioNumbers = $baseRequest->folios
                ->pluck('folio_number')
                ->map(fn (mixed $folio): int => (int) $folio)
                ->sort()
                ->values();
            $selectedFolioNumbers = collect($data['selected_folio_numbers'] ?? [])
                ->map(fn (mixed $folio): int => (int) $folio)
                ->unique()
                ->values();
            $additionalCount = (int) $data['additional_folios_count'];
            $originalMaximum = (int) $originalFolioNumbers->max();
            $additionalFolioNumbers = collect($additionalCount > 0
                ? range($originalMaximum + 1, $originalMaximum + $additionalCount)
                : []);
            $finalFolioNumbers = $selectedFolioNumbers
                ->merge($additionalFolioNumbers)
                ->unique()
                ->sort()
                ->values();
            $partialFolio = isset($data['partial_folio']) ? (int) $data['partial_folio'] : null;
            $partialQty = isset($data['partial_qty']) ? (int) $data['partial_qty'] : null;
            $standardPack = isset($data['std_pack_qty']) ? (int) $data['std_pack_qty'] : null;
            $poNumber = $this->normalizeNullable($packagingJob?->ttl_cust_po);
            $destination = $this->normalizeNullable($packagingJob?->ship_code);
            $originalValues = $this->originalValues($baseRequest);
            $resolvedValues = [
                'line_id' => $resolvedContext['line_id'],
                'line' => $resolvedContext['oracle_line'],
                'local' => $suggestedLocal,
                'subinventory' => $suggestedSubinventory,
                'model' => $this->normalizeNullable($suggestedModel),
                'po_number' => $poNumber,
                'destination' => $destination,
            ];
            $finalValues = [
                'job_assembly' => $this->normalizeNullable($data['job_assembly'] ?? null),
                'job_packaging' => $this->normalizeNullable($data['job_packaging'] ?? null),
                'request_type' => $data['request_type'],
                'line_id' => $finalLine->id,
                'line' => $this->normalizeNullable($finalLine->code),
                'local' => $finalLocal,
                'subinventory' => $finalSubinventory,
                'model' => $finalModel,
                'po_number' => $poNumber,
                'destination' => $destination,
                'std_pack_qty' => $standardPack,
                'partial_folio' => $partialFolio,
                'partial_qty' => $partialQty,
            ];

            $revision = MasterRequest::query()->create([
                'parent_master_request_id' => $rootRequestId,
                'revision_number' => $revisionNumber,
                'request_date' => $baseRequest->request_date,
                'week' => $baseRequest->week,
                'line_id' => $finalLine->id,
                'shift_id' => $baseRequest->shift_id,
                'leader_name' => $baseRequest->leader_name,
                'request_source' => $baseRequest->request_source,
                'requested_by_name' => $baseRequest->requested_by_name,
                'requested_by_user_id' => $baseRequest->requested_by_user_id,
                'po_number' => $poNumber,
                'job_assembly' => $finalValues['job_assembly'],
                'job_packaging' => $finalValues['job_packaging'],
                'destination' => $destination,
                'oracle_line' => $finalValues['line'],
                'subinventory' => $finalSubinventory,
                'local' => $finalLocal,
                'model' => $finalModel,
                'folios_from' => (int) $finalFolioNumbers->min(),
                'folios_to' => (int) $finalFolioNumbers->max(),
                'std_pack_qty' => $standardPack,
                'partial_folio' => $partialFolio,
                'partial_qty' => $partialQty,
                'request_type' => $data['request_type'],
                'kind' => 'rework',
                'status' => MasterRequest::STATUS_REQUESTED,
                'rework_reason' => $data['rework_reason'],
                'reworked_by_user_id' => $userId,
                'reworked_by_name' => $userName,
                'reworked_at' => now(),
                'rework_changes' => [
                    'base_request_id' => $baseRequest->id,
                    'original' => $originalValues,
                    'resolved' => $resolvedValues,
                    'final' => $finalValues,
                    'folios' => [
                        'selected' => $selectedFolioNumbers->all(),
                        'added' => $additionalFolioNumbers->all(),
                        'removed' => $originalFolioNumbers->diff($selectedFolioNumbers)->values()->all(),
                    ],
                ],
                'notes' => $data['notes'] ?? null,
            ]);

            $revision->folios()->createMany($finalFolioNumbers->map(
                fn (int $folioNumber): array => [
                    'folio_number' => $folioNumber,
                    'is_partial' => $partialFolio === $folioNumber,
                    'qty_for_folio' => $partialFolio === $folioNumber ? $partialQty : $standardPack,
                    'status' => 'pending',
                ]
            )->all());

            return $revision->load(['originalRequest', 'line', 'shift', 'folios', 'reworkedBy']);
        });
    }

    public function findRevisionForSummary(MasterRequest $revision): MasterRequest
    {
        if (! $revision->isRework()) {
            abort(404);
        }

        return $revision->load([
            'originalRequest.line',
            'line',
            'shift',
            'folios' => fn ($query) => $query->orderBy('folio_number'),
            'reworkedBy:id,name',
            'printBatches' => fn ($query) => $query->latest('printed_at')->latest('id'),
        ]);
    }

    public function createInitialPrintBatch(
        MasterRequest $revision,
        ?int $userId,
        string $userName,
    ): MasterPrintBatch {
        return DB::transaction(function () use ($revision, $userId, $userName): MasterPrintBatch {
            $revision = MasterRequest::query()
                ->with('folios')
                ->lockForUpdate()
                ->findOrFail($revision->id);

            if (! $revision->isRework() || $revision->isCancelled()) {
                throw ValidationException::withMessages([
                    'status' => 'Esta revisión Master no está disponible para impresión.',
                ]);
            }

            $existingBatch = $revision->printBatches()->oldest('id')->first();

            if ($existingBatch) {
                return $existingBatch;
            }

            return $this->masterPrintService->createBatch(
                masterRequest: $revision,
                folioIds: $revision->folios->modelKeys(),
                batchType: 'rework',
                copies: 1,
                reason: $revision->rework_reason,
                printedByUserId: $userId,
                printedByName: $userName,
            );
        });
    }

    private function ensureCanBeReworked(MasterRequest $masterRequest): void
    {
        if ($masterRequest->isCancelled()) {
            throw ValidationException::withMessages([
                'status' => 'Las requisiciones Master canceladas no se pueden retrabajar.',
            ]);
        }

        if (! $masterRequest->canBeReworked()) {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden retrabajar requisiciones Master en proceso o completadas.',
            ]);
        }
    }

    private function firstPrintedSnapshot(MasterRequest $masterRequest): array
    {
        foreach ($masterRequest->printBatches->sortBy([['printed_at', 'asc'], ['id', 'asc']]) as $batch) {
            foreach ($batch->items->sortBy('id') as $item) {
                if (is_array($item->sheet_snapshot) && $item->sheet_snapshot !== []) {
                    return $item->sheet_snapshot;
                }
            }
        }

        return [];
    }

    private function originalValues(MasterRequest $masterRequest): array
    {
        $snapshot = $this->firstPrintedSnapshot($masterRequest);

        return [
            'job_assembly' => $masterRequest->job_assembly,
            'job_packaging' => $masterRequest->job_packaging,
            'request_type' => $masterRequest->request_type,
            'line_id' => $masterRequest->line_id,
            'line' => $masterRequest->line?->code,
            'local' => $masterRequest->local,
            'subinventory' => $masterRequest->subinventory,
            'model' => $masterRequest->model ?: ($snapshot['model'] ?? null),
            'po_number' => $masterRequest->po_number,
            'destination' => $masterRequest->destination,
            'std_pack_qty' => $masterRequest->std_pack_qty,
            'partial_folio' => $masterRequest->partial_folio,
            'partial_qty' => $masterRequest->partial_qty,
        ];
    }

    private function resolveModelFromRequest(MasterRequest $masterRequest): ?string
    {
        $assemblyJob = $masterRequest->job_assembly
            ? $this->oracleJobService->findByJobNumber($masterRequest->job_assembly)
            : null;
        $packagingJob = $masterRequest->job_packaging
            ? $this->oracleJobService->findByJobNumber($masterRequest->job_packaging)
            : null;

        return $this->masterModelMappingService->resolveModelFromJobs(
            (string) $masterRequest->request_type,
            $assemblyJob?->assembly,
            $packagingJob?->assembly,
        );
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
