<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialUnit extends Model
{
    protected $table = 'serial_units';

    protected $fillable = [
        'serial_week_id',
        'serial_number',
        'serial_full',
        'status',
    ];

    protected $casts = [
        'serial_week_id' => 'integer',
        'serial_number' => 'integer',
    ];
}

