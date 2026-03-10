<?php

namespace App\Services\Catalogs;

use App\Models\LabelPrintProfile;
use App\Models\LabelPrintProfileVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LabelPrintProfileService
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return LabelPrintProfile::query()
            ->with(['sku', 'template'])
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

    public function create(array $data, ?int $userId = null): LabelPrintProfile
    {
        $profile = LabelPrintProfile::query()->create($this->normalizeData($data, true, $userId));

        if ($profile->is_active) {
            $this->deactivateOthers($profile, $userId);
        }

        $this->createVersionSnapshot($profile, $userId);

        return $profile;
    }

    public function update(LabelPrintProfile $profile, array $data, ?int $userId = null): LabelPrintProfile
    {
        $profile->update($this->normalizeData($data, false, $userId));

        if ($profile->is_active) {
            $this->deactivateOthers($profile, $userId);
        }

        $this->createVersionSnapshot($profile, $userId);

        return $profile;
    }

    public function toggleActive(LabelPrintProfile $profile, ?int $userId = null): LabelPrintProfile
    {
        $profile->update([
            'is_active' => !$profile->is_active,
            'updated_by_user_id' => $userId,
        ]);

        if ($profile->is_active) {
            $this->deactivateOthers($profile, $userId);
            $this->createVersionSnapshot($profile, $userId);
        }

        return $profile;
    }

    private function normalizeData(array $data, bool $defaultActive, ?int $userId): array
    {
        $payload = [
            'label_sku_id' => (int) $data['label_sku_id'],
            'label_type' => $data['label_type'] ?: null,
            'label_template_id' => $data['label_template_id'] ?: null,
            'name' => trim($data['name']),
            'default_printer_name' => $data['default_printer_name'] ?: null,
            'default_printer_ip' => $data['default_printer_ip'] ?: null,
            'dpi' => (int) $data['dpi'],
            'darkness' => $data['darkness'] ?: null,
            'speed' => $data['speed'] ?: null,
            'media_type' => $data['media_type'] ?: null,
            'media_tracking' => $data['media_tracking'] ?: null,
            'print_mode' => $data['print_mode'] ?: null,
            'offset_x' => (int) ($data['offset_x'] ?? 0),
            'offset_y' => (int) ($data['offset_y'] ?? 0),
            'settings' => $data['settings'] ?: null,
            'is_active' => (bool) ($data['is_active'] ?? $defaultActive),
            'updated_by_user_id' => $userId,
        ];

        if ($defaultActive) {
            $payload['created_by_user_id'] = $userId;
        }

        return $payload;
    }

    private function deactivateOthers(LabelPrintProfile $profile, ?int $userId): void
    {
        LabelPrintProfile::query()
            ->where('id', '!=', $profile->id)
            ->where('label_sku_id', $profile->label_sku_id)
            ->where('label_type', $profile->label_type)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_by_user_id' => $userId,
            ]);
    }

    private function createVersionSnapshot(LabelPrintProfile $profile, ?int $userId): void
    {
        $nextVersion = (int) LabelPrintProfileVersion::query()
            ->where('label_print_profile_id', $profile->id)
            ->max('version') + 1;

        LabelPrintProfileVersion::query()->create([
            'label_print_profile_id' => $profile->id,
            'version' => $nextVersion,
            'snapshot' => $profile->fresh()->only([
                'label_sku_id', 'label_type', 'label_template_id', 'name', 'default_printer_name', 'default_printer_ip',
                'dpi', 'darkness', 'speed', 'media_type', 'media_tracking', 'print_mode', 'offset_x', 'offset_y', 'settings', 'is_active',
            ]),
            'created_by_user_id' => $userId,
        ]);
    }
}
