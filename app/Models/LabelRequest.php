<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LabelRequest extends Model
{
    public const KIND_STANDARD = 'standard';

    public const KIND_LPK = 'lpk';

    public const KIND_LABELS = [
        self::KIND_STANDARD => 'Estándar',
        self::KIND_LPK => 'LPK',
    ];

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_READY_FOR_DELIVERY = 'attended';

    /** @deprecated Use STATUS_READY_FOR_DELIVERY. */
    public const STATUS_ATTENDED = self::STATUS_READY_FOR_DELIVERY;

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const OPEN_STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_READY_FOR_DELIVERY,
    ];

    public const STATUS_LABELS = [
        self::STATUS_REQUESTED => 'Pendiente',
        self::STATUS_IN_PROGRESS => 'En preparación',
        self::STATUS_READY_FOR_DELIVERY => 'Lista para entregar',
        self::STATUS_COMPLETED => 'Entregada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    protected $table = 'label_requests';

    protected $fillable = [
        'request_kind',
        'request_date',
        'week',
        'line_id',
        'shift_id',
        'leader_name',
        'requested_by_name',
        'requested_by_user_id',
        'label_part_number',
        'serial_part_number',
        'inner_part_number',
        'inner_model',
        'shipping_part_number',
        'shipping_model',
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

    public function shippingItems(): HasMany
    {
        return $this->hasMany(LabelRequestShippingItem::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(LabelRequestSerial::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function lpkLabelGroups(): HasMany
    {
        return $this->hasMany(LabelRequestLpkLabelGroup::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function lpkShippingGroups(): HasMany
    {
        return $this->hasMany(LabelRequestLpkShippingGroup::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function kioskRequisitionPrintJob(): HasOne
    {
        return $this->hasOne(KioskRequisitionPrintJob::class);
    }

    public function requisitionPrintedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requisition_printed_by_user_id');
    }

    public function readyForDeliveryByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by_user_id');
    }

    /** @deprecated Use readyForDeliveryByUser(). */
    public function attendedByUser(): BelongsTo
    {
        return $this->readyForDeliveryByUser();
    }

    public function deliveredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by_user_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function canStartPreparation(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    public function canMarkReadyForDelivery(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function canConfirmDelivery(): bool
    {
        return $this->status === self::STATUS_READY_FOR_DELIVERY;
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
        return array_column($this->requestedRatingItems(), 'part_number');
    }

    /**
     * @return array<int, string>
     */
    public function requestedSerialPartNumbers(): array
    {
        return array_column($this->requestedSerialItems(), 'part_number');
    }

    /**
     * @return array<int, array{part_number: string, model: ?string}>
     */
    public function requestedSerialItems(): array
    {
        $items = ($this->relationLoaded('serials')
            ? $this->serials
            : $this->serials()->get())
            ->filter(fn (LabelRequestSerial $item) => filled($item->part_number))
            ->map(fn (LabelRequestSerial $item) => [
                'part_number' => (string) $item->part_number,
                'model' => filled($item->model) ? (string) $item->model : null,
            ])
            ->values()
            ->all();

        if ($items === [] && $this->include_serial) {
            $legacyPartNumber = $this->serial_part_number ?: $this->label_part_number;

            if ($legacyPartNumber) {
                return [[
                    'part_number' => (string) $legacyPartNumber,
                    'model' => filled($this->model) ? (string) $this->model : null,
                ]];
            }
        }

        return $items;
    }

    /**
     * @return array<int, array{part_number: string, model: ?string}>
     */
    public function requestedRatingItems(): array
    {
        $items = ($this->relationLoaded('ratings')
            ? $this->ratings
            : $this->ratings()->get())
            ->filter(fn (LabelRequestRating $item) => filled($item->part_number))
            ->map(fn (LabelRequestRating $item) => [
                'part_number' => (string) $item->part_number,
                'model' => filled($item->model) ? (string) $item->model : null,
            ])
            ->values()
            ->all();

        if ($items === [] && $this->include_rating && $this->label_part_number) {
            return [[
                'part_number' => (string) $this->label_part_number,
                'model' => filled($this->model) ? (string) $this->model : null,
            ]];
        }

        return $items;
    }

    /**
     * @return array<int, string>
     */
    public function requestedShippingItemReferences(): array
    {
        return array_column($this->requestedShippingItems(), 'part_number');
    }

    /**
     * @return array<int, array{part_number: string, model: ?string}>
     */
    public function requestedShippingItems(): array
    {
        $items = ($this->relationLoaded('shippingItems')
            ? $this->shippingItems
            : $this->shippingItems()->get())
            ->filter(fn (LabelRequestShippingItem $item) => filled($item->item_reference))
            ->map(fn (LabelRequestShippingItem $item) => [
                'part_number' => (string) $item->item_reference,
                'model' => filled($item->model) ? (string) $item->model : null,
            ])
            ->values()
            ->all();

        if ($items === [] && $this->include_shipping && $this->shipping_part_number) {
            return [[
                'part_number' => (string) $this->shipping_part_number,
                'model' => filled($this->shipping_model) ? (string) $this->shipping_model : null,
            ]];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function requestedLabelLines(): array
    {
        if ($this->hasGroupedLpkDetails()) {
            return $this->groupedLpkLabelLines();
        }

        $lines = [];

        if ($this->include_serial) {
            foreach ($this->requestedSerialItems() as $item) {
                $lines[] = [
                    'type' => 'Serial',
                    'part_number' => $item['part_number'],
                    'model' => $item['model'],
                    'quantity' => (int) $this->quantity_requested,
                ];
            }
        }

        if ($this->include_rating) {
            foreach ($this->requestedRatingItems() as $item) {
                $lines[] = [
                    'type' => 'Rating',
                    'part_number' => $item['part_number'],
                    'model' => $item['model'],
                    'quantity' => (int) $this->quantity_requested,
                ];
            }
        }

        if ($this->include_inner) {
            $lines[] = [
                'type' => 'Inner',
                'part_number' => $this->inner_part_number,
                'model' => $this->inner_model,
                'quantity' => (int) $this->quantity_requested,
            ];
        }

        if ($this->include_shipping) {
            $shippingItems = $this->requestedShippingItems();

            if ($shippingItems === []) {
                $lines[] = [
                    'type' => 'Shipping',
                    'part_number' => $this->shipping_part_number,
                    'model' => $this->shipping_model,
                    'quantity' => (int) ($this->shipping_quantity ?? $this->quantity_requested),
                ];
            } else {
                foreach ($shippingItems as $item) {
                    $lines[] = [
                        'type' => $this->isLpk() ? 'Shipping LPK' : 'Shipping',
                        'part_number' => $item['part_number'],
                        'model' => $item['model'],
                        'quantity' => (int) ($this->shipping_quantity ?? $this->quantity_requested),
                    ];
                }
            }
        }

        return $lines;
    }

    public function hasGroupedLpkDetails(): bool
    {
        if (! $this->isLpk()) {
            return false;
        }

        if ($this->relationLoaded('lpkLabelGroups') && $this->lpkLabelGroups->isNotEmpty()) {
            return true;
        }

        if ($this->relationLoaded('lpkShippingGroups') && $this->lpkShippingGroups->isNotEmpty()) {
            return true;
        }

        if (
            $this->relationLoaded('lpkLabelGroups')
            && $this->relationLoaded('lpkShippingGroups')
        ) {
            return false;
        }

        return $this->exists
            && ($this->lpkLabelGroups()->exists() || $this->lpkShippingGroups()->exists());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groupedLpkLabelLines(): array
    {
        $labelGroups = $this->relationLoaded('lpkLabelGroups')
            ? $this->lpkLabelGroups
            : $this->lpkLabelGroups()->with('items')->get();
        $shippingGroups = $this->relationLoaded('lpkShippingGroups')
            ? $this->lpkShippingGroups
            : $this->lpkShippingGroups()->with('items')->get();
        $lines = [];

        foreach ($labelGroups as $group) {
            $items = $group->relationLoaded('items') ? $group->items : $group->items()->get();

            foreach ($items as $item) {
                $lines[] = [
                    'type' => $group->type_label,
                    'part_number' => $group->part_number,
                    'model' => $item->model,
                    'job_number' => $item->job_number,
                    'quantity' => (int) $item->quantity,
                ];
            }
        }

        foreach ($shippingGroups as $group) {
            $items = $group->relationLoaded('items') ? $group->items : $group->items()->get();

            $lines[] = [
                'type' => 'Shipping LPK',
                'part_number' => $group->part_number,
                'model' => $items->pluck('model')->filter()->implode(', ') ?: null,
                'job_number' => $items->pluck('job_number')->filter()->implode(', ') ?: null,
                'quantity' => (int) $group->quantity,
                'po_number' => $group->po_number,
                'destination' => $group->destination,
                'models_count' => $items->count(),
            ];
        }

        return $lines;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getRequestKindLabelAttribute(): string
    {
        return self::KIND_LABELS[$this->request_kind] ?? ucfirst((string) $this->request_kind);
    }

    public function isLpk(): bool
    {
        return $this->request_kind === self::KIND_LPK;
    }

    public function getStatusBadgeClassesAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'border-violet-200 bg-violet-100 text-violet-700',
            self::STATUS_READY_FOR_DELIVERY => 'border-amber-200 bg-amber-100 text-amber-700',
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
