<?php

namespace App\Imports;

use App\Models\MasterModelMapping;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MasterModelMappingsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Intencionalmente vacío; la persistencia vive en el servicio.
    }

    public static function normalizeRow(array $row): array
    {
        $normalized = array_change_key_case($row, CASE_LOWER);

        $np = self::cleanValue($normalized['np'] ?? $normalized['numero_de_parte'] ?? null);
        $sku = self::cleanValue($normalized['sku'] ?? null);
        $sheet = self::normalizeSheetType($normalized['hoja_master'] ?? $normalized['master_sheet'] ?? null);

        return [
            'np' => $np,
            'sku' => $sku,
            'master_sheet_type' => $sheet,
        ];
    }

    public static function normalizeSheetType(?string $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));
        $normalized = str_replace(['_', '.'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?: '';

        return match ($normalized) {
            'ENSAMBLE', 'HOJA MASTER ENSAMBLE' => MasterModelMapping::TYPE_ASSEMBLY,
            'ENSAMBLE - EMPAQUE', 'ENSAMBLE EMPAQUE', 'HOJA MASTER ENSAMBLE Y EMPAQUE' => MasterModelMapping::TYPE_ASSEMBLY_PACKAGING,
            'BATERIAS', 'BATERÍAS', 'ENSAMBLE BATERIAS', 'ENSAMBLE BATERÍAS' => MasterModelMapping::TYPE_BATTERIES_ASSEMBLY,
            'MOTORES - MODELO', 'MOTORES MODELO', 'MOTORES - MOLDEO', 'MOTORES MOLDEO' => MasterModelMapping::TYPE_MOTORS_MOLDING,
            default => null,
        };
    }

    private static function cleanValue(mixed $value): ?string
    {
        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : strtoupper($stringValue);
    }
}
