<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MasterActionRequest extends Model
{
    public const TYPE_REWORK = 'rework';

    public const TYPE_REPRINT = 'reprint';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'master_request_id',
        'action_type',
        'status',
        'copies',
        'additional_folios_count',
        'reason',
        'proposed_rework',
        'requested_by_user_id',
        'requested_by_name',
        'reviewed_by_user_id',
        'reviewed_by_name',
        'reviewed_at',
        'review_notes',
        'resulting_master_request_id',
        'resulting_print_batch_id',
    ];

    protected $casts = [
        'copies' => 'integer',
        'additional_folios_count' => 'integer',
        'reviewed_at' => 'datetime',
        'proposed_rework' => 'array',
    ];

    public function masterRequest(): BelongsTo
    {
        return $this->belongsTo(MasterRequest::class);
    }

    public function selectedFolios(): BelongsToMany
    {
        return $this->belongsToMany(
            MasterRequestFolio::class,
            'master_action_request_folios',
        )->withTimestamps()->orderBy('folio_number');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function resultingMasterRequest(): BelongsTo
    {
        return $this->belongsTo(MasterRequest::class, 'resulting_master_request_id');
    }

    public function resultingPrintBatch(): BelongsTo
    {
        return $this->belongsTo(MasterPrintBatch::class, 'resulting_print_batch_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function typeLabel(): string
    {
        return $this->action_type === self::TYPE_REWORK ? 'Retrabajo' : 'Reimpresión';
    }
}
