<?php

namespace App\Services\Catalogs;

use App\Models\LabelSku;
use Illuminate\Support\Collection;

class LabelSkuService
{
    public function groupedByStandard(?string $search = null): array
    {
        $baseQuery = LabelSku::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('sku', 'like', "%{$search}%")
                        ->orWhere('label_part_number', 'like', "%{$search}%")
                        ->orWhere('serial_standard', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('console_sku', 'like', "%{$search}%")
                        ->orWhere('assembly_part_number', 'like', "%{$search}%")
                        ->orWhere('packaging_part_number', 'like', "%{$search}%")
                        ->orWhere('emea_sku', 'like', "%{$search}%")
                        ->orWhere('anz_sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('sku')
            ->orderBy('label_part_number');

        return [
            'UL' => $this->forStandard($baseQuery, 'UL'),
            'EMEA' => $this->forStandard($baseQuery, 'EMEA'),
            'ANZ' => $this->forStandard($baseQuery, 'ANZ'),
        ];
    }

    public function create(array $data, ?int $updatedByUserId = null): LabelSku
    {
        $payload = $this->normalizeData($data, true, $updatedByUserId);

        return LabelSku::create($payload);
    }

    public function update(LabelSku $labelSku, array $data, ?int $updatedByUserId = null): LabelSku
    {
        $payload = $this->normalizeData($data, false, $updatedByUserId);

        $labelSku->update($payload);

        return $labelSku;
    }

    public function toggleActive(LabelSku $labelSku, ?int $updatedByUserId = null): LabelSku
    {
        $labelSku->update([
            'is_active' => !$labelSku->is_active,
            'updated_by_user_id' => $updatedByUserId,
        ]);

        return $labelSku;
    }

    private function normalizeData(array $data, bool $defaultActive, ?int $updatedByUserId): array
    {
        return [
            'sku' => strtoupper(trim($data['sku'])),
            'serial_standard' => strtoupper(trim((string) ($data['serial_standard'] ?? 'UL'))),
            'label_part_number' => strtoupper(trim($data['label_part_number'])),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'console_sku' => $this->nullableString($data['console_sku'] ?? null),
            'assembly_part_number' => $this->nullableString($data['assembly_part_number'] ?? null),
            'packaging_part_number' => $this->nullableString($data['packaging_part_number'] ?? null),
            'emea_sku' => $this->nullableString($data['emea_sku'] ?? null),
            'anz_sku' => $this->nullableString($data['anz_sku'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? $defaultActive),
            'updated_by_user_id' => $updatedByUserId,
        ];
    }

    private function forStandard($query, string $standard): Collection
    {
        return (clone $query)
            ->where('serial_standard', $standard)
            ->get();
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }
}
