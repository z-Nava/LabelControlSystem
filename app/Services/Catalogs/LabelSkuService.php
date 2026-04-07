<?php

namespace App\Services\Catalogs;

use App\Models\LabelSku;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LabelSkuService
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return LabelSku::query()
            ->when($search, function ($query) use ($search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('label_part_number', 'like', "%{$search}%")
                    ->orWhere('serial_standard', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('sku')
            ->paginate($perPage)
            ->withQueryString();
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
            'is_active' => (bool) ($data['is_active'] ?? $defaultActive),
            'updated_by_user_id' => $updatedByUserId,
        ];
    }
}
