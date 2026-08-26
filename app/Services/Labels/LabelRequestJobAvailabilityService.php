<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use App\Models\OracleJob;
use Illuminate\Support\Facades\DB;

class LabelRequestJobAvailabilityService
{
    /**
     * @return array{job_qty: int, reserved_quantity: int, available_quantity: int}
     */
    public function calculate(OracleJob $job): array
    {
        $jobQty = max(0, (int) $job->job_qty);
        $jobNumber = strtoupper(trim((string) $job->job_number));
        $legacyReservedQuantity = (int) LabelRequest::query()
            ->whereRaw('UPPER(TRIM(job_number)) = ?', [$jobNumber])
            ->where('status', '!=', LabelRequest::STATUS_CANCELLED)
            ->where(function ($query) {
                $query->where('request_kind', '!=', LabelRequest::KIND_LPK)
                    ->orWhere(function ($legacyLpkQuery) {
                        $legacyLpkQuery
                            ->where('request_kind', LabelRequest::KIND_LPK)
                            ->whereDoesntHave('lpkLabelGroups')
                            ->whereDoesntHave('lpkShippingGroups');
                    });
            })
            ->sum('quantity_requested');

        $groupedLpkReservedQuantity = DB::table('label_request_lpk_label_items as items')
            ->join(
                'label_request_lpk_label_groups as groups',
                'groups.id',
                '=',
                'items.label_request_lpk_label_group_id',
            )
            ->join('label_requests as requests', 'requests.id', '=', 'groups.label_request_id')
            ->whereRaw('UPPER(TRIM(items.job_number)) = ?', [$jobNumber])
            ->where('requests.status', '!=', LabelRequest::STATUS_CANCELLED)
            ->groupBy('requests.id')
            ->selectRaw('MAX(items.quantity) as reserved_quantity')
            ->get()
            ->sum(fn (object $reservation): int => (int) $reservation->reserved_quantity);

        $reservedQuantity = $legacyReservedQuantity + $groupedLpkReservedQuantity;

        return [
            'job_qty' => $jobQty,
            'reserved_quantity' => $reservedQuantity,
            'available_quantity' => max(0, $jobQty - $reservedQuantity),
        ];
    }
}
