<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelPrintBlock extends Model
{
    protected $table = 'label_print_blocks';

    protected $fillable = [
        'label_print_batch_id',
        'label_type',
        'sequence',
        'unit_count',
        'label_count',
        'status',
        'attempts',
        'sent_at',
        'confirmed_at',
        'failed_at',
        'last_error',
    ];

    protected $casts = [
        'label_print_batch_id' => 'integer',
        'sequence' => 'integer',
        'unit_count' => 'integer',
        'label_count' => 'integer',
        'attempts' => 'integer',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LabelPrintBatch::class, 'label_print_batch_id');
    }

    public function blockItems(): HasMany
    {
        return $this->hasMany(LabelPrintBlockItem::class, 'label_print_block_id');
    }
}
