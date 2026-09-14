<?php

namespace App\Imports;

use App\Support\SerialStandards;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RatingAssemblyMappingsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        // La persistencia y la transacción pertenecen al servicio de catálogo.
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{rating_part_number: ?string, assembly_part_number: ?string, market: ?string}
     */
    public static function normalizeRow(array $row): array
    {
        $row = array_change_key_case($row, CASE_LOWER);

        return [
            'rating_part_number' => self::cleanIdentifier(self::first($row, [
                'np_rating', 'rating_part_number', 'rating', 'np_de_rating', 'numero_de_parte_rating',
            ])),
            'assembly_part_number' => self::cleanIdentifier(self::first($row, [
                'ensamble', 'assembly_part_number', 'assembly', 'empaque', 'np_empaque', 'np_ensamble',
            ])),
            'market' => SerialStandards::fromAlias(self::first($row, [
                'mercado', 'market', 'serial_standard', 'estandar',
            ])),
        ];
    }

    /** @param array<string, mixed> $row */
    private static function first(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && filled($row[$key])) {
                return $row[$key];
            }
        }

        return null;
    }

    private static function cleanIdentifier(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        if (preg_match('/^\d+\.0$/', $normalized)) {
            $normalized = substr($normalized, 0, -2);
        }

        return $normalized === '' ? null : $normalized;
    }
}
