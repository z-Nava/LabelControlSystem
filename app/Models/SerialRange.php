<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SerialRange extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['range_start' => 'integer', 'range_end' => 'integer', 'quantity' => 'integer', 'evidence_folio' => 'integer'];

    public function week(): BelongsTo
    {
        return $this->belongsTo(SerialWeek::class, 'serial_week_id');
    }

    public function labelRequest(): BelongsTo
    {
        return $this->belongsTo(LabelRequest::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(LabelWorkTask::class);
    }
}
