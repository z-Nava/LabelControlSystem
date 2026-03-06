<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelRequestService
{
    public function create(array $data): LabelRequest
    {
        return DB::transaction(function () use ($data) {
            $payload = $data;
            $payload['status'] = 'requested';

            return LabelRequest::query()->create($payload)->load(['line', 'shift']);
        });
    }

    public function complete(LabelRequest $labelRequest): LabelRequest
    {
        if ($labelRequest->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'No se puede completar una requisición cancelada.',
            ]);
        }

        $labelRequest->update(['status' => 'completed']);

        return $labelRequest->refresh();
    }

    public function cancel(LabelRequest $labelRequest): LabelRequest
    {
        if (in_array($labelRequest->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden cancelar requisiciones en estado requested o in_progress.',
            ]);
        }

        $labelRequest->update(['status' => 'cancelled']);

        return $labelRequest->refresh();
    }
}
