<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';

    protected $fillable = [
        'request_date',
        'week',
        'line_id',
        'shift_id',
        'leader_name',
        'requested_by_name',
        'requested_by_user_id',
        'po_number',
        'job_assembly',
        'job_packaging',
        'destination',
        'local',
        'folios_from',
        'folios_to',
        'std_pack_qty',
        'partial_folio',
        'partial_qty',
        'request_type',
        'kind',
        'status',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class, 'line_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function folios(): HasMany
    {
        return $this->hasMany(MasterRequestFolio::class);
    }

    public function printBatches(): HasMany
    {
        return $this->hasMany(MasterPrintBatch::class, 'master_request_id');
    }
}
