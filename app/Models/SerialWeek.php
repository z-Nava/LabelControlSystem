<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialWeek extends Model
{
    protected $table = 'serial_weeks';

    protected $fillable = [
        'label_part_number',
        'serial_standard',
        'week',
        'year',
        'prefix',
        'last_serial_number',
    ];

    protected $casts = [
        'week' => 'integer',
        'year' => 'integer',
        'last_serial_number' => 'integer',
    ];
}
