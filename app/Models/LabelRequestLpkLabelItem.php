<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelRequestLpkLabelItem extends Model
{
    protected $fillable = [
        'job_number',
        'model',
        'quantity',
        'position',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'position' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(
            LabelRequestLpkLabelGroup::class,
            'label_request_lpk_label_group_id',
        );
    }
}
