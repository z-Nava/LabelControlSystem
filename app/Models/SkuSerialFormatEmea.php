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
        'prefix_source',
        'prefix_digits',
        'conformity_code',
        'plant_code',
        'unit_digits',
        'declaration_required',
        'reset_scope',
        'pattern',
    ];

    protected $casts = [
        'prefix_digits' => 'integer',
        'unit_digits' => 'integer',
        'declaration_required' => 'boolean',
    ];

    public function serialFormat(): BelongsTo
    {
        return $this->belongsTo(SkuSerialFormat::class, 'sku_serial_format_id');
    }
}
