<?php

namespace App\Services\Catalogs;

use App\Models\LabelTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LabelTemplateService
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return LabelTemplate::query()
            ->with('sku')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('label_type', 'like', "%{$search}%")
                    ->orWhereHas('sku', fn ($q) => $q->where('sku', 'like', "%{$search}%"));
            })
            ->orderBy('is_active', 'desc')
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, ?int $userId = null): LabelTemplate
    {
        $payload = $this->normalizeData($data, true, $userId);
        $payload['version'] = $this->nextVersion(
            (int) ($payload['label_sku_id'] ?? 0),
            $payload['label_type'],
            $payload['serial_standard'] ?? 'UL',
        );

        $template = LabelTemplate::query()->create($payload);

        if ($template->is_active) {
            $this->deactivateOthers($template);
        }

        return $template;
    }

    public function update(LabelTemplate $template, array $data, ?int $userId = null): LabelTemplate
    {
        $payload = $this->normalizeData($data, false, $userId);
        $template->update($payload);

        if ($template->is_active) {
            $this->deactivateOthers($template);
        }

        return $template;
    }

    public function toggleActive(LabelTemplate $template, ?int $userId = null): LabelTemplate
    {
        $template->update([
            'is_active' => !$template->is_active,
            'updated_by_user_id' => $userId,
        ]);

        if ($template->is_active) {
            $this->deactivateOthers($template);
        }

        return $template;
    }

    private function normalizeData(array $data, bool $defaultActive, ?int $userId): array
    {
        $serialLayout = $this->normalizeSerialLayout($data['serial_layout'] ?? null);
        $meta = $this->normalizeMeta($data['meta'] ?? null, $serialLayout);

        $payload = [
            'name' => trim($data['name']),
            'label_type' => trim($data['label_type']),
            'serial_standard' => strtoupper(trim((string) ($data['serial_standard'] ?? 'UL'))),
            'label_sku_id' => $data['label_sku_id'] ?: null,
            'dpi' => (int) $data['dpi'],
            'width_mm' => $data['width_mm'] ?: null,
            'height_mm' => $data['height_mm'] ?: null,
            'zpl' => trim($data['zpl']),
            'serial_layout' => $serialLayout,
            'meta' => $meta,
            'is_active' => (bool) ($data['is_active'] ?? $defaultActive),
            'updated_by_user_id' => $userId,
        ];

        if ($defaultActive) {
            $payload['created_by_user_id'] = $userId;
        }

        return $payload;
    }

    private function normalizeSerialLayout(mixed $serialLayout): ?array
    {
        return is_array($serialLayout) && $serialLayout !== []
            ? $serialLayout
            : null;
    }

    private function normalizeMeta(mixed $meta, ?array $serialLayout): ?array
    {
        $normalizedMeta = is_array($meta) ? $meta : [];

        if ($serialLayout !== null) {
            $normalizedMeta['serial_layout'] = $serialLayout;
        }

        return $normalizedMeta !== [] ? $normalizedMeta : null;
    }

    private function nextVersion(int $skuId, string $labelType, string $standard): int
    {
        return (int) LabelTemplate::query()
            ->where('label_sku_id', $skuId ?: null)
            ->where('label_type', $labelType)
            ->where('serial_standard', strtoupper(trim($standard)))
            ->max('version') + 1;
    }

    private function deactivateOthers(LabelTemplate $template): void
    {
        LabelTemplate::query()
            ->where('id', '!=', $template->id)
            ->where('label_type', $template->label_type)
            ->where('serial_standard', $template->serial_standard)
            ->where('label_sku_id', $template->label_sku_id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'updated_by_user_id' => $template->updated_by_user_id]);
    }
}
