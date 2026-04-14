<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DummyPrintBatchItem extends Model
{
    protected $table = 'dummy_print_batch_items';

    protected $fillable = [
        'dummy_print_batch_id',
        'dummy_request_item_id',
        'copies',
    ];

    protected $casts = [
        'dummy_print_batch_id' => 'integer',
        'dummy_request_item_id' => 'integer',
        'copies' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DummyPrintBatch::class, 'dummy_print_batch_id');
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(DummyRequestItem::class, 'dummy_request_item_id');
    }
}
