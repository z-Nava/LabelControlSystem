<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelTemplate extends Model
{
    protected $table = 'label_templates';

    protected $fillable = [
        'name',
        'label_type',
        'label_sku_id',
        'dpi',
        'width_mm',
        'height_mm',
        'zpl',
        'meta',
        'version',
        'is_active',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'label_sku_id' => 'integer',
        'dpi' => 'integer',
        'width_mm' => 'decimal:2',
        'height_mm' => 'decimal:2',
        'meta' => 'array',
        'version' => 'integer',
        'is_active' => 'boolean',
        'created_by_user_id' => 'integer',
        'updated_by_user_id' => 'integer',
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(LabelSku::class, 'label_sku_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}