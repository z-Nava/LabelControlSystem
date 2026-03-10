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
        'separator',
        'year_digits',
        'week_digits',
        'include_year',
        'include_week',
        'pattern',
        'unit_length',
        'next_unit',
        'is_active',
        'updated_by_user_id',
    ];

    protected $casts = [
        'unit_length' => 'integer',
        'year_digits' => 'integer',
        'week_digits' => 'integer',
        'next_unit' => 'integer',
        'include_year' => 'boolean',
        'include_week' => 'boolean',
        'is_active' => 'boolean',
        'updated_by_user_id' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
