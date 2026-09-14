<?php

namespace App\Services\Catalogs;

use App\Imports\RatingAssemblyMappingsImport;
use App\Models\RatingAssemblyMapping;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class RatingAssemblyMappingService
{
    public function paginate(?string $search = null, ?string $market = null, ?bool $active = null, int $perPage = 25): LengthAwarePaginator
    {
        return RatingAssemblyMapping::query()
            ->with('updatedByUser')
            ->when($search, fn ($query, $term) => $query->where(fn ($nested) => $nested
                ->where('rating_part_number', 'like', "%{$term}%")
                ->orWhere('assembly_part_number', 'like', "%{$term}%")
                ->orWhere('serial_part_number', 'like', "%{$term}%")
                ->orWhere('shipping_part_number', 'like', "%{$term}%")
                ->orWhere('inner_part_number', 'like', "%{$term}%")))
            ->when($market, fn ($query, $value) => $query->where('market', $value))
            ->when($active !== null, fn ($query) => $query->where('active', $active))
            ->orderByDesc('active')
            ->orderBy('assembly_part_number')
            ->orderBy('rating_part_number')
            ->orderBy('market')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, ?int $userId): RatingAssemblyMapping
    {
        return RatingAssemblyMapping::query()->create($this->payload($data, $userId, true));
    }

    public function update(RatingAssemblyMapping $mapping, array $data, ?int $userId): RatingAssemblyMapping
    {
        $mapping->update($this->payload($data, $userId, $mapping->active));

        return $mapping->refresh();
    }

    public function toggleActive(RatingAssemblyMapping $mapping, ?int $userId): RatingAssemblyMapping
    {
        $mapping->update([
            'active' => ! $mapping->active,
            'updated_by_user_id' => $userId,
        ]);

        return $mapping->refresh();
    }

    /** @return Collection<int, array{rating_part_number: string, market: string}> */
    public function activeOptionsForAssembly(?string $assemblyPartNumber): Collection
    {
        $assemblyPartNumber = $this->normalize($assemblyPartNumber);

        if (! $assemblyPartNumber) {
            return collect();
        }

        return RatingAssemblyMapping::query()
            ->active()
            ->where('assembly_part_number', $assemblyPartNumber)
            ->orderBy('rating_part_number')
            ->orderBy('market')
            ->get(['rating_part_number', 'market'])
            ->map(fn (RatingAssemblyMapping $mapping): array => [
                'rating_part_number' => $mapping->rating_part_number,
                'market' => $mapping->market,
            ]);
    }

    /**
     * @param  iterable<mixed>  $assemblyPartNumbers
     * @return array<string, Collection<int, array{rating_part_number: string, market: string}>>
     */
    public function activeOptionsForAssemblies(iterable $assemblyPartNumbers): array
    {
        $assemblies = collect($assemblyPartNumbers)
            ->map(fn (mixed $part): ?string => $this->normalize($part))
            ->filter()
            ->unique()
            ->values();

        if ($assemblies->isEmpty()) {
            return [];
        }

        return RatingAssemblyMapping::query()
            ->active()
            ->whereIn('assembly_part_number', $assemblies)
            ->orderBy('rating_part_number')
            ->orderBy('market')
            ->get(['assembly_part_number', 'rating_part_number', 'market'])
            ->groupBy('assembly_part_number')
            ->map(fn (Collection $mappings): Collection => $mappings->map(fn (RatingAssemblyMapping $mapping): array => [
                'rating_part_number' => $mapping->rating_part_number,
                'market' => $mapping->market,
            ])->values())
            ->all();
    }

    /**
     * The market is inferred only when every selected Rating NP has one exact active mapping
     * for the Job assembly and all matches agree. Manual or ambiguous inputs remain unresolved.
     *
     * @param  iterable<mixed>  $ratingPartNumbers
     */
    public function resolveMarket(?string $assemblyPartNumber, iterable $ratingPartNumbers): ?string
    {
        $ratings = collect($ratingPartNumbers)
            ->map(fn (mixed $part): ?string => $this->normalize($part))
            ->filter()
            ->unique()
            ->values();
        $assemblyPartNumber = $this->normalize($assemblyPartNumber);

        if (! $assemblyPartNumber || $ratings->isEmpty()) {
            return null;
        }

        $matches = RatingAssemblyMapping::query()
            ->active()
            ->where('assembly_part_number', $assemblyPartNumber)
            ->whereIn('rating_part_number', $ratings)
            ->get(['rating_part_number', 'market'])
            ->groupBy('rating_part_number');

        $markets = collect();
        foreach ($ratings as $rating) {
            $ratingMarkets = $matches->get($rating, collect())->pluck('market')->unique();
            if ($ratingMarkets->count() !== 1) {
                return null;
            }
            $markets->push($ratingMarkets->first());
        }

        return $markets->unique()->count() === 1 ? $markets->first() : null;
    }

    /** @return array{inserted: int, updated: int, skipped: int} */
    public function importFromExcel(UploadedFile $file, ?int $userId): array
    {
        $rows = Excel::toArray(new RatingAssemblyMappingsImport, $file)[0] ?? [];
        $result = ['inserted' => 0, 'updated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($rows, $userId, &$result): void {
            foreach ($rows as $row) {
                $data = RatingAssemblyMappingsImport::normalizeRow($row);
                if (Validator::make($data, [
                    'rating_part_number' => ['required', 'string', 'max:80'],
                    'assembly_part_number' => ['required', 'string', 'max:80'],
                    'market' => ['required'],
                    'serial_part_number' => ['nullable', 'string', 'max:80'],
                    'shipping_part_number' => ['nullable', 'string', 'max:80'],
                    'inner_part_number' => ['nullable', 'string', 'max:80'],
                ])->fails()) {
                    $result['skipped']++;

                    continue;
                }

                $mapping = RatingAssemblyMapping::query()->firstOrNew([
                    'rating_part_number' => $data['rating_part_number'],
                    'assembly_part_number' => $data['assembly_part_number'],
                    'market' => $data['market'],
                ]);
                $wasRecentlyCreated = ! $mapping->exists;
                $mapping->fill([...$data, 'active' => true, 'updated_by_user_id' => $userId])->save();
                $result[$wasRecentlyCreated ? 'inserted' : 'updated']++;
            }
        });

        return $result;
    }

    private function payload(array $data, ?int $userId, bool $defaultActive): array
    {
        return [
            'rating_part_number' => $this->normalize($data['rating_part_number'] ?? null),
            'serial_part_number' => $this->normalize($data['serial_part_number'] ?? null),
            'shipping_part_number' => $this->normalize($data['shipping_part_number'] ?? null),
            'inner_part_number' => $this->normalize($data['inner_part_number'] ?? null),
            'assembly_part_number' => $this->normalize($data['assembly_part_number'] ?? null),
            'market' => strtoupper(trim((string) ($data['market'] ?? ''))),
            'active' => (bool) ($data['active'] ?? $defaultActive),
            'updated_by_user_id' => $userId,
        ];
    }

    private function normalize(mixed $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return $value === '' ? null : $value;
    }
}
