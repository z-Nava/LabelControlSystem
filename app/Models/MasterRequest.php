<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_LABEL_ROOM = 'label_room';

    public const SOURCE_KIOSK = 'kiosk';

    public const SOURCES = [
        self::SOURCE_LABEL_ROOM,
        self::SOURCE_KIOSK,
    ];

    protected $fillable = [
        'request_date',
        'parent_master_request_id',
        'revision_number',
        'week',
        'line_id',
        'shift_id',
        'leader_name',
        'request_source',
        'requested_by_name',
        'requested_by_user_id',
        'po_number',
        'job_assembly',
        'job_packaging',
        'destination',
        'oracle_line',
        'subinventory',
        'local',
        'model',
        'folios_from',
        'folios_to',
        'std_pack_qty',
        'partial_folio',
        'partial_qty',
        'request_type',
        'kind',
        'status',
        'rework_reason',
        'reworked_by_user_id',
        'reworked_by_name',
        'reworked_at',
        'rework_changes',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancelled_by_name',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'revision_number' => 'integer',
        'reworked_at' => 'datetime',
        'rework_changes' => 'array',
        'cancelled_at' => 'datetime',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class, 'line_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function originalRequest(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_master_request_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_master_request_id')
            ->orderBy('revision_number');
    }

    public function reworkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reworked_by_user_id');
    }

    public function folios(): HasMany
    {
        return $this->hasMany(MasterRequestFolio::class);
    }

    public function printBatches(): HasMany
    {
        return $this->hasMany(MasterPrintBatch::class, 'master_request_id');
    }

    public function isFromLabelRoom(): bool
    {
        return $this->request_source === self::SOURCE_LABEL_ROOM;
    }

    public function isFromKiosk(): bool
    {
        return $this->request_source === self::SOURCE_KIOSK;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRework(): bool
    {
        return $this->parent_master_request_id !== null;
    }

    public function canBeReworked(): bool
    {
        return in_array($this->status, [self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED], true);
    }

    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_REQUESTED => 'Solicitada',
            self::STATUS_IN_PROGRESS => 'En proceso',
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_CANCELLED => 'Cancelada',
            default => $this->status,
        };
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            self::STATUS_REQUESTED => 'bg-blue-100 text-blue-800',
            self::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            default => 'bg-slate-100 text-slate-700',
        };
    }

    public function getRequestSourceLabelAttribute(): string
    {
        return $this->isFromKiosk() ? 'Kiosco' : 'Label Room';
    }
}
