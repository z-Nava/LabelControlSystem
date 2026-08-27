<?php

namespace App\Services\Catalogs;

use App\Imports\MasterModelMappingsImport;
use App\Models\MasterModelMapping;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MasterModelMappingService
{
    public function paginateByType(string $type, int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        return MasterModelMapping::query()
            ->where('master_sheet_type', $type)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('np', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('active')
            ->orderBy('np')
            ->orderBy('sku')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, string $type): MasterModelMapping
    {
        $payload = $this->normalizePayload($data, $type, true);

        return MasterModelMapping::create($payload);
    }

    public function update(MasterModelMapping $mapping, array $data, string $type): MasterModelMapping
    {
        $payload = $this->normalizePayload($data, $type, (bool) $mapping->active);
        $mapping->update($payload);

        return $mapping;
    }

    public function toggleActive(MasterModelMapping $mapping): MasterModelMapping
    {
        $mapping->update(['active' => ! $mapping->active]);

        return $mapping;
    }

    public function resolveModelFromJobs(string $requestType, ?string $assemblyNp, ?string $packagingNp): ?string
    {
        $targetType = $requestType;
        $lookupNps = [];

        if ($requestType === MasterModelMapping::TYPE_ASSEMBLY_PACKAGING) {
            $lookupNps = [
                $this->normalizeValue($packagingNp),
                $this->normalizeValue($assemblyNp),
            ];
        } elseif (in_array($requestType, [
            MasterModelMapping::TYPE_ASSEMBLY,
            MasterModelMapping::TYPE_ORT_ASSEMBLY,
        ], true)) {
            $lookupNps = [
                $this->normalizeValue($packagingNp) ?? $this->normalizeValue($assemblyNp),
            ];
            $targetType = $requestType === MasterModelMapping::TYPE_ORT_ASSEMBLY
                ? MasterModelMapping::TYPE_ASSEMBLY
                : $requestType;
        } elseif (in_array($requestType, [MasterModelMapping::TYPE_BATTERIES_ASSEMBLY, MasterModelMapping::TYPE_MOTORS_MOLDING], true)) {
            $lookupNps = [$this->normalizeValue($assemblyNp)];
        }

        if (! in_array($targetType, MasterModelMapping::TYPES, true)) {
            return null;
        }

        foreach ($lookupNps as $lookupNp) {
            if (! $lookupNp) {
                continue;
            }

            $mapping = $this->findActiveMapping($targetType, $lookupNp);

            if ($mapping) {
                return $mapping->sku;
            }
        }

        return null;
    }

    public function resolveAssemblyPackagingModel(?string $assemblyNp): ?string
    {
        $normalizedAssemblyNp = $this->normalizeValue($assemblyNp);

        if (! $normalizedAssemblyNp) {
            return null;
        }

        return $this->findActiveMapping(
            MasterModelMapping::TYPE_ASSEMBLY_PACKAGING,
            $normalizedAssemblyNp,
        )?->sku;
    }

    /**
     * @param  iterable<mixed>  $assemblyNps
     * @return array<string, string>
     */
    public function resolveAssemblyPackagingModels(iterable $assemblyNps): array
    {
        $normalizedAssemblyNps = collect($assemblyNps)
            ->map(fn (mixed $assemblyNp): ?string => $this->normalizeValue($assemblyNp))
            ->filter()
            ->unique()
            ->values();

        if ($normalizedAssemblyNps->isEmpty()) {
            return [];
        }

        return MasterModelMapping::query()
            ->where('master_sheet_type', MasterModelMapping::TYPE_ASSEMBLY_PACKAGING)
            ->where('active', true)
            ->whereIn('np', $normalizedAssemblyNps)
            ->pluck('sku', 'np')
            ->all();
    }

    /**
     * Resolve the model that an NP would use for each supported request type.
     *
     * @return array<string, string|null>
     */
    public function resolveModelsForNp(?string $np): array
    {
        $models = [];

        foreach (MasterModelMapping::REQUEST_TYPES as $requestType) {
            $models[$requestType] = $this->resolveModelFromJobs($requestType, $np, $np);
        }

        return $models;
    }

    public function importFromExcel(UploadedFile $file, ?string $forcedType = null): array
    {
        $rows = Excel::toArray(new MasterModelMappingsImport, $file)[0] ?? [];

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $forcedType, &$inserted, &$updated, &$skipped) {
            foreach ($rows as $row) {
                $normalized = MasterModelMappingsImport::normalizeRow($row);
                $resolvedType = $forcedType ?? $normalized['master_sheet_type'];

                if (! $normalized['np'] || ! $normalized['sku'] || ! $resolvedType || ! in_array($resolvedType, MasterModelMapping::TYPES, true)) {
                    $skipped++;

                    continue;
                }

                if ($forcedType && $normalized['master_sheet_type'] && $normalized['master_sheet_type'] !== $forcedType) {
                    $skipped++;

                    continue;
                }

                $payload = [
                    'np' => $this->normalizeValue($normalized['np']),
                    'sku' => $this->normalizeValue($normalized['sku']),
                    'master_sheet_type' => $resolvedType,
                    'active' => true,
                ];

                $existing = MasterModelMapping::query()
                    ->where('np', $payload['np'])
                    ->where('master_sheet_type', $payload['master_sheet_type'])
                    ->first();

                if ($existing) {
                    $existing->update([
                        'sku' => $payload['sku'],
                        'active' => true,
                    ]);
                    $updated++;
                } else {
                    MasterModelMapping::create($payload);
                    $inserted++;
                }
            }
        });

        return compact('inserted', 'updated', 'skipped');
    }

    private function normalizePayload(array $data, string $type, bool $defaultActive): array
    {
        return [
            'np' => $this->normalizeValue($data['np'] ?? null),
            'sku' => $this->normalizeValue($data['sku'] ?? null),
            'master_sheet_type' => $type,
            'active' => (bool) ($data['active'] ?? $defaultActive),
        ];
    }

    protected function findActiveMapping(string $type, string $np): ?MasterModelMapping
    {
        return MasterModelMapping::query()
            ->where('master_sheet_type', $type)
            ->where('np', $np)
            ->where('active', true)
            ->orderByDesc('id')
            ->first();
    }

    private function normalizeValue(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }
}
