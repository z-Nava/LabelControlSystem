<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuSerialFormatEmea extends Model
{
    protected $table = 'sku_serial_format_emea';

    protected $fillable = [
        'sku_serial_format_id',
        'prefix_value',
        'conformity_code',
        'unit_digits',
        'pattern',
    ];

    protected $casts = [
        'unit_digits' => 'integer',
    ];

    public function serialFormat(): BelongsTo
    {
        return $this->belongsTo(SkuSerialFormat::class, 'sku_serial_format_id');
    }
}
