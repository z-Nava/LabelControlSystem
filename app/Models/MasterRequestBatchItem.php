<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterRequestBatchItem extends Model
{
    protected $fillable = [
        'master_print_batch_id',
        'master_request_folio_id',
        'copies',
        'sheet_snapshot',
    ];

    protected $casts = [
        'sheet_snapshot' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MasterPrintBatch::class, 'master_print_batch_id');
    }

    public function folio(): BelongsTo
    {
        return $this->belongsTo(MasterRequestFolio::class, 'master_request_folio_id');
    }
}
