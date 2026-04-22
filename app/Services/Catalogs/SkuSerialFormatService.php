<?php

namespace App\Services\Catalogs;

use App\Models\SkuSerialFormat;
use App\Support\SerialSchemes;
use App\Support\SerialStandards;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SkuSerialFormatService
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return SkuSerialFormat::query()
            ->when($search, function ($query) use ($search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('serial_standard', 'like', "%{$search}%")
                    ->orWhere('ul_prefix', 'like', "%{$search}%")
                    ->orWhere('ul_serial_break', 'like', "%{$search}%")
                    ->orWhere('ul_plant_code', 'like', "%{$search}%")
                    ->orWhere('emea_prefix', 'like', "%{$search}%")
                    ->orWhere('emea_conformity_code', 'like', "%{$search}%")
                    ->orWhere('emea_plant_code', 'like', "%{$search}%")
                    ->orWhere('anz_customer_tool_code', 'like', "%{$search}%")
                    ->orWhere('separator', 'like', "%{$search}%")
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
                        ->orWhere('serial_standard', 'like', "%{$search}%")
                        ->orWhere('ul_prefix', 'like', "%{$search}%")
                        ->orWhere('ul_serial_break', 'like', "%{$search}%")
                        ->orWhere('ul_plant_code', 'like', "%{$search}%")
                        ->orWhere('emea_prefix', 'like', "%{$search}%")
                        ->orWhere('emea_conformity_code', 'like', "%{$search}%")
                        ->orWhere('emea_plant_code', 'like', "%{$search}%")
                        ->orWhere('anz_customer_tool_code', 'like', "%{$search}%")
                        ->orWhere('separator', 'like', "%{$search}%")
                        ->orWhere('pattern', 'like', "%{$search}%");
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

        $normalized = [
            'sku' => strtoupper(trim($data['sku'])),
            'serial_standard' => $serialStandard,
            'serial_scheme' => trim((string) ($data['serial_scheme'] ?? $defaultScheme)),
            'separator' => $this->normalizeSeparator($data['separator'] ?? ''),
            'year_digits' => (int) ($data['year_digits'] ?? 2),
            'week_digits' => (int) ($data['week_digits'] ?? 2),
            'include_year' => (bool) ($data['include_year'] ?? true),
            'include_week' => (bool) ($data['include_week'] ?? true),
            'pattern' => $this->nullablePattern($data['pattern'] ?? null),
            'unit_length' => (int) $data['unit_length'],
            'is_active' => (bool) ($data['is_active'] ?? $defaultActive),
            'updated_by_user_id' => $updatedByUserId,
        ];

        if (SerialStandards::isInternational($serialStandard)) {
            $normalized['emea_prefix'] = $this->nullableUpper($data['emea_prefix'] ?? null);
            $normalized['emea_conformity_code'] = $this->nullableUpper($data['emea_conformity_code'] ?? null);
            $normalized['emea_plant_code'] = $this->nullableUpper($data['emea_plant_code'] ?? null);
            $normalized['anz_customer_tool_code'] = $serialStandard === SerialStandards::ANZ
                ? $this->nullableUpper($data['anz_customer_tool_code'] ?? null)
                : null;
            $normalized['ul_prefix'] = null;
            $normalized['ul_serial_break'] = null;
            $normalized['ul_plant_code'] = null;
        } else {
            $normalized['ul_prefix'] = $this->nullableUpper($data['ul_prefix'] ?? null);
            $normalized['ul_serial_break'] = $this->nullableUpper($data['ul_serial_break'] ?? null);
            $normalized['ul_plant_code'] = $this->nullableUpper($data['ul_plant_code'] ?? null);
            $normalized['emea_prefix'] = null;
            $normalized['emea_conformity_code'] = null;
            $normalized['emea_plant_code'] = null;
            $normalized['anz_customer_tool_code'] = null;
        }

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
