<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LostLabelReworkService
{
    public function assertValid(LabelRequest $request, LabelRequest $source): void
    {
        if ($source->id === $request->id || $source->request_kind !== $request->request_kind
            || ! $source->released_at || $source->status === LabelRequest::STATUS_CANCELLED
            || $source->isOriginalReprint()) {
            throw ValidationException::withMessages([
                'source_label_request_id' => 'Selecciona una requisición anterior liberada, del mismo tipo y con folios originales vigentes.',
            ]);
        }

        if (! $request->include_serial || ! $request->include_rating
            || $request->include_inner || $request->include_shipping) {
            throw ValidationException::withMessages([
                'folio_mode' => 'La reposición por faltantes debe contener únicamente Serial y Rating juntos.',
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
        if ($requested->isEmpty() || $requested->pluck('type')->unique()->count() !== 2) {
            throw ValidationException::withMessages(['folio_mode' => 'Captura Serial y Rating para la reposición.']);
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

        foreach ($requested->groupBy(fn (array $line) => $line['job'].'|'.$line['model']) as $jobLines) {
            $serial = $jobLines->where('type', 'serial');
            $rating = $jobLines->where('type', 'rating');
            if ($serial->isEmpty() || $rating->isEmpty()
                || $jobLines->pluck('quantity')->unique()->count() !== 1
                || $rating->count() !== 1) {
                throw ValidationException::withMessages([
                    'lpk_label_groups' => 'Cada Job y modelo del retrabajo requiere Serial y un Rating con la misma cantidad de faltantes.',
                ]);
            }
        }

        $source->loadMissing('workTasks');
        foreach ($requested as $line) {
            $rating = $requested->first(fn (array $item) => $item['type'] === 'rating'
                && $item['job'] === $line['job'] && $item['model'] === $line['model']);
            if (! $source->workTasks->contains(fn ($task) => $task->status === 'completed'
                && strtolower((string) $task->label_type) === $line['type']
                && strtoupper((string) $task->part_number) === $line['part']
                && ($line['type'] !== 'serial' || strtoupper((string) $task->rating_part_number) === $rating['part'])
                && collect($task->jobs ?? [])->contains(fn ($job) => strtoupper((string) ($job['job_number'] ?? '')) === $line['job']))) {
                throw ValidationException::withMessages([
                    'source_label_request_id' => 'La pareja Serial y Rating debe corresponder a las etiquetas impresas de la requisición original.',
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
}
