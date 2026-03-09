<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerialRange extends Model
{
    protected $table = 'serial_ranges';

    protected $fillable = [
        'serial_week_id',
        'range_start',
        'range_end',
        'quantity',
        'label_request_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'serial_week_id' => 'integer',
        'range_start' => 'integer',
        'range_end' => 'integer',
        'quantity' => 'integer',
        'label_request_id' => 'integer',
        'created_by_user_id' => 'integer',
    ];

     public function week(): BelongsTo
    {
        return $this->belongsTo(SerialWeek::class, 'serial_week_id');
    }
}


