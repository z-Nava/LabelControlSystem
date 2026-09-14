<?php

namespace App\Models;

use App\Support\SerialPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SerialPeriod extends Model
{
    /**
     * Kept on the historical table to avoid rewriting established range foreign keys.
     */
    protected $table = 'serial_weeks';

    protected $guarded = ['id'];

    protected $casts = [
        'year' => 'integer',
        'week' => 'integer',
        'period_number' => 'integer',
        'last_serial_number' => 'integer',
        'opening_serial_number' => 'integer',
    ];

    public function ranges(): HasMany
    {
        return $this->hasMany(SerialRange::class, 'serial_week_id');
    }

    public function getPeriodLabelAttribute(): string
    {
        if (! $this->period_type || ! $this->period_number) {
            return 'Control histórico · Semana '.$this->week;
        }

        return SerialPeriods::describe($this->period_type, (int) $this->period_number);
    }
}
