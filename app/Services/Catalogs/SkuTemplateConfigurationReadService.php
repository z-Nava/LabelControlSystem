<?php

namespace App\Services\Catalogs;

use App\Models\LabelPrintProfile;

class SkuTemplateConfigurationReadService
{
    private const SORT_OPTIONS = [
        'sku' => 'SKU (A → Z)',
        'type' => 'Tipo de etiqueta',
        'updated' => 'Última actualización',
    ];

    public function paginateForIndex(array $filters, int $perPage = 15): array
    {
        $search = $filters['q'] ?? null;
        $sort = $filters['sort'] ?? 'sku';
        $serialStandard = $filters['serial_standard'] ?? 'ALL';

        $configs = LabelPrintProfile::query()
            ->with(['sku', 'template'])
            ->leftJoin('label_skus', 'label_skus.id', '=', 'label_print_profiles.label_sku_id')
            ->select('label_print_profiles.*')
            ->when($search, function ($query, string $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('label_print_profiles.name', 'like', "%{$search}%")
                        ->orWhere('label_print_profiles.label_type', 'like', "%{$search}%")
                        ->orWhereHas('sku', fn ($skuQuery) => $skuQuery
                            ->where('sku', 'like', "%{$search}%")
                            ->orWhere('label_part_number', 'like', "%{$search}%"));
                });
            })
            ->when(
                $serialStandard !== 'ALL',
                fn ($query) => $query->where('label_skus.serial_standard', $serialStandard)
            )
            ->when($sort === 'sku', function ($query) {
                $query->orderByRaw("CASE WHEN label_skus.sku IS NULL OR label_skus.sku = '' THEN 1 ELSE 0 END")
                    ->orderBy('label_skus.sku')
                    ->orderBy('label_skus.label_part_number')
                    ->orderBy('label_print_profiles.label_type')
                    ->orderBy('label_print_profiles.name');
            })
            ->when($sort === 'type', function ($query) {
                $query->orderBy('label_print_profiles.label_type')
                    ->orderBy('label_skus.sku')
                    ->orderBy('label_print_profiles.name');
            })
            ->when(
                $sort === 'updated',
                fn ($query) => $query->orderByDesc('label_print_profiles.updated_at')
            )
            ->orderByDesc('label_print_profiles.is_active')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'configs' => $configs,
            'search' => $search,
            'sort' => $sort,
            'sorts' => self::SORT_OPTIONS,
            'serialStandard' => $serialStandard,
        ];
    }
}
