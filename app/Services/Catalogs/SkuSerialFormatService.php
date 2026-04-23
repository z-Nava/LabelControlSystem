<?php

namespace App\Services\Catalogs;

use App\Models\SkuSerialFormat;
use App\Support\SerialSchemes;
use App\Support\SerialStandards;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SkuSerialFormatService
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return SkuSerialFormat::query()
            ->when($search, function ($query) use ($search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('serial_standard', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ul_prefix', 'like', "%{$search}%")
                    ->orWhere('ul_serial_break', 'like', "%{$search}%")
                    ->orWhere('ul_plant_code', 'like', "%{$search}%")
                    ->orWhere('emea_prefix', 'like', "%{$search}%")
                    ->orWhere('emea_conformity_code', 'like', "%{$search}%")
                    ->orWhere('anz_product_prefix', 'like', "%{$search}%")
                    ->orWhere('anz_tool_version', 'like', "%{$search}%")
                    ->orWhere('anz_customer_tool_code', 'like', "%{$search}%")
                    ->orWhere('pattern', 'like', "%{$search}%");
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('sku')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listByStandard(string $serialStandard, ?string $search = null): Collection
    {
        return SkuSerialFormat::query()
            ->where('serial_standard', strtoupper($serialStandard))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('ul_prefix', 'like', "%{$search}%")
                        ->orWhere('ul_serial_break', 'like', "%{$search}%")
                        ->orWhere('emea_prefix', 'like', "%{$search}%")
                        ->orWhere('emea_conformity_code', 'like', "%{$search}%")
                        ->orWhere('anz_product_prefix', 'like', "%{$search}%")
                        ->orWhere('anz_tool_version', 'like', "%{$search}%")
                        ->orWhere('anz_customer_tool_code', 'like', "%{$search}%");
                });
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('sku')
            ->get();
    }

    public function create(array $data, ?int $updatedByUserId = null): SkuSerialFormat
    {
        return SkuSerialFormat::query()->create($this->normalizeData($data, true, $updatedByUserId));
    }

    public function update(SkuSerialFormat $format, array $data, ?int $updatedByUserId = null): SkuSerialFormat
    {
        $format->update($this->normalizeData($data, false, $updatedByUserId));

        return $format;
    }

    public function toggleActive(SkuSerialFormat $format, ?int $updatedByUserId = null): SkuSerialFormat
    {
        $format->update([
            'is_active' => !$format->is_active,
            'updated_by_user_id' => $updatedByUserId,
        ]);

        return $format;
    }

    private function normalizeData(array $data, bool $defaultActive, ?int $updatedByUserId): array
    {
        $serialStandard = SerialStandards::normalize((string) ($data['serial_standard'] ?? SerialStandards::UL));
        $defaultScheme = SerialStandards::isInternational($serialStandard)
            ? ($serialStandard === SerialStandards::ANZ ? SerialSchemes::ANZ_STANDARD : SerialSchemes::EMEA_RATING)
            : SerialSchemes::UL_STANDARD;

        $unitDigits = (int) ($data['unit_digits'] ?? $data['unit_length'] ?? 5);

        $normalized = [
            'sku' => strtoupper(trim($data['sku'])),
            'serial_standard' => $serialStandard,
            'serial_scheme' => trim((string) ($data['serial_scheme'] ?? $defaultScheme)),
            'description' => $this->nullableString($data['description'] ?? null),
            'serial_length' => $this->nullableInt($data['serial_length'] ?? null),
            'qr_payload_format' => trim((string) ($data['qr_payload_format'] ?? 'serial_only')),
            'date_mode' => trim((string) ($data['date_mode'] ?? 'year_week')),
            'month_letter_enabled' => (bool) ($data['month_letter_enabled'] ?? false),
            'month_letter_map' => $this->nullableString($data['month_letter_map'] ?? null),
            'separator' => $this->normalizeSeparator($data['separator'] ?? ''),
            'year_digits' => (int) ($data['year_digits'] ?? 2),
            'week_digits' => (int) ($data['week_digits'] ?? 2),
            'include_year' => (bool) ($data['include_year'] ?? true),
            'include_week' => (bool) ($data['include_week'] ?? true),
            'pattern' => $this->nullablePattern($data['pattern'] ?? null),
            'unit_length' => $unitDigits,
            'unit_digits' => $unitDigits,
            'is_active' => (bool) ($data['is_active'] ?? $defaultActive),
            'updated_by_user_id' => $updatedByUserId,
        ];

        if ($serialStandard === SerialStandards::UL) {
            $normalized['date_mode'] = 'year_week';
            $normalized['month_letter_enabled'] = false;
            $normalized['ul_prefix'] = $this->nullableUpper($data['ul_prefix'] ?? null);
            $normalized['ul_prefix_length'] = $this->nullableInt($data['ul_prefix_length'] ?? null);
            $normalized['ul_serial_break'] = $this->nullableUpper($data['ul_serial_break'] ?? null);
            $normalized['ul_plant_code'] = $this->nullableUpper($data['ul_plant_code'] ?? null);
            $normalized['ul_use_plant_code'] = (bool) ($data['ul_use_plant_code'] ?? true);

            $normalized['emea_prefix'] = null;
            $normalized['emea_prefix_source'] = null;
            $normalized['emea_prefix_digits'] = null;
            $normalized['emea_conformity_code'] = null;
            $normalized['emea_plant_code'] = null;
            $normalized['emea_unit_digits'] = null;
            $normalized['emea_declaration_required'] = false;

            $normalized['anz_customer_tool_code'] = null;
            $normalized['anz_product_prefix'] = null;
            $normalized['anz_tool_version'] = null;
            $normalized['anz_tool_version_required'] = false;
            $normalized['anz_unit_digits'] = null;
            $normalized['anz_qr_separator'] = null;
            $normalized['anz_include_customer_tool_code_in_qr'] = false;
            $normalized['anz_serial_print_format'] = null;

            return $normalized;
        }

        if ($serialStandard === SerialStandards::EMEA) {
            $emeaUnitDigits = (int) ($data['emea_unit_digits'] ?? $unitDigits ?: 6);

            $normalized['date_mode'] = 'month_year';
            $normalized['month_letter_enabled'] = true;
            $normalized['emea_prefix'] = $this->nullableUpper($data['emea_prefix'] ?? null);
            $normalized['emea_prefix_source'] = $this->nullableString($data['emea_prefix_source'] ?? 'fixed_value');
            $normalized['emea_prefix_digits'] = $this->nullableInt($data['emea_prefix_digits'] ?? null);
            $normalized['emea_conformity_code'] = $this->nullableUpper($data['emea_conformity_code'] ?? null);
            $normalized['emea_plant_code'] = $this->nullableUpper($data['emea_plant_code'] ?? null);
            $normalized['emea_unit_digits'] = $emeaUnitDigits;
            $normalized['emea_declaration_required'] = (bool) ($data['emea_declaration_required'] ?? false);

            $normalized['unit_length'] = $emeaUnitDigits;
            $normalized['unit_digits'] = $emeaUnitDigits;

            $normalized['ul_prefix'] = null;
            $normalized['ul_prefix_length'] = null;
            $normalized['ul_serial_break'] = null;
            $normalized['ul_plant_code'] = null;
            $normalized['ul_use_plant_code'] = false;

            $normalized['anz_customer_tool_code'] = null;
            $normalized['anz_product_prefix'] = null;
            $normalized['anz_tool_version'] = null;
            $normalized['anz_tool_version_required'] = false;
            $normalized['anz_unit_digits'] = null;
            $normalized['anz_qr_separator'] = null;
            $normalized['anz_include_customer_tool_code_in_qr'] = false;
            $normalized['anz_serial_print_format'] = null;

            return $normalized;
        }

        $anzUnitDigits = (int) ($data['anz_unit_digits'] ?? $unitDigits ?: 5);
        $normalized['date_mode'] = 'month_year';
        $normalized['month_letter_enabled'] = true;

        $normalized['anz_customer_tool_code'] = $this->nullableUpper($data['anz_customer_tool_code'] ?? null);
        $normalized['anz_product_prefix'] = $this->nullableUpper($data['anz_product_prefix'] ?? null);
        $normalized['anz_tool_version'] = $this->nullableUpper($data['anz_tool_version'] ?? null);
        $normalized['anz_tool_version_required'] = (bool) ($data['anz_tool_version_required'] ?? true);
        $normalized['anz_unit_digits'] = $anzUnitDigits;
        $normalized['anz_qr_separator'] = $this->nullableString($data['anz_qr_separator'] ?? ' | ');
        $normalized['anz_include_customer_tool_code_in_qr'] = (bool) ($data['anz_include_customer_tool_code_in_qr'] ?? true);
        $normalized['anz_serial_print_format'] = $this->nullableString($data['anz_serial_print_format'] ?? 'spaces');

        // Compatibilidad con formateador actual basado en campos EMEA.
        $normalized['emea_prefix'] = $normalized['anz_product_prefix'];
        $normalized['emea_conformity_code'] = $normalized['anz_tool_version'];
        $normalized['emea_plant_code'] = null;
        $normalized['emea_prefix_source'] = 'fixed_value';
        $normalized['emea_prefix_digits'] = $this->nullableInt($data['emea_prefix_digits'] ?? null);
        $normalized['emea_unit_digits'] = $anzUnitDigits;
        $normalized['emea_declaration_required'] = false;

        $normalized['unit_length'] = $anzUnitDigits;
        $normalized['unit_digits'] = $anzUnitDigits;

        $normalized['ul_prefix'] = null;
        $normalized['ul_prefix_length'] = null;
        $normalized['ul_serial_break'] = null;
        $normalized['ul_plant_code'] = null;
        $normalized['ul_use_plant_code'] = false;

        return $normalized;
    }

    private function nullableUpper(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : strtoupper($value);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function normalizeSeparator(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value === '__SPACE__') {
            return ' ';
        }

        return in_array($value, ['', ' ', '-', '_', '|'], true) ? $value : '';
    }

    private function nullablePattern(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
