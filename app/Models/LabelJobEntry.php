<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelJobEntry extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['shared_shipping' => 'array', 'work_date' => 'date'];

    public function labelRequest(): BelongsTo
    {
        return $this->belongsTo(LabelRequest::class);
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
