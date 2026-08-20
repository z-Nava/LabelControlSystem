<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KioskRequisitionPrintJob extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENDING = 'sending';

    public const STATUS_PRINTED = 'printed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'token',
        'label_request_id',
        'master_request_id',
        'requested_by_user_id',
        'status',
        'attempts',
        'printer_name',
        'zpl',
        'last_error',
        'dispatched_at',
        'printed_at',
    ];

    protected $casts = [
        'label_request_id' => 'integer',
        'master_request_id' => 'integer',
        'requested_by_user_id' => 'integer',
        'attempts' => 'integer',
        'dispatched_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    protected $hidden = [
        'zpl',
    ];

    public function labelRequest(): BelongsTo
    {
        return $this->belongsTo(LabelRequest::class);
    }

    public function masterRequest(): BelongsTo
    {
        return $this->belongsTo(MasterRequest::class);
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
