<?php

namespace App\Services\Catalogs;

use App\Models\StockLocator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockLocatorService
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return StockLocator::query()
            ->when($search, function ($q) use ($search) {
                $q->where('stock_locator', 'like', "%{$search}%")
                    ->orWhere('subinventory', 'like', "%{$search}%");
            })
            ->orderBy('active', 'desc')
            ->orderBy('stock_locator')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): StockLocator
    {
        return StockLocator::create($this->normalize($data, true));
    }

    public function update(StockLocator $stockLocator, array $data): StockLocator
    {
        $stockLocator->update($this->normalize($data, false));

        return $stockLocator;
    }

    public function toggleActive(StockLocator $stockLocator): StockLocator
    {
        $stockLocator->update(['active' => !$stockLocator->active]);

        return $stockLocator;
    }

    public function resolveSubinventoryByStockLocator(?string $stockLocator): ?string
    {
        return $this->resolveActiveMappingByStockLocator($stockLocator)?->subinventory;
    }

    public function resolveActiveMappingByStockLocator(?string $stockLocator): ?StockLocator
    {
        if (!$stockLocator) {
            return null;
        }

        return StockLocator::query()
            ->where('stock_locator', strtoupper(trim($stockLocator)))
            ->where('active', true)
            ->first();
    }

    private function normalize(array $data, bool $defaultActive): array
    {
        $data['stock_locator'] = strtoupper(trim($data['stock_locator']));
        $data['subinventory'] = strtoupper(trim($data['subinventory']));
        $data['active'] = (bool) ($data['active'] ?? $defaultActive);

        return $data;
    }
}
