<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use App\Models\OracleJob;
use App\Services\Catalogs\MasterModelMappingService;
use Illuminate\Support\Collection;

class LabelWorkDefinitionService
{
    public function __construct(private readonly MasterModelMappingService $mappings) {}

    /** Each Shipping group is one physical task, even when several Jobs share it. */
    public function forRequest(LabelRequest $request): Collection
    {
        $lines = collect();
        if ($request->hasGroupedLpkDetails()) {
            foreach ($request->lpkLabelGroups as $group) {
                foreach ($group->items as $item) {
                    $lines->push($this->line('item_'.$item->id, $group->label_type, $group->part_number,
                        [['job_number' => $item->job_number, 'model' => $item->model]], $item->quantity));
                }
            }
            foreach ($request->lpkShippingGroups as $group) {
                $lines->push($this->line('shipping_'.$group->id, 'shipping', $group->part_number,
                    $group->items->map(fn ($item) => ['job_number' => $item->job_number, 'model' => $item->model])->all(),
                    $group->quantity, $group->po_number, $group->destination));
            }
        } else {
            foreach ($request->requestedLabelLines() as $index => $line) {
                $lines->push($this->line('line_'.$index, strtolower(explode(' ', $line['type'])[0]),
                    $line['part_number'] ?? '', [['job_number' => $request->job_number, 'model' => $line['model']]],
                    $line['quantity'], $request->po_number, $request->destination));
            }
        }

        $jobs = OracleJob::whereIn('job_number', $lines->flatMap(fn ($line) => array_column($line['jobs'], 'job_number'))->unique())->get()->keyBy('job_number');
        $models = $this->mappings->resolveAssemblyPackagingModels($jobs->pluck('assembly'));

        return $lines->map(function (array $line) use ($lines, $jobs, $models): array {
            $job = $jobs->get($line['job_number']);
            $assembly = strtoupper(trim((string) $job?->assembly));
            $family = $models[$assembly] ?? null;
            $line['assembly_number'] = $assembly;
            $ratings = $lines->where('label_type', 'rating')->where('job_number', $line['job_number'])->where('model', $line['model']);
            $line['folio_family'] = $family;
            $line['rating_part_number'] = $line['label_type'] === 'rating'
                ? $line['part_number'] : ($ratings->count() === 1 ? $ratings->first()['part_number'] : null);
            $line['requires_folios'] = in_array($line['label_type'], ['serial', 'rating'], true);

            return $line;
        })->keyBy('source_key');
    }

    private function line(string $key, string $type, string $part, array $jobs, int $quantity, ?string $po = null, ?string $destination = null): array
    {
        return [
            'source_key' => $key, 'label_type' => $type, 'part_number' => $part,
            'jobs' => $jobs, 'job_number' => count($jobs) === 1 ? $jobs[0]['job_number'] : null,
            'model' => count($jobs) === 1 ? $jobs[0]['model'] : null,
            'quantity' => $quantity, 'po_number' => $po, 'destination' => $destination,
        ];
    }
}
