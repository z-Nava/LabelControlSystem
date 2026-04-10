<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelSku extends Model
{
    protected $table = 'label_skus';

    protected $fillable = [
        'sku',
        'serial_standard',
        'label_part_number',
        'description',
        'console_sku',
        'assembly_part_number',
        'packaging_part_number',
        'emea_sku',
        'anz_sku',
        'is_active',
        'updated_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'updated_by_user_id' => 'integer',
    ];

    /**
     * Usuario que hizo el último cambio (opcional).
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * Scope: solo activos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Helpers opcionales.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
