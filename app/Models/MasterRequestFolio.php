<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterRequestFolio extends Model
{
    protected $fillable = [
        'master_request_id',
        'folio_number',
        'is_partial',
        'qty_for_folio',
        'status',
    ];

    protected $casts = [
        'is_partial' => 'boolean',
    ];

    public function masterRequest(): BelongsTo
    {
        return $this->belongsTo(MasterRequest::class);
    }

    public function batchItems(): HasMany
    {
        return $this->hasMany(MasterRequestBatchItem::class);
    }
}
