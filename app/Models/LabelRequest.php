<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelRequest extends Model
{
    protected $table = 'label_requests';

    protected $fillable = [
        'request_date',
        'week',
        'line_id',
        'shift_id',
        'leader_name',
        'requested_by_name',
        'requested_by_user_id',
        'label_part_number',
        'po_number',
        'destination',
        'model',
        'job_number',
        'quantity_requested',
        'include_serial',
        'include_rating',
        'status',
        'notes',
    ];

    protected $casts = [
        'request_date'    => 'date:Y-m-d',
        'week'            => 'integer',
        'line_id'         => 'integer',
        'shift_id'        => 'integer',
        'requested_by_user_id' => 'integer',
        'quantity_requested'   => 'integer',
        'include_serial'  => 'boolean',
        'include_rating'  => 'boolean',
    ];

    // Relaciones
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

    public function printBatches(): HasMany
    {
        return $this->hasMany(LabelPrintBatch::class, 'label_request_id');
    }

    // Scopes útiles
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['requested', 'in_progress']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['completed', 'cancelled']);
    }
}