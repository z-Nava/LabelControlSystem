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
                $q->where('oracle_line', 'like', "%{$search}%")
                    ->orWhere('stock_locator', 'like', "%{$search}%")
                    ->orWhere('subinventory', 'like', "%{$search}%");
            })
            ->orderBy('active', 'desc')
            ->orderBy('oracle_line')
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
        $stockLocator->update(['active' => ! $stockLocator->active]);

        return $stockLocator;
    }

    public function resolveSubinventoryByOracleLine(?string $oracleLine): ?string
    {
        return $this->resolveActiveMappingByOracleLine($oracleLine)?->subinventory;
    }

    public function resolveActiveMappingByOracleLine(?string $oracleLine): ?StockLocator
    {
        if (! $oracleLine) {
            return null;
        }

        return StockLocator::query()
            ->where('oracle_line', strtoupper(trim($oracleLine)))
            ->where('active', true)
            ->first();
    }

    private function normalize(array $data, bool $defaultActive): array
    {
        $data['oracle_line'] = strtoupper(trim($data['oracle_line']));
        $data['subinventory'] = strtoupper(trim($data['subinventory']));
        $data['stock_locator'] = strtoupper(trim($data['stock_locator']));
        $data['active'] = (bool) ($data['active'] ?? $defaultActive);

        return $data;
    }
}
