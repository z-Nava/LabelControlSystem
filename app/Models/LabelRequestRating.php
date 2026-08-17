<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelRequestRating extends Model
{
    protected $fillable = [
        'part_number',
        'position',
    ];

    protected $casts = [
        'label_request_id' => 'integer',
        'position' => 'integer',
    ];

    public function labelRequest(): BelongsTo
    {
        return $this->belongsTo(LabelRequest::class);
    }
}
