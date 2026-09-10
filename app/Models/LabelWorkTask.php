<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelWorkTask extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['jobs' => 'array', 'quantity' => 'integer', 'evidence_quantity' => 'integer', 'work_date' => 'date', 'completed_at' => 'datetime'];

    public function labelRequest(): BelongsTo
    {
        return $this->belongsTo(LabelRequest::class);
    }

    public function range(): BelongsTo
    {
        return $this->belongsTo(SerialRange::class, 'serial_range_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function printedShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'printed_shift_id');
    }
}
