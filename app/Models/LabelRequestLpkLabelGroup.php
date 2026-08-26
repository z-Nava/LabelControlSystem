<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelRequestLpkLabelGroup extends Model
{
    public const TYPE_SERIAL = 'serial';

    public const TYPE_RATING = 'rating';

    public const TYPE_INNER = 'inner';

    public const TYPES = [
        self::TYPE_SERIAL,
        self::TYPE_RATING,
        self::TYPE_INNER,
    ];

    protected $fillable = [
        'label_type',
        'part_number',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function labelRequest(): BelongsTo
    {
        return $this->belongsTo(LabelRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabelRequestLpkLabelItem::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->label_type) {
            self::TYPE_SERIAL => 'Serial',
            self::TYPE_RATING => 'Rating',
            self::TYPE_INNER => 'Inner',
            default => ucfirst((string) $this->label_type),
        };
    }
}
