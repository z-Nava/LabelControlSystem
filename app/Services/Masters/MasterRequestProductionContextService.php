<?php

namespace App\Services\Masters;

use App\Models\MasterModelMapping;
use App\Models\OracleJob;
use App\Models\ProductionLine;
use App\Services\Catalogs\StockLocatorService;
use App\Services\Oracle\OracleJobService;
use Illuminate\Validation\ValidationException;

final class MasterRequestProductionContextService
{
    public function __construct(
        private readonly OracleJobService $oracleJobService,
        private readonly StockLocatorService $stockLocatorService,
    ) {}

    /**
     * Describe the catalogs associated with an Oracle line for the job lookup UI.
     *
     * @return array{
     *     line_code: ?string,
     *     production_line_id: ?int,
     *     line_type: ?string,
     *     production_line_configured: bool,
     *     stock_locator: ?string,
     *     subinventory: ?string,
     *     inventory_configured: bool,
     *     allowed_request_types: array<int, string>
     * }
     */
    public function describeOracleLine(mixed $oracleLine): array
    {
        $lineCode = $this->normalize($oracleLine);
        $productionLine = $lineCode !== ''
            ? ProductionLine::query()
                ->where('active', true)
                ->whereRaw('UPPER(TRIM(code)) = ?', [$lineCode])
                ->first()
            : null;
        $inventoryMapping = $lineCode !== ''
            ? $this->stockLocatorService->resolveActiveMappingByOracleLine($lineCode)
            : null;
        $stockLocator = $this->normalizeNullable($inventoryMapping?->stock_locator);
        $subinventory = $this->normalizeNullable($inventoryMapping?->subinventory);

        return [
            'line_code' => $lineCode !== '' ? $lineCode : null,
            'production_line_id' => $productionLine?->id,
            'line_type' => $productionLine?->line_type,
            'production_line_configured' => $productionLine !== null,
            'stock_locator' => $stockLocator,
            'subinventory' => $subinventory,
            'inventory_configured' => $stockLocator !== null && $subinventory !== null,
            'allowed_request_types' => $productionLine
                ? MasterModelMapping::allowedTypesForLineType((string) $productionLine->line_type)
                : [],
        ];
    }

    /**
     * Resolve the server-owned production fields for a Label Room request.
     *
     * @return array{line_id: int, oracle_line: string, local: string, subinventory: string}
     */
    public function resolveForLabelRoom(array $data): array
    {
        $requestType = (string) ($data['request_type'] ?? '');
        $usesPackagingLine = $requestType === MasterModelMapping::TYPE_ASSEMBLY_PACKAGING;
        $jobField = $usesPackagingLine ? 'job_packaging' : 'job_assembly';
        $jobLabel = $usesPackagingLine ? 'Empaque' : 'Ensamble';
        $jobNumber = (string) ($data[$jobField] ?? '');
        $job = $this->oracleJobService->findByJobNumber($jobNumber);

        if (! $job) {
            throw ValidationException::withMessages([
                $jobField => "El Job {$jobLabel} no existe en Oracle Jobs.",
            ]);
        }

        $this->validateOfficialJobRole($job, $usesPackagingLine, $jobField);

        $context = $this->describeOracleLine($job->line);
        $oracleLine = (string) ($context['line_code'] ?? '');

        if ($oracleLine === '') {
            throw ValidationException::withMessages([
                $jobField => "El Job {$jobLabel} {$job->job_number} no tiene una línea registrada en Oracle.",
            ]);
        }

        if (! $context['production_line_configured']) {
            throw ValidationException::withMessages([
                $jobField => "La línea {$oracleLine} reportada por Oracle no existe o no está activa en el catálogo de líneas de producción.",
            ]);
        }

        if (! in_array($requestType, $context['allowed_request_types'], true)) {
            throw ValidationException::withMessages([
                'request_type' => sprintf(
                    'El tipo de hoja Master seleccionado no corresponde al tipo de línea %s de la línea oficial %s.',
                    $context['line_type'] ?: 'desconocido',
                    $oracleLine,
                ),
            ]);
        }

        if ($requestType === MasterModelMapping::TYPE_ORT_ASSEMBLY) {
            $local = $this->normalize($data['local'] ?? '') ?: MasterModelMapping::ORT_DEFAULT_LOCAL;
            $subinventory = $this->normalize($data['subinventory'] ?? '') ?: MasterModelMapping::ORT_DEFAULT_SUBINVENTORY;
        } else {
            $local = (string) ($context['stock_locator'] ?? '');
            $subinventory = (string) ($context['subinventory'] ?? '');

            $this->validateInventoryConfiguration($oracleLine, $local, $subinventory);
        }

        return [
            'line_id' => (int) $context['production_line_id'],
            'oracle_line' => $oracleLine,
            'local' => $local,
            'subinventory' => $subinventory,
        ];
    }

    private function validateOfficialJobRole(OracleJob $job, bool $usesPackagingLine, string $jobField): void
    {
        $isValid = $usesPackagingLine
            ? $this->oracleJobService->isPackagingJob($job)
            : $this->oracleJobService->isAssemblyJob($job);

        if ($isValid) {
            return;
        }

        throw ValidationException::withMessages([
            $jobField => $usesPackagingLine
                ? 'El Job Empaque no corresponde a una operación de Empaque.'
                : 'El Job Ensamble no corresponde a una operación de Ensamble/Subensamble o Motores-Moldeo.',
        ]);
    }

    private function validateInventoryConfiguration(string $oracleLine, string $local, string $subinventory): void
    {
        $errors = [];

        if ($local === '') {
            $errors['local'] = "La línea {$oracleLine} no tiene Local configurado en Locals by Oracle Line.";
        }

        if ($subinventory === '') {
            $errors['subinventory'] = "La línea {$oracleLine} no tiene Subinventory configurado en Locals by Oracle Line.";
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
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
