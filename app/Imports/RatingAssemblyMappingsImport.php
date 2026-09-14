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
     * @return array{rating_part_number: ?string, assembly_part_number: ?string, market: ?string, serial_part_number?: ?string, shipping_part_number?: ?string, inner_part_number?: ?string}
     */
    public static function normalizeRow(array $row): array
    {
        $row = array_change_key_case($row, CASE_LOWER);

        $data = [
            'rating_part_number' => self::cleanIdentifier(self::first($row, [
                'np_rating', 'rating_pn', 'rating_np', 'rating_part_number', 'rating', 'np_de_rating', 'numero_de_parte_rating',
            ])),
            'assembly_part_number' => self::cleanIdentifier(self::first($row, [
                'ensamble', 'assembly_part_number', 'assembly', 'empaque', 'np_empaque', 'np_ensamble',
            ])),
            'market' => SerialStandards::fromAlias(self::first($row, [
                'mercado', 'market', 'serial_standard', 'estandar',
            ])),
        ];

        foreach ([
            'serial_part_number' => ['serial_np', 'np_serial', 'serial_pn', 'serial_part_number'],
            'shipping_part_number' => ['shipping_np', 'np_shipping', 'shipping_pn', 'shipping_part_number'],
            'inner_part_number' => ['inner_np', 'np_inner', 'inner_pn', 'inner_part_number'],
        ] as $field => $aliases) {
            // Older files must not erase catalog fields they do not include.
            if (array_intersect($aliases, array_keys($row))) {
                $data[$field] = self::cleanIdentifier(self::first($row, $aliases));
            }
        }

        return $data;
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
