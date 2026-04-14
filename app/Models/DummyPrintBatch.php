<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DummyPrintBatch extends Model
{
    protected $table = 'dummy_print_batches';

    protected $fillable = [
        'dummy_request_id',
        'shift_id',
        'batch_type',
        'reason',
        'printed_by_user_id',
        'printed_by_name',
        'quantity',
        'printed_at',
    ];

    protected $casts = [
        'dummy_request_id' => 'integer',
        'shift_id' => 'integer',
        'printed_by_user_id' => 'integer',
        'quantity' => 'integer',
        'printed_at' => 'datetime',
    ];

    public function dummyRequest(): BelongsTo
    {
        return $this->belongsTo(DummyRequest::class, 'dummy_request_id');
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
        return $this->hasMany(DummyPrintBatchItem::class, 'dummy_print_batch_id');
    }

    public function isReprint(): bool
    {
        return $this->batch_type === 'reprint';
    }
}
