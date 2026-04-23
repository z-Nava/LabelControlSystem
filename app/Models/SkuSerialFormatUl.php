<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuSerialFormatUl extends Model
{
    protected $table = 'sku_serial_format_ul';

    protected $fillable = [
        'sku_serial_format_id',
        'prefix',
        'prefix_length',
        'serial_break',
        'plant_code',
        'use_plant_code',
        'reset_scope',
        'pattern',
    ];

    protected $casts = [
        'prefix_length' => 'integer',
        'use_plant_code' => 'boolean',
    ];

    public function serialFormat(): BelongsTo
    {
        return $this->belongsTo(SkuSerialFormat::class, 'sku_serial_format_id');
    }
}
