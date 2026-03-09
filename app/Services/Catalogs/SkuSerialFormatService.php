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
                    ->orWhere('prefix', 'like', "%{$search}%")
                    ->orWhere('plant_code', 'like', "%{$search}%")
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
            'prefix' => $this->nullableUpper($data['prefix'] ?? null),
            'serial_break' => $this->nullableUpper($data['serial_break'] ?? null),
            'plant_code' => $this->nullableUpper($data['plant_code'] ?? null),
            'pattern' => trim((string) $data['pattern']),
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
}
