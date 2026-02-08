<?php

namespace App\Services\Catalogs;

use App\Models\ProductionLine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductionLineService
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return ProductionLine::query()
            ->when($search, function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('line_type', 'like', "%{$search}%");
            })
            ->orderBy('active', 'desc')
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): ProductionLine
    {
        $data['code'] = strtoupper(trim($data['code']));
        $data['active'] = (bool) ($data['active'] ?? true);

        return ProductionLine::create($data);
    }

    public function update(ProductionLine $line, array $data): ProductionLine
    {
        $data['code'] = strtoupper(trim($data['code']));
        $data['active'] = (bool) ($data['active'] ?? false);

        $line->update($data);

        return $line;
    }

    public function toggleActive(ProductionLine $line): ProductionLine
    {
        $line->update(['active' => !$line->active]);
        return $line;
    }
}
