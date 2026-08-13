<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const SOURCE_LABEL_ROOM = 'label_room';

    public const SOURCE_KIOSK = 'kiosk';

    public const SOURCES = [
        self::SOURCE_LABEL_ROOM,
        self::SOURCE_KIOSK,
    ];

    protected $fillable = [
        'request_date',
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
        'folios_from',
        'folios_to',
        'std_pack_qty',
        'partial_folio',
        'partial_qty',
        'request_type',
        'kind',
        'status',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
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

    public function getRequestSourceLabelAttribute(): string
    {
        return $this->isFromKiosk() ? 'Kiosco' : 'Label Room';
    }
}
