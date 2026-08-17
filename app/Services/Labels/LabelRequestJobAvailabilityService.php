<?php

namespace App\Services\Labels;

use App\Models\LabelRequest;
use App\Models\OracleJob;

class LabelRequestJobAvailabilityService
{
    /**
     * @return array{job_qty: int, reserved_quantity: int, available_quantity: int}
     */
    public function calculate(OracleJob $job): array
    {
        $jobQty = max(0, (int) $job->job_qty);
        $jobNumber = strtoupper(trim((string) $job->job_number));
        $reservedQuantity = (int) LabelRequest::query()
            ->whereRaw('UPPER(TRIM(job_number)) = ?', [$jobNumber])
            ->where('status', '!=', LabelRequest::STATUS_CANCELLED)
            ->sum('quantity_requested');

        return [
            'job_qty' => $jobQty,
            'reserved_quantity' => $reservedQuantity,
            'available_quantity' => max(0, $jobQty - $reservedQuantity),
        ];
    }
}
