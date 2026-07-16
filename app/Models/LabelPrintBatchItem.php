<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelPrintBatchItem extends Model
{
    protected $table = 'label_print_batch_items';

    protected $fillable = [
        'label_print_batch_id',
        'serial_unit_id', // Fase 1: nullable (sin FK real aún)
        'print_serial',
        'print_rating',
        'copies',
    ];

    protected $casts = [
        'label_print_batch_id' => 'integer',
        'serial_unit_id'       => 'integer',
        'print_serial'         => 'boolean',
        'print_rating'         => 'boolean',
        'copies'               => 'integer',
    ];

    // Relaciones
    public function batch(): BelongsTo
    {
        return $this->belongsTo(LabelPrintBatch::class, 'label_print_batch_id');
    }

    public function serialUnit(): BelongsTo
    {
        return $this->belongsTo(SerialUnit::class, 'serial_unit_id');
    }

    public function blockItems(): HasMany
    {
        return $this->hasMany(LabelPrintBlockItem::class, 'label_print_batch_item_id');
    }

    // Helpers
    public function printsAnything(): bool
    {
        return (bool) $this->print_serial || (bool) $this->print_rating;
    }
}
