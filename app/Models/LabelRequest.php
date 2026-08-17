<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_ATTENDED = 'attended';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const OPEN_STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ATTENDED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_REQUESTED => 'Pendiente',
        self::STATUS_IN_PROGRESS => 'Requisición impresa',
        self::STATUS_ATTENDED => 'Atendida',
        self::STATUS_COMPLETED => 'Entregada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    protected $table = 'label_requests';

    protected $fillable = [
        'request_date',
        'week',
        'line_id',
        'shift_id',
        'leader_name',
        'requested_by_name',
        'requested_by_user_id',
        'label_part_number',
        'serial_part_number',
        'serial_standard',
        'po_number',
        'destination',
        'model',
        'job_number',
        'quantity_requested',
        'shipping_quantity',
        'folio_start',
        'folio_end',
        'include_serial',
        'include_rating',
        'include_inner',
        'include_shipping',
        'status',
        'requisition_printed_at',
        'requisition_printed_by_user_id',
        'attended_at',
        'attended_by_user_id',
        'delivered_at',
        'delivered_by_user_id',
        'cancelled_at',
        'cancelled_by_user_id',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date:Y-m-d',
        'week' => 'integer',
        'line_id' => 'integer',
        'shift_id' => 'integer',
        'requested_by_user_id' => 'integer',
        'quantity_requested' => 'integer',
        'shipping_quantity' => 'integer',
        'folio_start' => 'integer',
        'folio_end' => 'integer',
        'include_serial' => 'boolean',
        'include_rating' => 'boolean',
        'include_inner' => 'boolean',
        'include_shipping' => 'boolean',
        'requisition_printed_at' => 'datetime',
        'requisition_printed_by_user_id' => 'integer',
        'attended_at' => 'datetime',
        'attended_by_user_id' => 'integer',
        'delivered_at' => 'datetime',
        'delivered_by_user_id' => 'integer',
        'cancelled_at' => 'datetime',
        'cancelled_by_user_id' => 'integer',
    ];

    // Relaciones
    public function line(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class, 'line_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function oracleJob(): BelongsTo
    {
        return $this->belongsTo(OracleJob::class, 'job_number', 'job_number');
    }

    public function printBatches(): HasMany
    {
        return $this->hasMany(LabelPrintBatch::class, 'label_request_id');
    }

    public function serialRanges(): HasMany
    {
        return $this->hasMany(SerialRange::class, 'label_request_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(LabelRequestRating::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function requisitionPrintedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requisition_printed_by_user_id');
    }

    public function attendedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by_user_id');
    }

    public function deliveredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by_user_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function canMarkRequisitionPrinted(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    public function canMarkAttended(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function canMarkDelivered(): bool
    {
        return $this->status === self::STATUS_ATTENDED;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_REQUESTED, self::STATUS_IN_PROGRESS], true);
    }

    /**
     * @return array<int, string>
     */
    public function requestedLabelTypes(): array
    {
        return array_values(array_filter([
            $this->include_serial ? 'Serial' : null,
            $this->include_rating ? 'Rating' : null,
            $this->include_inner ? 'Inner' : null,
            $this->include_shipping ? 'Shipping' : null,
        ]));
    }

    /**
     * @return array<int, string>
     */
    public function requestedRatingPartNumbers(): array
    {
        $partNumbers = ($this->relationLoaded('ratings')
            ? $this->ratings
            : $this->ratings()->get())
            ->pluck('part_number')
            ->filter()
            ->values()
            ->all();

        if ($partNumbers === [] && $this->include_rating && $this->label_part_number) {
            return [(string) $this->label_part_number];
        }

        return $partNumbers;
    }

    /**
     * @return array<int, array{type: string, part_number: ?string, quantity: int}>
     */
    public function requestedLabelLines(): array
    {
        $lines = [];

        if ($this->include_serial) {
            $lines[] = [
                'type' => 'Serial',
                'part_number' => $this->serial_part_number ?: $this->label_part_number,
                'quantity' => (int) $this->quantity_requested,
            ];
        }

        if ($this->include_rating) {
            foreach ($this->requestedRatingPartNumbers() as $partNumber) {
                $lines[] = [
                    'type' => 'Rating',
                    'part_number' => $partNumber,
                    'quantity' => (int) $this->quantity_requested,
                ];
            }
        }

        if ($this->include_inner) {
            $lines[] = [
                'type' => 'Inner',
                'part_number' => null,
                'quantity' => (int) $this->quantity_requested,
            ];
        }

        if ($this->include_shipping) {
            $lines[] = [
                'type' => 'Shipping',
                'part_number' => null,
                'quantity' => (int) ($this->shipping_quantity ?? $this->quantity_requested),
            ];
        }

        return $lines;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getStatusBadgeClassesAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'border-violet-200 bg-violet-100 text-violet-700',
            self::STATUS_ATTENDED => 'border-amber-200 bg-amber-100 text-amber-700',
            self::STATUS_COMPLETED => 'border-emerald-200 bg-emerald-100 text-emerald-700',
            self::STATUS_CANCELLED => 'border-red-200 bg-red-100 text-red-700',
            default => 'border-sky-200 bg-sky-100 text-sky-700',
        };
    }

    // Scopes útiles
    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }
}
