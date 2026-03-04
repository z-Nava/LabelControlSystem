<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelPrintBatch extends Model
{
    protected $table = 'label_print_batches';

    protected $fillable = [
        'label_request_id',
        'serial_week_id',       // Fase 1: nullable (sin FK real aún)
        'shift_id',
        'batch_type',           // print|reprint|rework
        'reason',
        'printed_by_user_id',
        'printed_by_name',
        'printed_at',
    ];

    protected $casts = [
        'label_request_id'   => 'integer',
        'serial_week_id'     => 'integer',
        'shift_id'           => 'integer',
        'printed_by_user_id' => 'integer',
        'printed_at'         => 'datetime',
    ];

    // Relaciones
    public function labelRequest(): BelongsTo
    {
        return $this->belongsTo(LabelRequest::class, 'label_request_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function printedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabelPrintBatchItem::class, 'label_print_batch_id');
    }

    // Helpers
    public function isRework(): bool
    {
        return $this->batch_type === 'rework';
    }

    public function isReprint(): bool
    {
        return $this->batch_type === 'reprint';
    }
}