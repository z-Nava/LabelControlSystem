<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerialUnit extends Model
{
    protected $table = 'serial_units';

    protected $fillable = [
        'serial_week_id',
        'label_sku_id',
        'label_part_number',
        'serial_standard',
        'serial_number',
        'serial_full',
        'rating_qr_code',
        'status',
        'printed_at',
    ];

    protected $casts = [
        'serial_week_id' => 'integer',
        'label_sku_id' => 'integer',
        'serial_number' => 'integer',
        'printed_at' => 'datetime',
    ];

    public function week(): BelongsTo
    {
        return $this->belongsTo(SerialWeek::class, 'serial_week_id');
    }

    public function labelSku(): BelongsTo
    {
        return $this->belongsTo(LabelSku::class, 'label_sku_id');
    }
}
