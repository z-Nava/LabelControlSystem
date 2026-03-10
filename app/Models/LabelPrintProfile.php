<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabelPrintProfile extends Model
{
    protected $table = 'label_print_profiles';

    protected $fillable = [
        'label_sku_id',
        'label_type',
        'label_template_id',
        'name',
        'default_printer_name',
        'default_printer_ip',
        'dpi',
        'darkness',
        'speed',
        'media_type',
        'media_tracking',
        'print_mode',
        'offset_x',
        'offset_y',
        'settings',
        'is_active',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'label_sku_id' => 'integer',
        'dpi' => 'integer',
        'label_template_id' => 'integer',
        'darkness' => 'integer',
        'speed' => 'integer',
        'offset_x' => 'integer',
        'offset_y' => 'integer',
        'settings' => 'array',
        'is_active' => 'boolean',
        'created_by_user_id' => 'integer',
        'updated_by_user_id' => 'integer',
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(LabelSku::class, 'label_sku_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LabelPrintProfileVersion::class, 'label_print_profile_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LabelTemplate::class, 'label_template_id');
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
