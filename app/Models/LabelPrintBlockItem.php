<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelPrintBlockItem extends Model
{
    protected $table = 'label_print_block_items';

    protected $fillable = [
        'label_print_block_id',
        'label_print_batch_item_id',
    ];

    protected $casts = [
        'label_print_block_id' => 'integer',
        'label_print_batch_item_id' => 'integer',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(LabelPrintBlock::class, 'label_print_block_id');
    }

    public function batchItem(): BelongsTo
    {
        return $this->belongsTo(LabelPrintBatchItem::class, 'label_print_batch_item_id');
    }
}
