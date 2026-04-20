<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DummyRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const REPRINT_SELECTION_ELIGIBLE_STATUSES = [
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
    ];

    protected $table = 'dummy_requests';

    protected $fillable = [
        'request_date',
        'week',
        'line_id',
        'shift_id',
        'leader_name',
        'requested_by_name',
        'requested_by_user_id',
        'job_number',
        'fg_code',
        'quantity_requested',
        'range_from',
        'range_to',
        'request_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date:Y-m-d',
        'week' => 'integer',
        'line_id' => 'integer',
        'shift_id' => 'integer',
        'requested_by_user_id' => 'integer',
        'quantity_requested' => 'integer',
        'range_from' => 'integer',
        'range_to' => 'integer',
    ];

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

    public function items(): HasMany
    {
        return $this->hasMany(DummyRequestItem::class, 'dummy_request_id');
    }

    public function printBatches(): HasMany
    {
        return $this->hasMany(DummyPrintBatch::class, 'dummy_request_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['requested', 'in_progress']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_REQUESTED => 'Solicitada',
            self::STATUS_IN_PROGRESS => 'En proceso',
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_CANCELLED => 'Cancelada',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function statusBadgeClasses(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-700 border-red-200',
            self::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-700 border-amber-200',
            default => 'bg-sky-100 text-sky-700 border-sky-200',
        };
    }

    public function requestTypeTitle(): string
    {
        return $this->request_type === 'rework' ? 'RW Dummy QR' : 'RMT Dummy QR';
    }

    public function printedQuantity(): int
    {
        if (array_key_exists('printed_qty', $this->attributes)) {
            return (int) $this->attributes['printed_qty'];
        }

        if ($this->relationLoaded('printBatches')) {
            return (int) $this->printBatches->sum('quantity');
        }

        return (int) $this->printBatches()->sum('quantity');
    }

    public function hasPrintedInitialBatch(): bool
    {
        if ($this->relationLoaded('printBatches')) {
            return $this->printBatches
                ->contains(fn (DummyPrintBatch $batch) => $batch->batch_type === 'print' && $batch->printed_at !== null);
        }

        return $this->printBatches()
            ->where('batch_type', 'print')
            ->whereNotNull('printed_at')
            ->exists();
    }

    public function canAccessSelectionReprint(): bool
    {
        if (!in_array($this->status, self::REPRINT_SELECTION_ELIGIBLE_STATUSES, true)) {
            return false;
        }

        return $this->hasPrintedInitialBatch();
    }

    public function selectionReprintBlockedReason(): ?string
    {
        if (!in_array($this->status, self::REPRINT_SELECTION_ELIGIBLE_STATUSES, true)) {
            return 'Disponible solo en requisiciones En proceso o Completadas.';
        }

        if (!$this->hasPrintedInitialBatch()) {
            return 'Debes imprimir y confirmar al menos un batch inicial para habilitar reimpresión por selección.';
        }

        return null;
    }
}
