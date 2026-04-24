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
        $mapping->update(['active' => !$mapping->active]);

        return $mapping;
    }

    public function resolveModelFromJobs(string $requestType, ?string $assemblyNp, ?string $packagingNp): ?string
    {
        $targetType = $requestType;
        $lookupNp = null;

        if (in_array($requestType, [MasterModelMapping::TYPE_ASSEMBLY, MasterModelMapping::TYPE_ASSEMBLY_PACKAGING], true)) {
            $lookupNp = $this->normalizeValue($packagingNp) ?? $this->normalizeValue($assemblyNp);
            $targetType = $requestType;
        } elseif (in_array($requestType, [MasterModelMapping::TYPE_BATTERIES_ASSEMBLY, MasterModelMapping::TYPE_MOTORS_MOLDING], true)) {
            $lookupNp = $this->normalizeValue($assemblyNp);
        }

        if (!$lookupNp || !in_array($targetType, MasterModelMapping::TYPES, true)) {
            return null;
        }

        $mapping = MasterModelMapping::query()
            ->where('master_sheet_type', $targetType)
            ->where('np', $lookupNp)
            ->where('active', true)
            ->orderByDesc('id')
            ->first();

        return $mapping?->sku;
    }

    public function importFromExcel(UploadedFile $file, ?string $forcedType = null): array
    {
        $rows = Excel::toArray(new MasterModelMappingsImport(), $file)[0] ?? [];

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $forcedType, &$inserted, &$updated, &$skipped) {
            foreach ($rows as $row) {
                $normalized = MasterModelMappingsImport::normalizeRow($row);
                $resolvedType = $forcedType ?? $normalized['master_sheet_type'];

                if (!$normalized['np'] || !$normalized['sku'] || !$resolvedType || !in_array($resolvedType, MasterModelMapping::TYPES, true)) {
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
                    ->where('sku', $payload['sku'])
                    ->where('master_sheet_type', $payload['master_sheet_type'])
                    ->first();

                if ($existing) {
                    $existing->update(['active' => true]);
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

    private function normalizeValue(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }
}
