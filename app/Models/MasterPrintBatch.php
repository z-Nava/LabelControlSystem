<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterPrintBatch extends Model
{
    protected $fillable = [
        'master_request_id',
        'shift_id',
        'batch_type',
        'reason',
        'printed_by_user_id',
        'printed_by_name',
        'printed_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];

    public function masterRequest(): BelongsTo
    {
        return $this->belongsTo(MasterRequest::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MasterRequestBatchItem::class, 'master_print_batch_id');
    }
}
