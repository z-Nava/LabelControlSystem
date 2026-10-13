<?php

namespace App\Services\Masters;

use App\Models\MasterActionRequest;
use App\Models\MasterRequest;
use App\Models\MasterRequestFolio;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterActionRequestService
{
    public function __construct(
        private readonly MasterReworkService $reworkService,
        private readonly MasterPrintService $printService,
    ) {}

    public function searchSources(string $query, string $type): LengthAwarePaginator
    {
        $sources = $this->eligibleSources($type)
            ->withCount('folios');

        if (ctype_digit($query)) {
            $sources->where(function (Builder $sources) use ($query): void {
                $sources->whereKey((int) $query)
                    ->orWhere('job_assembly', 'like', "%{$query}%")
                    ->orWhere('job_packaging', 'like', "%{$query}%");
            });
        } else {
            $sources->where(function (Builder $sources) use ($query): void {
                $sources->where('job_assembly', 'like', "%{$query}%")
                    ->orWhere('job_packaging', 'like', "%{$query}%");
            });
        }

        return $sources->latest('id')->paginate(10)->withQueryString();
    }

    public function findSelectableSource(int $sourceId, string $type): MasterRequest
    {
        return $this->eligibleSources($type)
            ->with(['line', 'folios' => fn ($folios) => $folios->orderBy('folio_number')])
            ->findOrFail($sourceId);
    }

    public function submit(array $data, User $requester): MasterActionRequest
    {
        return DB::transaction(function () use ($data, $requester): MasterActionRequest {
            $type = $data['action_type'];
            $source = MasterRequest::query()->lockForUpdate()->findOrFail($data['master_request_id']);
            $this->ensureSourceAvailable($source, $type);
            $folioIds = $data['folio_ids'] ?? [];

            if ($type === MasterActionRequest::TYPE_REWORK
                && $folioIds === []
                && (int) ($data['additional_folios_count'] ?? 0) === 0) {
                throw ValidationException::withMessages([
                    'folio_ids' => 'Selecciona al menos un folio original o agrega un folio nuevo.',
                ]);
            }

            $selectedFolios = $this->selectedFolios($source, $folioIds, $type);

            $action = MasterActionRequest::query()->create([
                'master_request_id' => $source->id,
                'action_type' => $type,
                'status' => MasterActionRequest::STATUS_PENDING,
                'copies' => $type === MasterActionRequest::TYPE_REPRINT ? (int) $data['copies'] : null,
                'additional_folios_count' => $type === MasterActionRequest::TYPE_REWORK
                    ? (int) ($data['additional_folios_count'] ?? 0)
                    : 0,
                'reason' => $data['reason'],
                'requested_by_user_id' => $requester->id,
                'requested_by_name' => $requester->name,
            ]);
            $action->selectedFolios()->attach($selectedFolios->modelKeys());

            return $action;
        });
    }

    public function approve(MasterActionRequest $action, User $reviewer, ?array $revisionData = null): MasterActionRequest
    {
        return DB::transaction(function () use ($action, $reviewer, $revisionData): MasterActionRequest {
            $action = MasterActionRequest::query()
                ->with('selectedFolios')
                ->lockForUpdate()
                ->findOrFail($action->id);
            $this->ensurePending($action);

            $source = MasterRequest::query()
                ->with('folios')
                ->lockForUpdate()
                ->findOrFail($action->master_request_id);
            $this->ensureSourceAvailable($source, $action->action_type);
            $selectedFolios = $this->selectedFolios(
                $source,
                $action->selectedFolios->modelKeys(),
                $action->action_type,
            );

            if ($action->action_type === MasterActionRequest::TYPE_REWORK) {
                if ($revisionData === null) {
                    throw ValidationException::withMessages([
                        'status' => 'Revisa los datos y las cantidades del retrabajo antes de confirmarlo.',
                    ]);
                }

                $revision = $this->reworkService->createRevision(
                    baseRequest: $source,
                    data: $revisionData,
                    userId: $reviewer->id,
                    userName: $reviewer->name,
                );
                $action->resulting_master_request_id = $revision->id;
            } else {
                $batch = $this->printService->createBatch(
                    masterRequest: $source,
                    folioIds: $selectedFolios->modelKeys(),
                    batchType: MasterActionRequest::TYPE_REPRINT,
                    copies: $action->copies,
                    reason: $action->reason,
                    printedByUserId: $reviewer->id,
                    printedByName: $reviewer->name,
                );
                $action->resulting_print_batch_id = $batch->id;
            }

            $action->status = MasterActionRequest::STATUS_APPROVED;
            $action->reviewed_by_user_id = $reviewer->id;
            $action->reviewed_by_name = $reviewer->name;
            $action->reviewed_at = now();
            $action->save();

            return $action;
        });
    }

    public function reject(MasterActionRequest $action, User $reviewer, string $reason): MasterActionRequest
    {
        return DB::transaction(function () use ($action, $reviewer, $reason): MasterActionRequest {
            $action = MasterActionRequest::query()->lockForUpdate()->findOrFail($action->id);
            $this->ensurePending($action);

            $action->update([
                'status' => MasterActionRequest::STATUS_REJECTED,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_by_name' => $reviewer->name,
                'reviewed_at' => now(),
                'review_notes' => $reason,
            ]);

            return $action;
        });
    }

    private function eligibleSources(string $type): Builder
    {
        $query = MasterRequest::query()
            ->whereNull('parent_master_request_id')
            ->whereIn('status', [
                MasterRequest::STATUS_IN_PROGRESS,
                MasterRequest::STATUS_COMPLETED,
            ]);

        if ($type === MasterActionRequest::TYPE_REPRINT) {
            $query->whereHas('folios', fn (Builder $folios) => $folios->where('status', 'printed'));
        }

        return $query;
    }

    private function ensureSourceAvailable(MasterRequest $source, string $type): void
    {
        if ($source->isRework()) {
            throw ValidationException::withMessages([
                'master_request_id' => 'Selecciona la requisición Master original; no se puede solicitar sobre una revisión.',
            ]);
        }

        if (! $source->canBeReworked()) {
            throw ValidationException::withMessages([
                'master_request_id' => 'La requisición origen debe estar en proceso o completada.',
            ]);
        }

        if ($type === MasterActionRequest::TYPE_REPRINT
            && ! $source->folios()->where('status', 'printed')->exists()) {
            throw ValidationException::withMessages([
                'master_request_id' => 'La requisición origen no tiene folios impresos para reimprimir.',
            ]);
        }
    }

    /** @param array<int, int|string> $folioIds */
    private function selectedFolios(MasterRequest $source, array $folioIds, string $type): Collection
    {
        $ids = collect($folioIds)->map(fn (mixed $id): int => (int) $id)->unique()->values();
        $folios = MasterRequestFolio::query()
            ->where('master_request_id', $source->id)
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get();

        if (($type === MasterActionRequest::TYPE_REPRINT && $ids->isEmpty())
            || $folios->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'folio_ids' => 'Selecciona folios que pertenezcan a la requisición origen.',
            ]);
        }

        if ($type === MasterActionRequest::TYPE_REPRINT && $folios->contains('status', '!=', 'printed')) {
            throw ValidationException::withMessages([
                'folio_ids' => 'Solo se pueden reimprimir folios cuya impresión ya fue confirmada.',
            ]);
        }

        return $folios->sortBy('folio_number')->values();
    }

    private function ensurePending(MasterActionRequest $action): void
    {
        if (! $action->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Esta solicitud ya fue revisada.',
            ]);
        }
    }
}
