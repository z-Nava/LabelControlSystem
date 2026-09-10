<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SerialWeek extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['year' => 'integer', 'week' => 'integer', 'last_serial_number' => 'integer', 'opening_serial_number' => 'integer'];

    public function ranges(): HasMany
    {
        return $this->hasMany(SerialRange::class);
    }
}
