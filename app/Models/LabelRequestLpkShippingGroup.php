<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelRequestLpkShippingGroup extends Model
{
    protected $fillable = [
        'part_number',
        'quantity',
        'po_number',
        'destination',
        'position',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'position' => 'integer',
    ];

    public function labelRequest(): BelongsTo
    {
        return $this->belongsTo(LabelRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabelRequestLpkShippingItem::class)
            ->orderBy('position')
            ->orderBy('id');
    }
}
