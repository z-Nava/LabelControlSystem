<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DummyRequestItem extends Model
{
    protected $table = 'dummy_request_items';

    protected $fillable = [
        'dummy_request_id',
        'job_number',
        'fg_code',
        'consecutive',
        'consecutive_10d',
        'dummy_type',
        'qr_payload',
        'print_count',
        'last_printed_at',
    ];

    protected $casts = [
        'dummy_request_id' => 'integer',
        'consecutive' => 'integer',
        'print_count' => 'integer',
        'last_printed_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(DummyRequest::class, 'dummy_request_id');
    }

    public function printBatchItems(): HasMany
    {
        return $this->hasMany(DummyPrintBatchItem::class, 'dummy_request_item_id');
    }

    public function isRework(): bool
    {
        return $this->dummy_type === 'rw';
    }
}
