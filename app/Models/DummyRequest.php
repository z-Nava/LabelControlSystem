<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DummyRequest extends Model
{
    protected $table = 'dummy_requests';

    protected $fillable = [
        'request_date',
        'week',
        'line_id',
        'shift_id',
        'leader_name',
        'requested_by_name',
        'requested_by_user_id',
        'job_number',
        'fg_code',
        'quantity_requested',
        'range_from',
        'range_to',
        'request_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date:Y-m-d',
        'week' => 'integer',
        'line_id' => 'integer',
        'shift_id' => 'integer',
        'requested_by_user_id' => 'integer',
        'quantity_requested' => 'integer',
        'range_from' => 'integer',
        'range_to' => 'integer',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class, 'line_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DummyRequestItem::class, 'dummy_request_id');
    }

    public function printBatches(): HasMany
    {
        return $this->hasMany(DummyPrintBatch::class, 'dummy_request_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['requested', 'in_progress']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['completed', 'cancelled']);
    }
}
