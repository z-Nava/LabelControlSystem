<?php

namespace App\Services\Labels;

use Illuminate\Support\Collection;

class LpkJobReservationCalculator
{
    /**
     * A Job can use several physical label types in the same LPK. Its production
     * reservation is the largest requested quantity, never the sum of those labels.
     *
     * @param  iterable<int, array<string, mixed>>  $labelGroups
     * @return Collection<string, int>
     */
    public function calculate(iterable $labelGroups): Collection
    {
        return collect($labelGroups)
            ->flatMap(fn (array $group): array => $group['items'] ?? [])
            ->filter(fn ($item): bool => is_array($item) && filled($item['job_number'] ?? null))
            ->groupBy(fn (array $item): string => strtoupper(trim((string) $item['job_number'])))
            ->map(fn (Collection $items): int => (int) $items->max('quantity'));
    }
}
