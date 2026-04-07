<?php

namespace App\Services\Catalogs;

use App\Models\SkuSerialFormat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SkuSerialFormatService
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return SkuSerialFormat::query()
            ->when($search, function ($query) use ($search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('serial_standard', 'like', "%{$search}%")
                    ->orWhere('prefix', 'like', "%{$search}%")
                    ->orWhere('serial_break', 'like', "%{$search}%")
                    ->orWhere('plant_code', 'like', "%{$search}%")
                    ->orWhere('separator', 'like', "%{$search}%")
                    ->orWhere('pattern', 'like', "%{$search}%");
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('sku')
            ->paginate($perPage)
            ->withQueryString();
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
        return [
            'sku' => strtoupper(trim($data['sku'])),
            'serial_standard' => strtoupper(trim((string) ($data['serial_standard'] ?? 'UL'))),
            'serial_scheme' => trim((string) ($data['serial_scheme'] ?? 'ul_standard')),
            'prefix' => $this->nullableUpper($data['prefix'] ?? null),
            'serial_break' => $this->nullableUpper($data['serial_break'] ?? null),
            'plant_code' => $this->nullableUpper($data['plant_code'] ?? null),
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
        return trim((string) $value);
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
