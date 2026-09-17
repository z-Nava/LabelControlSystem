<?php

namespace App\Services\Kiosk;

use App\Services\Catalogs\RatingAssemblyMappingService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class KioskLabelCatalogService
{
    public function __construct(private readonly RatingAssemblyMappingService $catalog) {}

    public function resolve(?string $assembly, string $type, string $part, mixed $mappingId, string $path, bool $allowManual = false): ?array
    {
        $options = $this->catalog->activeOptionsForAssembly($assembly);
        $column = $type.'_part_number';
        $part = strtoupper(trim($part));
        if (filled($mappingId)) {
            $selected = $options->firstWhere('id', (int) $mappingId);
            if (! $selected || (filled($selected[$column]) && $selected[$column] !== $part)) {
                throw ValidationException::withMessages([$path => 'La selección del catálogo no corresponde al Job y NP actuales. Vuelve a seleccionar la etiqueta.']);
            }

            return $selected;
        }

        // Reprints can refer to a retired Rating that used the same physical label NP.
        if ($allowManual) {
            return null;
        }

        $matches = $options->where($column, $part)->values();
        if ($matches->count() === 1) {
            return $matches->first();
        }
        if ($matches->count() > 1 && in_array($type, ['serial', 'rating'], true)) {
            throw ValidationException::withMessages([$path => 'Este NP tiene varios Ratings o mercados. Selecciona la relación del catálogo para identificar sus folios.']);
        }
        if ($matches->isNotEmpty() && $matches->pluck('market')->unique()->count() === 1) {
            // Inner/Shipping can be shared by several Ratings without choosing an arbitrary control.
            return ['assembly_part_number' => strtoupper(trim((string) $assembly)), 'market' => $matches->first()['market']];
        }
        if ($matches->isEmpty() && $options->isNotEmpty() && $options->every(fn ($row) => filled($row[$column]))) {
            throw ValidationException::withMessages([$path => "El NP {$part} no corresponde a {$type} del empaque {$assembly}. Revisa el catálogo o separa los Jobs en otro grupo."]);
        }

        return null;
    }

    public function market(Collection $snapshots, string $path): ?string
    {
        $markets = $snapshots->pluck('market')->filter()->unique();
        if ($markets->count() > 1) {
            throw ValidationException::withMessages([$path => 'Los folios de una requisición deben pertenecer al mismo mercado. Separa los Jobs o componentes de mercados distintos.']);
        }

        return $snapshots->isNotEmpty() && $snapshots->every(fn ($snapshot) => filled($snapshot['market'] ?? null))
            ? $markets->first() : null;
    }

    public function assertDistinct(Collection $items, string $path, bool $includeModel = false, bool $includeControl = true): void
    {
        $seen = [];
        foreach ($items as $index => $item) {
            $key = json_encode([$item['part_number'], $includeModel ? ($item['model'] ?? null) : null, $includeControl ? ($item['catalog_snapshot']['rating_part_number'] ?? null) : null, $includeControl ? ($item['catalog_snapshot']['market'] ?? null) : null]);
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([$path.'.'.$index.'.part_number' => 'Esta etiqueta y su Rating de control ya están incluidos.']);
            }
            $seen[$key] = true;
        }
    }
}
