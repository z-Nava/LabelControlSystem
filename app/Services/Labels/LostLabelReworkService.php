<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LostLabelReworkService
{
    public function resolveSourceReference(string $reference, string $requestKind): ?LabelRequest
    {
        $reference = strtoupper(trim($reference));

        if ($reference === '' || ! array_key_exists($requestKind, LabelRequest::KIND_LABELS)) {
            return null;
        }

        $eligible = $this->eligibleSources($requestKind);

        if (ctype_digit($reference)) {
            $sourceById = (clone $eligible)->whereKey((int) $reference)->first();

            if ($sourceById) {
                return $sourceById;
            }
        }

        return $eligible
            ->whereRaw('UPPER(TRIM(job_number)) = ?', [$reference])
            ->orderByDesc('id')
            ->first();
    }

    public function assertValid(LabelRequest $request, LabelRequest $source): void
    {
        if ($source->id === $request->id || $source->request_kind !== $request->request_kind
            || ! $source->released_at || $source->status === LabelRequest::STATUS_CANCELLED
            || $source->isOriginalReprint()) {
            throw ValidationException::withMessages([
                'source_label_request_id' => 'Selecciona una requisición anterior liberada, del mismo tipo y con folios originales vigentes.',
            ]);
        }

        if ($request->include_inner || $request->include_shipping) {
            throw ValidationException::withMessages([
                'folio_mode' => 'La reposición con folios nuevos sólo puede contener etiquetas Serial o Rating.',
            ]);
        }

        if ($source->serial_standard && $request->serial_standard
            && $source->serial_standard !== $request->serial_standard) {
            throw ValidationException::withMessages([
                'source_label_request_id' => 'El mercado del retrabajo debe ser el mismo de la requisición original.',
            ]);
        }

        $requested = $this->folioLines($request);
        $original = $this->folioLines($source);
        $requestedTypes = $this->folioTypes($request);
        $originalTypes = $this->folioTypes($source);

        if ($originalTypes->isEmpty()) {
            throw ValidationException::withMessages([
                'source_label_request_id' => 'La requisición original no contiene etiquetas Serial ni Rating para reponer.',
            ]);
        }

        if ($requestedTypes->all() !== $originalTypes->all()) {
            throw ValidationException::withMessages([
                'folio_mode' => 'El retrabajo debe conservar los tipos Serial y/o Rating de la requisición original.',
            ]);
        }

        if ($requested->isEmpty() || $requested->pluck('type')->unique()->sort()->values()->all() !== $requestedTypes->all()) {
            throw ValidationException::withMessages([
                'folio_mode' => 'Captura las etiquetas Serial y/o Rating indicadas por la requisición original.',
            ]);
        }

        foreach ($requested as $line) {
            $matchingOriginal = $original->first(fn (array $item) => $this->identity($item) === $this->identity($line));
            if (! $matchingOriginal) {
                throw ValidationException::withMessages([
                    'source_label_request_id' => "La requisición original no contiene {$line['type']} {$line['part']} para Job {$line['job']} y modelo {$line['model']}.",
                ]);
            }
            if ($line['quantity'] > $matchingOriginal['quantity']) {
                throw ValidationException::withMessages([
                    'source_label_request_id' => 'La cantidad a reponer no puede superar la cantidad original de ese Job y etiqueta.',
                ]);
            }
        }

        $source->loadMissing('workTasks');
        foreach ($requested as $line) {
            if (! $source->workTasks->contains(fn ($task) => $task->status === 'completed'
                && strtolower((string) $task->label_type) === $line['type']
                && strtoupper((string) $task->part_number) === $line['part']
                && collect($task->jobs ?? [])->contains(fn ($job) => strtoupper((string) ($job['job_number'] ?? '')) === $line['job']))) {
                throw ValidationException::withMessages([
                    'source_label_request_id' => 'Cada etiqueta del retrabajo debe corresponder a una etiqueta impresa de la requisición original.',
                ]);
            }
        }
    }

    /** @return Collection<int, array{type: string, part: string, job: string, model: string, quantity: int}> */
    private function folioLines(LabelRequest $request): Collection
    {
        return collect($request->requestedLabelLines())
            ->filter(fn (array $line) => in_array(strtolower((string) $line['type']), ['serial', 'rating'], true))
            ->map(fn (array $line) => [
                'type' => strtolower((string) $line['type']),
                'part' => strtoupper(trim((string) ($line['part_number'] ?? ''))),
                'job' => strtoupper(trim((string) ($line['job_number'] ?? $request->job_number))),
                'model' => strtoupper(trim((string) ($line['model'] ?? ''))),
                'quantity' => (int) $line['quantity'],
            ])->values();
    }

    private function identity(array $line): string
    {
        return implode('|', [$line['type'], $line['part'], $line['job'], $line['model']]);
    }

    /** @return Collection<int, string> */
    private function folioTypes(LabelRequest $request): Collection
    {
        return collect([
            $request->include_rating ? 'rating' : null,
            $request->include_serial ? 'serial' : null,
        ])->filter()->sort()->values();
    }

    private function eligibleSources(string $requestKind): Builder
    {
        return LabelRequest::query()
            ->where('request_kind', $requestKind)
            ->whereNotNull('released_at')
            ->where('status', '!=', LabelRequest::STATUS_CANCELLED)
            ->where(function (Builder $query): void {
                $query->whereNull('folio_mode')
                    ->orWhere('folio_mode', '!=', LabelRequest::FOLIO_MODE_REPRINT_ORIGINALS);
            })
            ->where(function (Builder $query): void {
                $query->where('include_serial', true)
                    ->orWhere('include_rating', true);
            });
    }
}
