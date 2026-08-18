<?php

namespace App\Services\Catalogs;

use App\Models\MasterAssemblyClassificationRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MasterAssemblyClassificationRuleService
{
    public function paginate(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        return MasterAssemblyClassificationRule::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('prefix', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('match_field', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('active')
            ->orderBy('match_field')
            ->orderBy('prefix')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): MasterAssemblyClassificationRule
    {
        return MasterAssemblyClassificationRule::create($this->normalize($data, true));
    }

    public function update(MasterAssemblyClassificationRule $rule, array $data): MasterAssemblyClassificationRule
    {
        $rule->update($this->normalize($data, false));

        return $rule;
    }

    public function toggleActive(MasterAssemblyClassificationRule $rule): MasterAssemblyClassificationRule
    {
        $rule->update(['active' => ! $rule->active]);

        return $rule;
    }

    private function normalize(array $data, bool $defaultActive): array
    {
        return [
            'match_field' => strtolower(trim((string) $data['match_field'])),
            'prefix' => strtoupper(trim((string) $data['prefix'])),
            'description' => $this->normalizeNullable($data['description'] ?? null),
            'allows_assembly' => (bool) ($data['allows_assembly'] ?? false),
            'allows_packaging' => (bool) ($data['allows_packaging'] ?? false),
            'active' => (bool) ($data['active'] ?? $defaultActive),
        ];
    }

    private function normalizeNullable(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
