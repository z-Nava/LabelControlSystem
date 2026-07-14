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
            ->with(['ulConfig', 'emeaConfig', 'anzConfig'])
            ->when($search, function ($query) use ($search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('serial_standard', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('ulConfig', fn ($q) => $q
                        ->where('prefix', 'like', "%{$search}%")
                        ->orWhere('serial_break', 'like', "%{$search}%")
                        ->orWhere('plant_code', 'like', "%{$search}%"))
                    ->orWhereHas('emeaConfig', fn ($q) => $q
                        ->where('prefix_value', 'like', "%{$search}%")
                        ->orWhere('conformity_code', 'like', "%{$search}%"))
                    ->orWhereHas('anzConfig', fn ($q) => $q
                        ->where('product_prefix', 'like', "%{$search}%")
                        ->orWhere('tool_version_letter', 'like', "%{$search}%")
                        ->orWhere('customer_tool_code', 'like', "%{$search}%"))
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
            ->with(['ulConfig', 'emeaConfig', 'anzConfig'])
            ->where('serial_standard', strtoupper($serialStandard))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('ulConfig', fn ($q) => $q
                            ->where('prefix', 'like', "%{$search}%")
                            ->orWhere('serial_break', 'like', "%{$search}%"))
                        ->orWhereHas('emeaConfig', fn ($q) => $q
                            ->where('prefix_value', 'like', "%{$search}%")
                            ->orWhere('conformity_code', 'like', "%{$search}%"))
                        ->orWhereHas('anzConfig', fn ($q) => $q
                            ->where('product_prefix', 'like', "%{$search}%")
                            ->orWhere('tool_version_letter', 'like', "%{$search}%")
                            ->orWhere('customer_tool_code', 'like', "%{$search}%"));
                });
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('sku')
            ->get();
    }

    public function create(array $data, ?int $updatedByUserId = null): SkuSerialFormat
    {
        $normalizedData = $this->stripLegacyMarketColumns(
            $this->normalizeData($data, true, $updatedByUserId)
        );
        $format = SkuSerialFormat::query()->create($normalizedData);
        $this->syncMarketConfig($format, $data);

        return $format->fresh(['ulConfig', 'emeaConfig', 'anzConfig']);
    }

    public function update(SkuSerialFormat $format, array $data, ?int $updatedByUserId = null): SkuSerialFormat
    {
        $normalizedData = $this->stripLegacyMarketColumns(
            $this->normalizeData($data, false, $updatedByUserId)
        );
        $format->update($normalizedData);
        $this->syncMarketConfig($format, $data);

        return $format->fresh(['ulConfig', 'emeaConfig', 'anzConfig']);
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
            'market' => $serialStandard,
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
            $normalized['separator'] = '';
            $normalized['year_digits'] = 2;
            $normalized['week_digits'] = 2;
            $normalized['include_year'] = true;
            $normalized['include_week'] = true;
            $normalized['pattern'] = null;
            $normalized['qr_payload_format'] = 'serial_only';

            $ulPrefix = $this->nullableUpper($data['ul_prefix'] ?? null);
            $ulPlantCode = $this->nullableUpper($data['ul_plant_code'] ?? null);

            $normalized['ul_prefix'] = $ulPrefix;
            $normalized['ul_prefix_length'] = $this->stringLength($ulPrefix);
            $normalized['ul_serial_break'] = $this->nullableUpper($data['ul_serial_break'] ?? null);
            $normalized['ul_plant_code'] = $ulPlantCode;
            $normalized['ul_use_plant_code'] = true;
            $normalized['serial_length'] = $this->resolveUlSerialLength($ulPrefix, $ulPlantCode, $unitDigits);

            $normalized['emea_prefix'] = null;
            $normalized['emea_prefix_source'] = null;
            $normalized['emea_prefix_digits'] = null;
            $normalized['emea_conformity_code'] = null;
            $normalized['emea_plant_code'] = null;
            $normalized['emea_unit_digits'] = null;
            $normalized['emea_declaration_required'] = false;
            $normalized['emea_serial_print_format'] = null;

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
            $emeaUnitDigits = 6;

            $normalized['date_mode'] = 'month_year';
            $normalized['month_letter_enabled'] = true;
            $normalized['serial_length'] = null;
            $normalized['emea_prefix'] = $this->nullableUpper($data['emea_prefix'] ?? null);
            $normalized['emea_prefix_source'] = null;
            $normalized['emea_prefix_digits'] = null;
            $normalized['emea_conformity_code'] = $this->nullableUpper($data['emea_conformity_code'] ?? null);
            $normalized['emea_plant_code'] = null;
            $normalized['emea_unit_digits'] = $emeaUnitDigits;
            $normalized['emea_declaration_required'] = false;
            $normalized['emea_serial_print_format'] = null;

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
        $normalized['emea_serial_print_format'] = null;

        $normalized['unit_length'] = $anzUnitDigits;
        $normalized['unit_digits'] = $anzUnitDigits;

        $normalized['ul_prefix'] = null;
        $normalized['ul_prefix_length'] = null;
        $normalized['ul_serial_break'] = null;
        $normalized['ul_plant_code'] = null;
        $normalized['ul_use_plant_code'] = false;

        return $normalized;
    }

    private function syncMarketConfig(SkuSerialFormat $format, array $data): void
    {
        $standard = strtoupper((string) $format->serial_standard);
        $pattern = $this->nullablePattern($data['pattern'] ?? null);
        $resetScope = trim((string) ($data['reset_scope'] ?? ($standard === SerialStandards::UL ? 'weekly' : 'monthly')));

        if ($standard === SerialStandards::UL) {
            $ulPrefix = $this->nullableUpper($data['ul_prefix'] ?? null);
            $format->ulConfig()->updateOrCreate(
                ['sku_serial_format_id' => $format->id],
                [
                    'prefix' => $ulPrefix,
                    'prefix_length' => $this->stringLength($ulPrefix),
                    'serial_break' => $this->nullableUpper($data['ul_serial_break'] ?? null),
                    'plant_code' => $this->nullableUpper($data['ul_plant_code'] ?? null),
                    'use_plant_code' => true,
                    'reset_scope' => 'weekly',
                    'pattern' => null,
                ]
            );
            $format->emeaConfig()->delete();
            $format->anzConfig()->delete();

            return;
        }

        if ($standard === SerialStandards::EMEA) {
            $format->emeaConfig()->updateOrCreate(
                ['sku_serial_format_id' => $format->id],
                [
                    'prefix_value' => $this->nullableUpper($data['emea_prefix'] ?? null),
                    'conformity_code' => $this->nullableUpper($data['emea_conformity_code'] ?? null),
                    'unit_digits' => 6,
                    'pattern' => $pattern,
                ]
            );
            $format->ulConfig()->delete();
            $format->anzConfig()->delete();

            return;
        }

        $anzUnitDigits = (int) ($data['anz_unit_digits'] ?? $data['unit_digits'] ?? 5);
        $format->anzConfig()->updateOrCreate(
            ['sku_serial_format_id' => $format->id],
            [
                'product_prefix' => $this->nullableUpper($data['anz_product_prefix'] ?? null),
                'product_prefix_length' => $this->nullableInt($data['emea_prefix_digits'] ?? null),
                'tool_version_letter' => $this->nullableUpper($data['anz_tool_version'] ?? null),
                'tool_version_required' => (bool) ($data['anz_tool_version_required'] ?? true),
                'customer_tool_code' => $this->nullableUpper($data['anz_customer_tool_code'] ?? null),
                'customer_tool_code_required' => false,
                'unit_digits' => $anzUnitDigits,
                'qr_separator' => $this->nullableString($data['anz_qr_separator'] ?? ' | '),
                'include_customer_tool_code_in_qr' => (bool) ($data['anz_include_customer_tool_code_in_qr'] ?? true),
                'print_format' => $this->nullableString($data['anz_serial_print_format'] ?? 'spaces'),
                'reset_scope' => $resetScope ?: 'monthly',
                'pattern' => $pattern,
                'qr_pattern' => null,
            ]
        );
        $format->ulConfig()->delete();
        $format->emeaConfig()->delete();
    }

    private function stripLegacyMarketColumns(array $data): array
    {
        foreach ([
        'ul_prefix',
        'ul_prefix_length',
        'ul_serial_break',
        'ul_plant_code',
        'ul_use_plant_code',
        'emea_prefix',
        'emea_prefix_source',
        'emea_prefix_digits',
        'emea_conformity_code',
        'emea_plant_code',
        'emea_unit_digits',
        'emea_declaration_required',
        'emea_serial_print_format',
        'anz_customer_tool_code',
        'anz_product_prefix',
        'anz_tool_version',
        'anz_tool_version_required',
        'anz_unit_digits',
        'anz_qr_separator',
        'anz_include_customer_tool_code_in_qr',
        'anz_serial_print_format',
    ] as $legacyColumn) {
            unset($data[$legacyColumn]);
        }

        return $data;
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

    private function stringLength(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return strlen($value);
    }

    private function resolveUlSerialLength(?string $prefix, ?string $plantCode, int $unitDigits): ?int
    {
        if ($prefix === null || $plantCode === null) {
            return null;
        }

        return strlen($prefix) + 1 + strlen($plantCode) + 2 + 2 + $unitDigits;
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
