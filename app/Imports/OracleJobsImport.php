<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OracleJobsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // La lógica de guardado NO va aquí.
        // Solo usamos esta clase para entregar los rows normalizados al Service.
    }

    public static function normalizeRow(array $row): array
    {
        $jobNumber = trim((string)($row['job_number'] ?? ''));

        return [
            'job_number' => $jobNumber,
            'line' => self::nullOrString($row['line'] ?? null),
            'job_status' => self::nullOrString($row['job_status'] ?? null),
            'assembly' => self::nullOrString($row['assembly'] ?? null),
            'bom_revision' => self::nullOrString($row['bom_revision'] ?? null),
            'part_description' => self::nullOrString($row['part_description'] ?? null),

            'job_qty' => self::nullOrInt($row['job_qty'] ?? null),
            'qty_completed' => self::nullOrInt($row['qty_completed'] ?? null),
            'quantity_remainder' => self::nullOrInt($row['quantity_remainder'] ?? null),

            'scheduled_start_date' => self::parseExcelDate($row['scheduled_start_date'] ?? null),
            'last_update_date' => self::parseExcelDate($row['last_update_date'] ?? null),

            'job_description' => self::nullOrString($row['job_description'] ?? null),
            'ttl_cust_po' => self::nullOrString($row['ttl_cust_po'] ?? null),
            'ship_to' => self::nullOrString($row['ship_to'] ?? null),
            'ship_code' => self::nullOrString($row['ship_code'] ?? null),
            'ship_address' => self::nullOrString($row['ship_address'] ?? null),
        ];
    }

    private static function nullOrString($value): ?string
    {
        $v = trim((string)$value);
        return $v === '' ? null : $v;
    }

    private static function nullOrInt($value): ?int
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (int)$value;
        return null;
    }

    private static function parseExcelDate($value): ?Carbon
    {
        if ($value === null || $value === '') return null;

        // Si viene como string tipo "1/7/2026 0:00"
        try {
            if (is_string($value)) return Carbon::parse($value);
        } catch (\Throwable $e) {}

        // Si viene como serial de Excel
        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }
        } catch (\Throwable $e) {}

        return null;
    }
}
