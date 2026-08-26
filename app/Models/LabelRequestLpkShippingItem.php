<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelRequestLpkShippingItem extends Model
{
    protected $fillable = [
        'job_number',
        'model',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(
            LabelRequestLpkShippingGroup::class,
            'label_request_lpk_shipping_group_id',
        );
    }
}
