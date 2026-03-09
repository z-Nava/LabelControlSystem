<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkuSerialFormat extends Model
{
    protected $table = 'sku_serial_formats';

    protected $fillable = [
        'sku',
        'prefix',
        'serial_break',
        'plant_code',
        'pattern',
        'unit_length',
        'next_unit',
        'is_active',
        'updated_by_user_id',
    ];

    protected $casts = [
        'unit_length' => 'integer',
        'next_unit' => 'integer',
        'is_active' => 'boolean',
        'updated_by_user_id' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
